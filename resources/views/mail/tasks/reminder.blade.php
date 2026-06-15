<x-mail::message>
# Notificación de tareas

{!! \Illuminate\Support\Str::markdown($body) !!}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
