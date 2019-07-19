<?php

namespace App\Jobs;

use App\Events\NewSubdomainFound;
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
use Pdp\Cache;
use Pdp\CurlHttpClient;
use Pdp\Manager;
use Psr\Http\Message\ResponseInterface;

class DiscoverSubdomains implements ShouldQueue, WebMonScannerContract
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var \Pdp\Rules
     */
    private $rules;


    /**
     * Create a new job instance.
     *
     * @param Domain $domain
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
        $manager = new Manager(new Cache(), new CurlHttpClient());
        $this->rules = $manager->getRules();

    }

    public function tags()
    {
        return ['scan:discoversubdomains', 'domain:' . $this->domain->id];
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting subdomain scan', ['domain' => $this->domain->domain]);

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

    public function getScanFrequency(): int
    {
        return config('webmon.scanners.git.min_scan_interval');
    }


    public function scan(Domain $domain): array
    {

        $domainProperties = $this->rules->resolve($domain->domain);

        // Skip subdomains
        if ($domainProperties->isPrivate() || $domainProperties->getSubDomain()) {
            return ['skip' => true];
        }

        $foundDomains = [];
        foreach ($this->getWordList() as $subdomain) {

            // Check if hostname resolves
            $hostname = sprintf('%s.%s', $subdomain, $domain->domain);
            Log::debug('Scanning subdomain', ['hostname' => $hostname]);
            $host = gethostbyname($hostname);
            if ($host === $hostname) {
                Log::debug('Domain does not resolve, skipping', ['hostname' => $hostname]);
                continue;
            }

            // Test to see if a webserver is listening
            try {
                $fp = fsockopen($hostname, 80, $errno, $errstr, 2);
                fclose($fp);

            } catch (\Exception $e) {
                continue;
            }

            event(new NewSubdomainFound($domain, $hostname));
            $foundDomains[] = $hostname;
        }
        Log::info('Discovered new subdomains', ['domains' => $foundDomains]);

        return $foundDomains;
    }

    private function getWordList()
    {
        return file(resource_path('wordlists/subdomains.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }


}
