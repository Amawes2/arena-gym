<?php

use App\Models\PendingRegistration;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::call(fn (): int => PendingRegistration::query()
    ->where('registration_expires_at', '<=', now())
    ->delete())->hourly();
