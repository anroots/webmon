<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;

class ImportCTDomains extends ImportDomains
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:import-ct {--tld=*} {--tags=*} {--username=} {--password=} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import domains from an URL';

    /**
     * @param string $file
     * @return array
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function readLines(string $file): \Iterator
    {

        $client = new Client;
        $response = $client->request('GET', $file, ['auth' => [$this->input->getOption('username'), $this->input->getOption('password')]]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Unable to open URL');
        }

        $domains = json_decode($response->getBody(), true);

        foreach ($domains as $domain){
            yield $domain['domain'];
        }
    }
}
