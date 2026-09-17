<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// research.md §1 : pas de worker de queue long-running garanti sur le
// mutualisé Camoo. `schedule:run` (déclenché chaque minute par le cron
// cPanel) traite le lot de jobs en attente via un `queue:work` borné,
// au lieu d'un `queue:work` daemon (T015).
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
