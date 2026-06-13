<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreateTask;
use App\Ai\Tools\DeleteTask;
use App\Ai\Tools\ListTasks;
use App\Ai\Tools\UpdateTask;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider([
    Lab::Gemini->value => 'gemini-2.5-pro',
    Lab::OpenRouter->value => 'google/gemma-4-31b-it:free',
    Lab::Groq->value => 'llama-3.1-8b-instant',
    //Lab::OpenRouter->value => 'openai/gpt-4o-mini',
])]
class TaskAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return '
            Eres un asistente de gestión de tareas. 
            Ayudas al usuario a crear, listar y actualizar sus tareas. 
            Responde siempre en el idioma del usuario.
            La fecha de hoy es ' . now()->toDateString() . '.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new CreateTask(Auth::user()),
            new ListTasks(Auth::user()),
            new UpdateTask(Auth::user()),
            new DeleteTask(Auth::user())
        ];
    }
}
