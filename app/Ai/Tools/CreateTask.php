<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTask implements Tool
{
    public function __construct(
        private User $user
    ){}
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '
            Crea una nueva tarea para el usuario autenticado.
            Usa esta tool cuando el usuario quiera añadir, crear o apuntar una tarea.
            El nombre es el campo name, y siempre será obligatorio.
            Pueden seleccionar una fecha de expiración con due_date, pero es opcional.
        ';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $name = $request['name'];
        $dueDate = $request['due_date'] ?? null;

        $create =  $this->user
            ->tasks()
            ->create([
                'name' => $name,
                'due_date' => $dueDate,
            ]);

        return (string) $create;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'due_date' => $schema->string()->nullable()
        ];
    }
}
