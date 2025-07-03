<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('filament.app.pages.monitoring-kwh');
})->middleware(['auth', 'signed', 'trustProxy'])->name('verification.verify');
