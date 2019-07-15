<?php

namespace App\Events;

use App\Dto\UriScanResult;
use App\Orm\Domain;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WordlistFilesFound
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    /**
     * @var Domain
     */
    public $domain;
    /**
     * @var UriScanResult[]
     */
    public $filesList;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Domain $domain,array $filesList)
    {
        $this->domain = $domain;
        $this->filesList = $filesList;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
