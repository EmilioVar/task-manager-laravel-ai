<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DeleteTask implements Tool
{
    public function __construct(
        private User $user
    ) {}

    public function description(): Stringable|string
    {
        return '
            Elimina una tarea existente del usuario por su ID.
            Necesitamos un nombre o parte del nombre para el borrado.
        ';
    }

    public function handle(Request $request): Stringable|string
    {
        $task = $this->user->tasks()->findOrFail($request['id']);
        $name = $task->name;
        $task->delete();

        return "Tarea '{$name}' eliminada correctamente.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('ID de la tarea a eliminar'),
        ];
    }
    }