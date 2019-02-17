<?php

namespace App\Listeners;

use App\Events\PublicGitFolderFound;
use App\Events\UnresolvableDomain;
use App\Jobs\ScanPublicGitFolder;
use App\Notifications\PublicGitFolderNotification;
use App\User;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Orm\Notification as NotificationModel;

class DeleteUnresolvableDomain implements ShouldQueue
{


    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle(UnresolvableDomain $event)
    {
        Log::info(sprintf('Unable to resolve IP to domain %s, marking it as deleted', $event->domain->domain));

        $event->domain->delete();
    }
}
