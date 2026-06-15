<?php

namespace App\Ai\Tools;

use App\Mail\TaskReminderMail;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SendEmail implements Tool
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
            Envía un email al usuario con información de sus tareas.
            Úsala DESPUÉS de haber obtenido la información necesaria de las tareas.
            Llámala UNA SOLA VEZ con el contenido del email ya redactado y listo para enviar.
        ';
    }

    /**
     * Execute the tool.
     */
   public function handle(Request $request): Stringable|string
    {
        try {
            Mail::to($this->user->email)
                ->send(new TaskReminderMail(
                    emailSubject: $request['subject'],
                    body: $request['body'],
                ));

            return "SUCCESS: Email enviado a {$this->user->email} con asunto '{$request['emailSubject']}'";
        } catch (\Exception $e) {
            Log::error('SendEmail tool error: ' . $e->getMessage());
            return "No se ha podido enviar el email: {$e->getMessage()}";
        }
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()
                ->description('Asunto del email'),
            'body' => $schema->string()
                ->description('Cuerpo del email con la información de la tarea'),
        ];
    }
}
