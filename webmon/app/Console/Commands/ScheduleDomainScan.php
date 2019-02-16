<?php

namespace App\Console\Commands;

use App\Services\ScanSchedulerService;
use Illuminate\Console\Command;

class ScheduleDomainScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule scans for domains that have not been scanned for a long time';
    /**
     * @var ScanSchedulerService
     */
    private $scanSchedulerService;

    /**
     * Create a new command instance.
     *
     * @param ScanSchedulerService $scanSchedulerService
     */
    public function __construct(ScanSchedulerService $scanSchedulerService)
    {
        parent::__construct();
        $this->scanSchedulerService = $scanSchedulerService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $scheduledScans = $this->scanSchedulerService->run();
        $this->output->text(sprintf('Scheduled %d scans', $scheduledScans));
    }
}
