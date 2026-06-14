<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTasks implements Tool
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
            Lista las tareas del usuario.
            Filtra por estado o fecha si el usuario lo indica.
            El estado significa que una tarea esté completa o no, es decir, que en la base de datos aparezca 0 (incompleta) o 1 (completa).
        ';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $status = $request['status'] ?? null;
        $dueDate = $request['due_date'] ?? null;

        $tasks = $this->user->tasks()
            ->when($status !== null, fn($q) => $q->where('is_completed', $status))
            ->when($dueDate !== null, fn($q) => $q->whereDate('due_date', $dueDate))
            ->get();

        if ($tasks->isEmpty()) {
            return 'No hay tareas que coincidan con los filtros indicados.';
        }

        return (string) $tasks;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->boolean()
                ->description('true para tareas completadas, false para tareas pendientes/incompletas. null para mostrar todas.')
                ->nullable(),
            'due_date' => $schema->string()
                ->description('Filtra por fecha límite en formato YYYY-MM-DD o día de la semana')
                ->nullable()
        ];
    }
}
