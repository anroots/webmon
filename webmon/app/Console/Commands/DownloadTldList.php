<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DownloadTldList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:download-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download list of domains';

    /**
     * Create a new command instance.
     *
     * @return void
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
        $domains = file_get_contents('https://ee-domains.sqroot.eu/lists/added.txt');
        file_put_contents(storage_path('added.txt'), $domains);
        $this->output->writeln(sprintf('Downloaded %d domains',count(explode("\n",$domains))));
    }
}
