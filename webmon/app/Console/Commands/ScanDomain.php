<?php

namespace App\Console\Commands;

use App\Orm\Domain;
use Illuminate\Console\Command;

class ScanDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan:domain {--scanner=} {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Immediately schedule a domain';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $scannerName = $this->input->getOption('scanner');
        $scannerClass = config('webmon.scanners')[$scannerName]['class'];

        $domain = Domain::firstOrCreate([
            'domain'=>$this->input->getArgument('domain')
        ]);

        $scanner = new $scannerClass($domain);

        $scanner::dispatch($domain);
    }
}
