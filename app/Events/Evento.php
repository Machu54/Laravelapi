<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Evento implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public array $data;

    public string $canal;

    public function __construct(array $data, string $canal)
    {
        $this->data = $data;

        $this->canal = $canal;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel($this->canal)
        ];
    }

    public function broadcastAs(): string
    {
        return 'Mensaje';
    }
}
