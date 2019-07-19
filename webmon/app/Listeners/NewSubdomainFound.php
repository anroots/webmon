<?php

namespace App\Listeners;

use App\Events\WordlistFilesFound;
use App\Jobs\ScanPublicGitFolder;
use App\Notifications\PublicGitFolderNotification;
use App\Orm\Domain;
use App\User;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Orm\Notification as NotificationModel;

class NewSubdomainFound implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }


    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(\App\Events\NewSubdomainFound $event)
    {
        $domain = new Domain;
        $domain->domain = $event->hostname;
        $domain->save();
    }
}
