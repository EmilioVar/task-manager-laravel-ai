<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreateTask;
use App\Ai\Tools\DeleteTask;
use App\Ai\Tools\ListTasks;
use App\Ai\Tools\UpdateTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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
    Lab::Gemini->value => 'gemini-3.1-flash-lite',
    Lab::Groq->value => 'llama-3.3-70b-versatile',
    //Lab::Groq->value => 'llama-3.1-8b-instant',
    //Lab::OpenRouter->value => 'google/gemini-2.5-flash-lite',
    //Lab::OpenRouter->value => 'google/gemma-4-31b-it:free',
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
            Además, tienes la capacidad de priorizar tareas o recomendar proximas tareas.
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
            // CRUD
            new CreateTask(Auth::user()),
            new ListTasks(Auth::user()),
            new UpdateTask(Auth::user()),
            new DeleteTask(Auth::user()),
            // SUBAGENT
            new PrioritizerAgent(Auth::user()),
            new RecommendNextTaskAgent(Auth::user())
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'list_items' => $schema->array()->nullable()
            /* 'list_items' => $schema->array()
            ->items(
                $schema->object(fn ($schema) => [
                    'name' => $schema->string()->required(),
                    'due_Date' => $schema->string()->required(),
                ])
            )
            ->required(), */
        ];
    }
}
