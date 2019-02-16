<?php

namespace App\Services;

use App\Orm\Domain;
use App\Orm\Notification;
use Illuminate\Database\Eloquent\Collection;

class GitDomainReportService
{

    public function getProblematicDomains(): Collection
    {
        return Domain::has('notifications')
            ->limit(1000)
            ->get();
    }
}