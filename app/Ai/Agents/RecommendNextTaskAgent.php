<?php

namespace App\Ai\Agents;

use App\Ai\Tools\ListTasks;
use App\Models\User;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider([
    Lab::OpenRouter->value => 'google/gemini-2.5-flash',
])]
class RecommendNextTaskAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    public function __construct(private User $user) {}

    public function name(): string
    {
        return 'RecomendarTarea';
    }

    public function description(): string
    {
        return 'Analiza las tareas pendientes del usuario y recomienda UNA sola tarea concreta para hacer ahora mismo, con justificación breve.';
    }

    public function instructions(): Stringable|string
    {
        return 'Eres un experto en productividad. 
                La fecha de hoy es ' . now()->toDateString() . '.
                PRIMERO llama a ListTasks para obtener las tareas.
                DESPUÉS elige UNA sola tarea y justifica en 2 líneas por qué es la más urgente ahora mismo.
                Sé directo y concreto. Nada de listas, solo una recomendación clara.';
    }

    public function tools(): iterable
    {
        return [
            new ListTasks($this->user),
        ];
    }
}