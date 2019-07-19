<?php

namespace App\Events;

use App\Orm\Domain;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewSubdomainFound
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    /**
     * @var Domain
     */
    public $domain;

    /**
     * @var string The full hostname of the subdomain of `domain`
     */
    public $hostname;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Domain $domain, $hostname)
    {
        $this->domain = $domain;
        $this->hostname = $hostname;
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
