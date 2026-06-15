<?php

use App\Ai\Agents\TaskAgent;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Enums\Lab;


Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    
    Route::view('/', 'welcome')->name('home');

    Route::get('prueba', function() {
        $question = (new TaskAgent)->prompt(
            'que tareas tengo?',
            provider: Lab::Groq,
            model: 'llama-3.3-70b-versatile'
        );
    
        dd($question);
        return (string) $question;
    });
});

require __DIR__.'/settings.php';
