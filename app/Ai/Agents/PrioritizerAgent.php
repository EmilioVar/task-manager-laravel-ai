<?php

namespace App\Ai\Agents;

use App\Ai\Tools\ListTasks;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider([
    Lab::OpenRouter => 'gemini-3.1-flash-lite'
])]
class PrioritizerAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    public function name(): string
    {
        return 'PriorizarTareas';
    }

    public function description(): string
    {
        return '
            Prioriza las tareas del usuario por urgencia y fecha límite.
            Úsame cuando el usuario quiera saber qué tarea debería hacer primero.
        ';
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'Eres un experto en productividad. 
                PRIMERO llama a ListTasks para obtener las tareas del usuario.
                DESPUÉS analiza y devuelve un orden de prioridad justificado por urgencia y fecha límite.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new ListTasks(Auth::user())
        ];
    }
}
