<?php

namespace App\Jobs;

use App\Orm\Domain;

interface WebMonScannerContract
{
    public static function getScanFrequency(): int;

    public function scan(Domain $domain): array;
}