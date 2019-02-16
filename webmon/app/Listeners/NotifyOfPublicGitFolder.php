<?php

namespace App\Listeners;

use App\Events\PublicGitFolderFound;
use App\Jobs\ScanPublicGitFolder;
use App\Notifications\PublicGitFolderNotification;
use App\User;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Orm\Notification as NotificationModel;

class NotifyOfPublicGitFolder implements ShouldQueue
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
     * @param  object $event
     * @return void
     */
    public function handle(PublicGitFolderFound $event)
    {
        Log::info(sprintf('Found public .git folder on domain %s', $event->domain->domain), $event->scanDetails);

        $notification = new PublicGitFolderNotification($event);

        foreach (User::getAdmins() as $user) {

            $lastNotification = NotificationModel::firstOrCreate([
                'user_id' => $user->id,
                'domain_id' => $event->domain->id,
                'scan_type' => ScanPublicGitFolder::class
            ]);

            $notificationLimit = Carbon::now()->subMinutes(config('webmon.min_renotify_interval'));

            if ($lastNotification->updated_at->greaterThan($notificationLimit)) {
                Log::info(sprintf('Will not renotify user % about %s on %s', $user->id, ScanPublicGitFolder::class, $event->domain->domain));
                continue;
            }

            Notification::send($user, $notification);
            $lastNotification->updated_at = Carbon::now();
            $lastNotification->save();

        }
    }
}
