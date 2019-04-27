<?php

namespace App\Console\Commands;

use App\Services\ScanSchedulerService;
use GuzzleHttp\ClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run basic diagnostics';
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Create a new command instance.
     *
     */
    public function __construct(ClientInterface $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $checks = [
            'myIp' => $this->getIp()
        ];
        Log::info('Running Healtcheck...', $checks);
        return 0;
    }

    private function getIp(): string
    {
        $r = $this->client->get('https://api.myip.com');
        return json_decode($r->getBody()->getContents())->ip;
    }
}
