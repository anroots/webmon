<?php

namespace App\Console\Commands;

use App\Orm\Domain;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Pdp\Cache;
use Pdp\CurlHttpClient;
use Pdp\Manager;
use Pdp\Rules;

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
        Log::info(sprintf('Imported domain %s using %s', $domain, self::class));
        return $d;
    }

    /**
     * @return Rules
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function getDomainParser()
    {
        $manager = new Manager(new Cache(), new CurlHttpClient());
        return $manager->getRules();
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

            // Ignore certain subdomains
            $domainParser = $this->getDomainParser();
            $parsedDomain = $domainParser->resolve($domain);
            $subdomain = $parsedDomain->getSubDomain();
            if (in_array($subdomain, config('webmon.ignore_subdomains'))) {
                $this->output->writeln(sprintf('Ignoring domain %s because subdomain %s is marked as ignored', $domain, $subdomain));
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
