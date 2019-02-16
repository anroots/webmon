<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportPhotonDomains extends AbstractImportDomains
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:import-photon {--tld=*} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import domains from a Photon JSON file';


    /**
     * @param string $file
     * @return array
     * @throws \Exception
     */
    protected function readLines(string $file): \Iterator
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

    protected function getUrlFromLine(string $line): string
    {
        // TODO: Implement getUrlFromLine() method.
    }
}
