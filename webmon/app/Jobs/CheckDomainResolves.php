<?php

namespace App\Jobs;

use App\Events\UnresolvableDomain;
use App\Orm\Domain;
use App\Orm\Scan;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CheckDomainResolves implements ShouldQueue, WebMonScannerContract
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * Create a new job instance.
     *
     * @param Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
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

        $result = $this->scan($this->domain);

        $scan->last_scan = Carbon::now();
        $scan->results = json_encode($result);
        $scan->save();

        $scan->domain->updated_at = Carbon::now();
        $scan->domain->save();
    }

    public function getScanFrequency(): int
    {
        return config('webmon.scanners.unresolvable.min_scan_interval');
    }


    public function tags()
    {
        return ['scan:unresolvable', 'domain:'.$this->domain->id];
    }
    /**
     * Return IP address of input domain or null if the DNS name does not resolve
     *
     * @param string $domain
     * @return null|string
     */
    protected function resolves(string $domain): ?string
    {
        $ip = gethostbyname($domain);
        Log::info(sprintf('Resolving %s - %s',$domain,$ip));
        return $domain !== $ip ? $ip : null;
    }

    public function scan(Domain $domain): array
    {

        $ip = $this->resolves($domain->domain);

        if ($ip === null) {
            event(new UnresolvableDomain($domain));
            return ['ip' => null];
        }
        return ['ip' => $ip];
    }


}
