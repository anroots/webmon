<?php

namespace App\Console\Commands;

use App\Orm\Domain;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportCommonCrawlerDomains extends AbstractImportDomains
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:import-common-crawler {--tld=*} {file}';

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


        $file = fopen($file, 'r');


        while (($line = fgets($file)) !== false) {
            yield json_decode($line,JSON_OBJECT_AS_ARRAY)['url'];
        }

        fclose($file);
    }


}
