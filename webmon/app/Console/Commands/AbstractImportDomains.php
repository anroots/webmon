<?php

namespace App\Console\Commands;

use App\Orm\Domain;
use Carbon\Carbon;
use Illuminate\Console\Command;

abstract class AbstractImportDomains extends Command
{


    abstract protected function readLines(string $file): \Iterator;


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


            $d = Domain::firstOrCreate(['domain' => $domain]);

            if ($d->wasRecentlyCreated === false) {
                $this->output->writeln(sprintf('Skipping existing domain %s', $domain));
                continue;
            }

            $d->updated_at = Carbon::now()->subYears(10); // Force rescan immediately
            $d->save();

            $this->output->writeln(sprintf('Inserted domain %s', $domain));
        }

        $this->output->writeln('Done importing domains');
        return 0;
    }


    protected function extractDomain(string $url): ?string
    {
        return parse_url($url, PHP_URL_HOST);
    }


    protected function getTld(string $domain): string
    {
        $bits = explode(".", $domain);
        return end($bits);
    }
}
