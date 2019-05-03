<?php

namespace App\Listeners;

use App\Events\WordlistFilesFound;
use App\Notifications\WordlistFilesFoundNotification;
use App\User;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Orm\Notification as NotificationModel;

class NotifyOfWordlistFiles implements ShouldQueue
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
    public function handle(WordlistFilesFound $event)
    {
        Log::info(
            sprintf('Found wordlist files on domain %s', $event->domain->domain),
            ['files' => json_encode($event->filesList)]
        );


        foreach (User::getAdmins() as $user) {

            $lastNotification = NotificationModel::firstOrCreate([
                'user_id' => $user->id,
                'domain_id' => $event->domain->id,
                'scan_type' => WordlistFilesFound::class
            ]);

            $notificationLimit = Carbon::now()->subMinutes(config('webmon.min_renotify_interval'));

            if (!$lastNotification->wasRecentlyCreated && $lastNotification->updated_at->greaterThan($notificationLimit)) {
                Log::info(sprintf('Will not renotify user % about %s on %s', $user->id, WordlistFilesFound::class, $event->domain->domain));
                continue;
            }

            $notification = new WordlistFilesFoundNotification($event);
            Notification::send($user, $notification);
            $lastNotification->updated_at = Carbon::now();
            $lastNotification->save();

        }
    }
}
