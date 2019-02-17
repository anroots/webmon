<?php

namespace App\Console\Commands;

class ImportCsvDomains extends ImportDomains
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:import-csv {--tld=*} {--tags=*} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import domains from a CSV file';


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
            yield trim($line);
        }

        fclose($file);
    }

}
