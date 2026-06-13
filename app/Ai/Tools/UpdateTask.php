<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTask implements Tool
{
    public function __construct(
        public User $user
    ){}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '
            Actualiza una tarea existente del usuario. Permite cambiar el nombre, la fecha límite y/o el estado de completado.
        ';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $task = $this->user->tasks()->findOrFail($request['id']);

        $name = $request['name'] ?? null;
        $dueDate = $request['due_date'] ?? null;
        $status = $request['status'] ?? null;

        if ($name !== null) $task->name = $name;
        if ($dueDate !== null) $task->due_date = $dueDate;
        if ($status !== null) $task->is_completed = $status;

        $task->save();

        return "Tarea '{$task->name}' actualizada correctamente.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('ID de la tarea a actualizar'),
            'name' => $schema->string()
                ->description('Nuevo nombre de la tarea')
                ->nullable(),
            'due_date' => $schema->string()
                ->description('Nueva fecha límite en formato YYYY-MM-DD')
                ->nullable(),
            'status' => $schema->boolean()
                ->description('true para marcar como completada, false para marcar como pendiente')
                ->nullable(),
        ];
    }
}
