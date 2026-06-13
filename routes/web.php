<?php

use App\Ai\Agents\TaskAgent;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Enums\Lab;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::get('prueba', function() {
    $question = (new TaskAgent)->prompt(
        'borra la tarea ir al cine',
        provider: Lab::Gemini,
        model: 'gemini-3.1-flash-lite'
    );

    dd($question);
    return (string) $question;
});
require __DIR__.'/settings.php';
