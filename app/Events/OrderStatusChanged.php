<?php

namespace App\Events;

use App\Models\Orden;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orden;
    public $restauranteSlug;
    public $action;

    /**
     * Create a new event instance.
     *
     * @param Orden $orden
     * @param string $restauranteSlug
     * @param string $action (activar, entregar, cancelar, finalizar, etc.)
     */
    public function __construct(Orden $orden, string $restauranteSlug, string $action = 'update')
    {
        $this->orden = $orden;
        $this->restauranteSlug = $restauranteSlug;
        $this->action = $action;

        Log::info('📡 OrderStatusChanged event created', [
            'orden_id' => $orden->id,
            'restaurante' => $restauranteSlug,
            'action' => $action,
            'broadcast_driver' => config('broadcasting.default'),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Canal específico por restaurante para aislar notificaciones
        $channel = 'restaurante.' . $this->restauranteSlug;

        Log::info('📺 Broadcasting on channel', [
            'channel' => $channel,
            'event' => 'orden.cambio',
        ]);

        return [
            new Channel($channel),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'orden.cambio';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'orden_id' => $this->orden->id,
            'mesa_id' => $this->orden->mesa_id,
            'estado' => $this->orden->estado,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
