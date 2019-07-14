<?php

namespace App\Notifications;

use App\Events\WordlistFilesFound;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class WordlistFilesFoundNotification extends Notification implements ShouldQueue
{
    use Queueable;
    /**
     * @var WordlistFilesFound
     */
    private $event;

    /**
     * Create a new notification instance.
     *
     * @param WordlistFilesFound $event
     */
    public function __construct(WordlistFilesFound $event)
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

        $files = '';

        foreach ($this->event->filesList as $scanResult) {
            $files .= sprintf("- %s://%s%s (%s bytes)\n", $scanResult->protocol, $this->event->domain->domain, $scanResult->uri, $scanResult->fileSize);
        }

        return (new MailMessage)
            ->subject(sprintf('Found wordlist files at %s', $this->event->domain->domain))
            ->line(sprintf('Webmon found wordlist matches at %s.', $this->event->domain->domain))
            ->line($files);
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

}
