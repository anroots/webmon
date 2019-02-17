<?php

namespace App\Notifications;

use App\Events\PublicGitFolderFound;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PublicGitFolderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    /**
     * @var PublicGitFolderFound
     */
    private $event;

    /**
     * Create a new notification instance.
     *
     * @param PublicGitFolderFound $event
     */
    public function __construct(PublicGitFolderFound $event)
    {

        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        return (new MailMessage)
            ->subject(sprintf('Public .git folder at %s',$this->event->domain->domain))
            ->line(sprintf('Webmon found a public .git folder at %s.', $this->event->domain->domain))
            ->action('View in browser', $this->composeUrl());
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /**
     * @return string
     */
    private function composeUrl(): string
    {
        $protocol = $this->event->scanDetails['httpGitHead'] ? 'http' : 'https';
        return sprintf('%s://%s/.git/', $protocol, $this->event->domain->domain);
    }
}
