<?php

namespace App\Console\Commands;

use App\Orm\Domain;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;

class ImportDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:import {--tld=*} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import domains from a Photon JSON file';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->input->getArgument('file');
        $tlds = $this->input->getOption('tld');

        foreach ($this->readLines($file) as $url) {
            $domain = $this->extractDomain($url);

            if ($domain === false) {
                $this->output->writeln(sprintf('Can not import %s (invalid URL), skipping.', $url));
                continue;
            }

            if (!in_array($this->getTld($domain), $tlds)) {
                $this->output->writeln(sprintf('Skipping domain %s because TLD is not in %s', $domain, implode(',', $tlds)));
                continue;
            }

            if (!$this->checkConnection($domain)) {
                $this->output->writeln(sprintf('Can not connect to domain %s, skipping', $domain));
                continue;
            }

            $d = Domain::firstOrCreate(['domain' => $domain]);

            if ($d->wasRecentlyCreated === false) {
                $this->output->writeln(sprintf('Skipping existing domain %s', $domain));
                continue;
            }

            $d->updated_at = Carbon::now()->subYears(10); // Force rescan immediately
            $d->save();

            $this->output->writeln(sprintf('Inserted domain %s', $domain));
        }
        return 0;
    }

    /**
     * @param string $file
     * @return array
     * @throws \Exception
     */
    private function readLines(string $file): array
    {
        if (!file_exists($file)) {
            throw new \Exception('File not found');
        }

        $content = json_decode(file_get_contents($file), JSON_OBJECT_AS_ARRAY);

        if (!$content || !array_key_exists('external', $content)) {
            throw new \Exception('Invalid JSON input file');
        }

        return $content['external'];
    }

    private function extractDomain(string $url): ?string
    {
        return parse_url($url, PHP_URL_HOST);
    }

    private function checkConnection(string $domain): bool
    {
        try {
            /** @var Client $client */
            $client = app()->get(ClientInterface::class);
            $client->get('http://' . $domain);
            return true;
        } catch (ClientException $e) {

        } catch (ConnectException $e) {

        } catch (RequestException $e) {

        }
        return false;
    }

    private function getTld(string $domain): string
    {
        $bits = explode(".", $domain);
        return end($bits);
    }
}
