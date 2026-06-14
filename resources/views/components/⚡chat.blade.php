<?php
use Livewire\Component;
use App\Ai\Agents\TaskAgent;
use App\Models\Conversation;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use Laravel\Ai\Streaming\Events\TextDelta;
use App\Models\User;

new class extends Component {
    public string $prompt = '';
    public array $messages = [];
    public string $conversationId = '';
    public string $model = '';
    public string $provider = '';

    public function user(): User
    {
        return Auth::user();
    }

    public function selectConversation(string $id): void
    {
        $this->conversationId = $id;
        $conv = $this->user()->conversations()->find($id);

        $this->messages = $conv->messages
            ->map(
                fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'meta' => null,
                    'tools' => [],
                ],
            )
            ->toArray();
    }

    public function newConversation(): void
    {
        $this->conversationId = '';
        $this->messages = [];
        $this->prompt = '';
    }

    public function submitPrompt(): void
    {
        $userMessage = $this->prompt;
        $this->prompt = '';

        $this->messages[] = ['role' => 'user', 'content' => $userMessage, 'meta' => null, 'tools' => []];
        $this->messages[] = ['role' => 'assistant', 'content' => '', 'meta' => null, 'tools' => []];
        $lastIndex = array_key_last($this->messages);

        $user = $this->user();
        $agent = new TaskAgent();

        if (!$this->conversationId) {
            $response = $agent->forUser($user)->stream($userMessage);
        } else {
            $response = $agent->continue($this->conversationId, as: $user)->stream($userMessage);
        }

        $response->then(function (StreamedAgentResponse $response) use ($lastIndex) {
            $this->conversationId = $response->conversationId;

            $this->messages[$lastIndex]['meta'] = [
                'provider' => $response->meta->provider,
                'model' => $response->meta->model,
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'cache_read' => $response->usage->cacheReadInputTokens,
                'reasoning' => $response->usage->reasoningTokens,
                'steps' => $response->steps->count(),
            ];
        });

        foreach ($response as $event) {
            if ($event instanceof StreamStart) {
                $this->model = $event->model;
                $this->provider = $event->provider;
            }
            if ($event instanceof TextStart) {

                $this->stream(to: 'answer', content: '');
            }
            if ($event instanceof ToolCall) {
                $this->messages[$lastIndex]['tools'][] = [
                    'id' => $event->toolCall->id, // ← guardamos el id
                    'name' => $event->toolCall->name,
                    'status' => 'running',
                ];

                $this->stream(to: 'thinking', content: '⚙ ' . $event->toolCall->name . '...', replace: true);
            }

            if ($event instanceof ToolResult) {
                foreach ($this->messages[$lastIndex]['tools'] as $i => $tool) {
                    if ($tool['status'] === 'running') {
                        $this->messages[$lastIndex]['tools'][$i]['status'] = 'done';
                    }
                }

                $this->stream(to: 'thinking', content: '', replace: true);
            }
            if ($event instanceof TextDelta) {
                $this->messages[$lastIndex]['content'] .= $event->delta;
                
                $this->stream(to: 'answer', content: $event->delta);
            }
        }
    }
};
?>

<div class="flex h-screen bg-gray-950 text-gray-100 font-mono overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-64 shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col">
        <div class="p-4 border-b border-gray-800">
            <div class="text-xs uppercase tracking-widest text-indigo-400 mb-1">Laravel AI</div>
            <div class="text-lg font-bold text-white">Task Agent</div>
            @if ($model)
                <div class="text-xs text-gray-500 mt-1">{{ $provider }} · {{ $model }}</div>
            @endif
        </div>

        <div class="p-3">
            <button wire:click="newConversation"
                class="w-full text-left text-sm px-3 py-2 rounded-lg border border-dashed border-gray-700 text-gray-400 hover:border-indigo-500 hover:text-indigo-400 transition-colors">
                + Nueva conversación
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-1">
            @foreach ($this->user()->conversations as $conv)
                <button wire:click="selectConversation('{{ $conv->id }}')"
                    class="w-full text-left text-sm px-3 py-2 rounded-lg transition-colors truncate
                        {{ $conversationId === $conv->id
                            ? 'bg-indigo-600 text-white'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    {{ $conv->title ?? 'Conversación' }}
                </button>
            @endforeach
        </nav>

        <div class="p-4 border-t border-gray-800 text-xs text-gray-600">
            {{ $this->user()->name }}
        </div>
    </aside>

    {{-- Chat --}}
    <main class="flex-1 flex flex-col min-w-0">

        {{-- Messages --}}
        <div x-data x-init="const el = $el;
        const observer = new MutationObserver(() => el.scrollTop = el.scrollHeight);
        observer.observe(el, { childList: true, subtree: true, characterData: true });
        el.scrollTop = el.scrollHeight;" class="flex-1 overflow-y-auto px-6 py-6 flex flex-col gap-6">

            @if (empty($messages))
                <div class="h-full flex flex-col items-center justify-center text-center text-gray-600">
                    <div class="text-4xl mb-4">⚡</div>
                    <div class="text-sm">Escribe algo para empezar.<br>Puedo crear, listar, actualizar y eliminar tus
                        tareas.</div>
                </div>
            @endif

            @foreach ($messages as $i => $message)
                @if ($message['role'] === 'user')
                    <div class="flex justify-end">
                        <div
                            class="bg-indigo-600 text-white text-sm leading-relaxed px-4 py-2.5 rounded-[18px] rounded-br-sm max-w-[75%]">
                            {{ $message['content'] }}
                        </div>
                    </div>
                @else
                    <div class="flex gap-3 items-start">
                        <div
                            class="w-7 h-7 rounded-full bg-indigo-900 flex items-center justify-center shrink-0 mt-0.5 text-indigo-400 text-xs">
                            AI
                        </div>
                        <div class="flex flex-col gap-2 max-w-[75%]">

                            {{-- Tools utilizadas --}}
                            @if (!empty($message['tools']))
                                <div class="flex flex-col gap-1">
                                    @foreach ($message['tools'] as $tool)
                                        <div
                                            class="flex items-center gap-2 text-[11px] font-mono px-3 py-1.5 rounded-lg
                                            {{ $tool['status'] === 'done'
                                                ? 'bg-gray-900 border border-gray-800 text-gray-500'
                                                : 'bg-amber-950 border border-amber-800 text-amber-400' }}">
                                            @if ($tool['status'] === 'done')
                                                <span class="text-green-500">✓</span>
                                            @else
                                                <span class="animate-pulse">⚙</span>
                                            @endif
                                            {{ $tool['name'] }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Respuesta --}}
                            @if ($message['content'] !== '')
                                <div @if ($loop->last) wire:stream="answer" @endif
                                    class="text-sm leading-relaxed text-gray-100 px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-sm rounded-tr-[18px] rounded-br-[18px] rounded-bl-[18px] prose prose-invert prose-sm max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($message['content']) !!}
                                </div>
                            @endif

                            {{-- Meta / tokens --}}
                            @if (!empty($message['meta']))
                                @php $m = $message['meta']; @endphp
                                <div class="flex flex-wrap gap-x-3 gap-y-1 px-1 text-[10px] text-gray-600 font-mono">
                                    <span class="text-indigo-500/70">{{ $m['provider'] }} · {{ $m['model'] }}</span>
                                    <span title="Prompt tokens">↑ {{ number_format($m['prompt_tokens']) }}</span>
                                    <span title="Completion tokens">↓
                                        {{ number_format($m['completion_tokens']) }}</span>
                                    @if ($m['cache_read'] > 0)
                                        <span class="text-green-700" title="Cache read tokens">♻
                                            {{ number_format($m['cache_read']) }}</span>
                                    @endif
                                    @if ($m['reasoning'] > 0)
                                        <span class="text-yellow-700" title="Reasoning tokens">🧠
                                            {{ number_format($m['reasoning']) }}</span>
                                    @endif
                                    @if ($m['steps'] > 1)
                                        <span class="text-gray-600">{{ $m['steps'] }} steps</span>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Thinking indicator --}}
            <div wire:loading wire:target="submitPrompt" class="flex gap-3 items-start">
                <div
                    class="w-7 h-7 rounded-full bg-indigo-900 flex items-center justify-center shrink-0 mt-0.5 text-indigo-400 text-xs">
                    AI
                </div>
                <div wire:stream="thinking" class="text-sm text-gray-500 px-4 py-2.5 animate-pulse">
                    pensando...
                </div>
            </div>

        </div>

        {{-- Input --}}
        <div class="border-t border-gray-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <input wire:model="prompt" wire:keydown.enter="submitPrompt" type="text"
                    placeholder="Escribe una tarea, pregunta o acción..." autocomplete="off"
                    class="flex-1 bg-gray-900 border border-gray-700 rounded-full px-5 py-3 text-sm text-gray-100 placeholder-gray-600 focus:outline-none focus:border-indigo-500 transition-colors">
                <button wire:click="submitPrompt" wire:loading.attr="disabled" wire:target="submitPrompt"
                    class="w-11 h-11 rounded-full bg-indigo-600 hover:bg-indigo-500 flex items-center justify-center shrink-0 transition-colors disabled:opacity-50">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
            <div class="mt-2 text-xs text-gray-700 text-center">Powered by Laravel AI</div>
        </div>

    </main>
</div>
