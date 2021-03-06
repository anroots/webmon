<?php

namespace App\Services;

use App\Jobs\WebMonScannerContract;
use App\Orm\Domain;
use App\Orm\Scan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ScanSchedulerService
{
    protected $scanners = [];

    /**
     * ScanSchedulerService constructor.
     */
    public function __construct()
    {
        $this->loadScanners();
    }

    public function run(): int
    {
        Log::info('Scheduling new scans...');

        $candidates = $this->getCandidateDomains();

        if ($candidates->count() === 0) {
            Log::info('No scans to schedule');
            return 0;
        }

        $count = 0;
        foreach ($candidates as $domain) {
            foreach ($this->scanners as $scanner) {
                $wasScheduled = $this->scheduleScan($domain, $scanner);

                if ($wasScheduled) {
                    $count++;
                }
            }

            $domain->updated_at = Carbon::now();
            $domain->save();
        }
        return $count;
    }

    /**
     * @return Collection
     */
    public function getCandidateDomains()
    {
        // Get domains that haven't been scanned by a minimum of this many minutes
        $limitTime = Carbon::now()->subDays(config('webmon.min_scan_interval'));

        return Domain::whereDate('updated_at', '<=', $limitTime)
            ->limit(10000)
            ->get();
    }

    private function loadScanners(): void
    {
        $scanners = config('webmon.scanners');
        foreach ($scanners as $scanner) {
            $this->scanners[] = app()->get($scanner['class']);
        }
    }

    public function scheduleScan(Domain $domain, WebMonScannerContract $scanner): bool
    {
        $lastScan = Scan::where('domain_id', $domain->id)
            ->where('scan_type', get_class($scanner))
            ->orderBy('id', 'desc')
            ->first();

        if ($lastScan !== null) {
            $diffInMinutes = Carbon::now()->diffInMinutes($lastScan->updated_at, false);

            // Do not scan, if not enough time has passed from the last scan
            // Based on the webmon.min_scan_interval setting of each specific scanner
            if ($diffInMinutes + $scanner->getScanFrequency() > 0) {
                Log::debug(
                    sprintf('Skipping %s scan for domain %s - not enough time has passed from last scan (%d)', get_class($scanner), $domain->domain, $diffInMinutes)
                );
                return false;
            }
        }

        Log::info(sprintf('Scheduling %s scan for domain %s', get_class($scanner), $domain->domain));
        $scanner::dispatch($domain);
        return true;
    }
}
