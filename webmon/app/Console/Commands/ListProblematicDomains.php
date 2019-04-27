<?php

namespace App\Console\Commands;

use App\Orm\Domain;
use App\Services\GitDomainReportService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;

class ListProblematicDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List problematic domains';
    /**
     * @var GitDomainReportService
     */
    private $gitDomainReportService;

    /**
     * Create a new command instance.
     * @param GitDomainReportService $gitDomainReportService
     */
    public function __construct(GitDomainReportService $gitDomainReportService)
    {
        parent::__construct();

        $this->gitDomainReportService = $gitDomainReportService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $domains = $this->gitDomainReportService->getProblematicDomains();

        $rows = [];
        foreach ($domains as $domain) {
            $rows[] = [$domain->domain, $domain->scan_type, $domain->updated_at];
        }
        $this->output->table(['Domain', 'Problem', 'Problem discovered'], $rows);

        return 0;
    }


}
