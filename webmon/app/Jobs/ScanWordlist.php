<?php

namespace App\Jobs;

use App\Dto\UriScanItem;
use App\Dto\UriScanResult;
use App\Events\WordlistFilesFound;
use App\Orm\Domain;
use App\Orm\Scan;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class ScanWordList implements ShouldQueue, WebMonScannerContract
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var array UriScanResult
     */
    protected $filesList = [];
    /**
     * @var Client
     */
    private $client;

    private $failureCounter = 0;

    /**
     * Create a new job instance.
     *
     * @param Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    public function tags()
    {
        return ['scan:wordlist', 'domain:' . $this->domain->id];
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

        $start = time();
        Log::info(sprintf('Starting wordlist scan of domain %s', $this->domain->domain));

        $result = $this->scan($this->domain);

        $scan->last_scan = Carbon::now();
        $scan->results = json_encode($result);
        $scan->save();

        $scan->domain->updated_at = Carbon::now();
        $scan->domain->save();

        Log::info(sprintf('Finished wordlist scan of domain %s, took %f seconds',
            $this->domain->domain,
            time() - $start
        ));

    }

    public function getScanFrequency(): int
    {
        return config('webmon.scanners.wordlist.min_scan_interval');
    }

    protected function getWordList(): array
    {
        return config('webmon.scanners.wordlist.wordlist');
    }

    /**
     * @param string $domain
     * @param string $uri
     * @param bool $secure
     * @param bool $recurse
     * @return int
     */
    protected function scanFile(string $domain, string $uri, bool $secure = false, bool $recurse = true): UriScanResult
    {

        $protocol = $secure ? 'https' : 'http';

        $result = new UriScanResult;
        $result->protocol = $protocol;
        $result->domain = $domain;
        $result->uri = $uri;
        $url = $result->getFullURL();

        $ignoredKeywords = config('webmon.scanners.wordlist.ignored_keywords');

        try {
            $response = $this->client->get($url);

            if (in_array($response->getStatusCode(), [301, 302]) && $recurse) {
                return $this->scanFile($domain, $uri, true, false);
            }

            $responseSize = $response->getBody()->getSize();
            $body = $response->getBody()->getContents();
            $response->getBody()->rewind();

            Log::debug(sprintf('Scan %s%s: HTTP %d (%d bytes)', $domain, $uri, $response->getStatusCode(), $responseSize));

            // If response body contains any of the false-positive keywords, count it as FP
            foreach ($ignoredKeywords as $keyword) {
                if (mb_stristr($body, $keyword)) {
                    return $result;
                }
            }
            if ($responseSize < 100) {
                return $result;
            }

            $result->success = $response->getStatusCode() === 200;
            $result->response = $response;
            return $result;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                Log::debug(sprintf('Scan %s%s: HTTP %d', $domain, $uri, $e->getResponse()->getStatusCode()));
            } else {
                Log::info(sprintf('Scan %s%s: %s', $domain, $uri, get_class($e)));
                $this->failureCounter++;
            }
        } catch (\Exception $e) {
            Log::alert(sprintf('Scan %s%s: error. %s', $domain, $uri, $e->getMessage()));
            $this->failureCounter++;
        }
        return $result;
    }


    /**
     * @param Domain $domain
     * @return array
     */
    protected function runChecks(Domain $domain): void
    {


        $lastBodyText=null;

        foreach ($this->getWordList() as $uri) {

            // Abort if too many fails
            $allowedFailures = config('webmon.scanners.wordlist.abort_after_fails');
            if ($this->failureCounter >= $allowedFailures) {
                Log::alert(
                    sprintf(
                        'Too many failures (fails: %d, allowed: %d) scanning %s, skipping',
                        $this->failureCounter,
                        $allowedFailures,
                        $domain->domain
                    )
                );
                return;
            }

            // Sleep a bit to not DOS the service between each request
            usleep(config('webmon.scanners.wordlist.request_delay') * 1000);

            $scanResult = $this->scanFile($domain->domain, $uri);
            if (!$scanResult->success) {
                continue;
            }

            $body = $scanResult->response->getBody()->getContents();

            $similarityIndex = 0;
            similar_text($lastBodyText, $body, $similarityIndex);
            $lastBodyText = $body;

            if (strlen($lastBodyText) && $similarityIndex > 85) {
                $removedItem = array_pop($this->filesList);
                Log::debug('Item was too similar to previous scan result; probably false-positive due too a 404 page. Removing both results', [
                    'similarityIndex' => $similarityIndex,
                    'previousUri' => $removedItem->uri ?? null,
                    'currentUri' => $uri,
                    'domain' => $domain->domain
                ]);
                continue;
            }
            Log::debug('Comparing...',['similarityIndex'=> $similarityIndex,'lastBodyText'=>$lastBodyText]);


            Log::info(sprintf('Found %s%s (%d bytes)', $domain->domain, $uri, $scanResult->response->getBody()->getSize()));
            $this->filesList[] = $scanResult;
        }


    }


    public function scan(Domain $domain): array
    {
        $this->client = app()->get(ClientInterface::class);

        if (!$this->canConnect($domain->domain)) {
            return ['error' => 'Unable to connect'];
        }
        $this->runChecks($domain);

        if (count($this->filesList)) {
            event(new WordlistFilesFound($domain, $this->filesList));
        } else {
            Log::info(sprintf('Did not find any wordlist matches from %s', $domain->domain));
        }

        return $this->filesList;
    }


    private function canConnect(string $domain): bool
    {
        try {
            /** @var Client $client */
            $response = $this->client->head('http://' . $domain);
            return (bool)$response->getStatusCode();
        } catch (\Exception $e) {
            Log::warning(sprintf('Skipping wordlist scan of %s: unable to connect', $domain), ['e' => $e->getMessage()]);
            return false;
        }
    }
}
