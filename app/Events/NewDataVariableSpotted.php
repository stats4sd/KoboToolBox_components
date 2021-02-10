<?php

namespace App\Events;

use App\Models\DataMap;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewDataVariableSpotted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $variable;
    public $datamap;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Array $variable, DataMap $datamap)
    {
        //
        $this->variable = $variable;
        $this->datamap = $datamap;

    }

    
}
