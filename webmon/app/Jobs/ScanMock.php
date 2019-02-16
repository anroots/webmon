<?php

namespace App\Jobs;


use App\Orm\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
class ScanMock implements ShouldQueue, WebMonScannerContract
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public static function getScanFrequency(): int
    {
        return 30;
    }

    public function scan(Domain $domain): array
    {
        return [];
    }

    public function handle(){

    }
}