<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObjectUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $action;
    public $tenant;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($action, $data)
    {
        $this->action = $action;
        $this->data = $data;
        $this->tenant = tenant('id');
        
    }

    // public function broadcastWith(): array
    // {
    //     return ['d' => $this->data];
    // }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $channels = [];
        
        // foreach (\App\Models\Tenant::get() as $tenant) {
        //     $channels[] = new PrivateChannel('tenant.'.$tenant->id);
        // }
        $channels[] = new PrivateChannel('tenant.'.tenant('id'));
        $channels[] = new PrivateChannel('object-updated');
        return $channels;
        // return [
        //     new PrivateChannel('tenant.seeds'),
        //     new PrivateChannel('object-updated')
            
        // ];
    }
}
