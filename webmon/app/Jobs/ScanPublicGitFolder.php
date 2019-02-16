<?php

namespace App\Jobs;

use App\Events\PublicGitFolderFound;
use App\Orm\Domain;
use App\Orm\Scan;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ScanPublicGitFolder implements ShouldQueue, WebMonScannerContract
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Domain
     */
    private $domain;

    protected $checks = [];

    /**
     * Create a new job instance.
     *
     * @param Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $scan = Scan::firstOrCreate([
            'domain_id' => $this->domain->id,
            'scan_type' => self::class
        ]);

        $result = $this->scan($this->domain);

        $scan->last_scan = Carbon::now();
        $scan->results = json_encode($result);
        $scan->save();

        $scan->domain->updated_at = Carbon::now();
        $scan->domain->save();
    }

    public static function getScanFrequency(): int
    {
        return config('webmon.scanners.git.min_scan_interval');
    }

    /**
     * @param string $domain
     * @param bool $secure
     * @return bool
     */
    protected function hasGitHead(string $domain, $secure = true): bool
    {
        /** @var Client $client */
        $client = app()->get(ClientInterface::class);

        $protocol = $secure ? 'https' : 'http';
        $url = sprintf('%s://%s/.git/HEAD', $protocol, $domain);

        Log::info(sprintf('Scanning %s', $url));

        try {
            $response = $client->get($url);
        } catch (RequestException $e) {
            return false;
        }

        return $response->getStatusCode() === 200 && strpos($response->getBody(), 'refs');
    }

    /**
     * @param string $domain
     * @param bool $secure
     * @return bool
     */
    protected function hasPublicDirectoryIndex(string $domain, $secure = true): bool
    {
        /** @var Client $client */
        $client = app()->get(ClientInterface::class);

        $protocol = $secure ? 'https' : 'http';
        $url = sprintf('%s://%s/.git/', $protocol, $domain);
        Log::info(sprintf('Scanning %s', $url));

        try {
            $response = $client->get($url);
        } catch (RequestException $e) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }
        foreach (['config', 'HEAD', 'objects', 'refs', 'info', 'logs'] as $file) {
            if (!strpos($response->getBody(), $file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Domain $domain
     * @return array
     */
    protected function runChecks(Domain $domain): void
    {
        $this->checks = ['httpGitHead' => $this->hasGitHead($domain->domain, false),
            'httpsGitHead' => $this->hasGitHead($domain->domain, true),
            'httpDirIndex' => $this->hasPublicDirectoryIndex($domain->domain, false),
            'httpsDirIndex' => $this->hasPublicDirectoryIndex($domain->domain, true)
        ];

        $this->checks['lastCommitMessage'] = $this->getLastCommitMessage($domain->domain);
    }

    public function scan(Domain $domain): array
    {

        if (!$this->canConnect($domain->domain)) {
            return ['error' => 'Unable to connect'];
        }
        $this->runChecks($domain);

        if (
            ($this->checks['httpGitHead'] || $this->checks['httpsGitHead']) && ($this->checks['httpDirIndex'] || $this->checks['httpsDirIndex'])
        ) {
            event(new PublicGitFolderFound($domain, $this->checks));
        }
        return $this->checks;
    }

    /**
     * @param string $domain
     * @return null|string
     */
    private function getLastCommitMessage(string $domain): ?string
    {
        if (!$this->checks['httpGitHead'] && !$this->checks['httpsGitHead']) {
            return null;
        }
        /** @var Client $client */
        $client = app()->get(ClientInterface::class);

        $protocol = $this->checks['httpsGitHead'] ? 'https' : 'http';
        $url = sprintf('%s://%s/.git/COMMIT_EDITMSG', $protocol, $domain);

        Log::info(sprintf('Scanning %s', $url));

        try {
            $response = $client->get($url);
        } catch (RequestException $e) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return (string)$response->getBody();
    }

    private function canConnect(string $domain): bool
    {
        try {
            /** @var Client $client */
            $client = app()->get(ClientInterface::class);
            $response = $client->head('http://' . $domain);
            return (bool)$response->getStatusCode();
        } catch (ClientException $e) {

        } catch (ConnectException $e) {

        }
        Log::warning(sprintf('Skipping scan of %s: unable to connect', $domain));
        return false;
    }
}
