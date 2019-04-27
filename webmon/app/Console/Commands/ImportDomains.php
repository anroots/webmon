<?php

namespace App\Console\Commands;

use App\Orm\Domain;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

abstract class ImportDomains extends Command
{


    abstract protected function readLines(string $file): \Iterator;

    private function createDomain(string $domain, array $tags = []): ?Domain
    {

        $d = Domain::firstOrCreate(['domain' => $domain]);

        if ($d->wasRecentlyCreated === false) {
            $this->output->writeln(sprintf('Skipping existing domain %s', $domain));
            return null;
        }

        $d->updated_at = Carbon::now()->subYears(10); // Force rescan immediately
        $d->save();
        $d->attachTags($tags);
        $this->output->writeln(sprintf('Inserted domain %s', $domain));
        Log::info(sprintf('Imported domain %s using %s', $domain,self::class));
        return $d;
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
        $tags = $this->input->getOption('tags');

        foreach ($this->readLines($file) as $url) {

            $domain = $this->extractDomain($url);

            if ($domain === false | $domain === null) {
                $this->output->writeln(sprintf('Can not import %s (invalid URL), skipping.', $url));
                continue;
            }

            if (!in_array('*', $tlds) && !in_array($this->getTld($domain), $tlds)) {
                $this->output->writeln(sprintf('Skipping domain %s because TLD is not in %s', $domain, implode(',', $tlds)));
                continue;
            }

            try {
                $this->createDomain($domain, $tags);
            } catch (\Exception $e) {
                $this->error($e);
                continue;
            }

        }

        $this->output->writeln('Done importing domains');
        return 0;
    }


    protected function extractDomain(string $url): ?string
    {
        if (mb_substr($url, 0, 4) !== 'http') {
            $url = 'http://' . $url;
        }

        return parse_url($url, PHP_URL_HOST);
    }


    protected function getTld(string $domain): string
    {
        $bits = explode(".", $domain);
        return end($bits);
    }
}
