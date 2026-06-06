<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatriculaMasivaFinalizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $usuarioId,
        public string $curso,
        public int $total,
        public int $enviados,
        public int $fallidos
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("usuario.{$this->usuarioId}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'matricula.finalizada';
    }
}
