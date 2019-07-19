<?php

namespace App\Jobs;

use App\Orm\Domain;

interface WebMonScannerContract
{
    public function getScanFrequency(): int;

    public function scan(Domain $domain): array;
}