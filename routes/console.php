<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Procesar cola de trabajos cada minuto (para broadcasting de Pusher)
Schedule::command('queue:work --stop-when-empty')->everyMinute();
