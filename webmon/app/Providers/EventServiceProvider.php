<?php

namespace App\Providers;

use App\Events\WordlistFilesFound;
use App\Events\PublicGitFolderFound;
use App\Events\UnresolvableDomain;
use App\Listeners\DeleteUnresolvableDomain;
use App\Listeners\LogSentNotification;
use App\Listeners\NotifyOfPublicGitFolder;
use App\Listeners\NotifyOfWordlistFiles;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        PublicGitFolderFound::class => [
            NotifyOfPublicGitFolder::class
        ],
        WordlistFilesFound::class => [
            NotifyOfWordlistFiles::class
        ],
        NotificationSent::class => [
            LogSentNotification::class
        ],
        UnresolvableDomain::class => [
            DeleteUnresolvableDomain::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
