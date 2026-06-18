<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatriculaMasivaProgreso implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $usuarioId,
        public string $jobId,
        public string $curso,
        public int    $procesados,
        public int    $total,
        public float  $porcentaje,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("usuario.{$this->usuarioId}")];
    }

    public function broadcastAs(): string
    {
        return 'matricula.progreso';
    }
}
