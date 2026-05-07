<?php

use Illuminate\Support\Facades\Route;
use UnifiedAppointments\Laravel\Http\Controllers\AppointmentController;
use UnifiedAppointments\Laravel\Http\Controllers\AvailabilityController;
use UnifiedAppointments\Laravel\Http\Controllers\AvailabilityExceptionController;
use UnifiedAppointments\Laravel\Http\Controllers\AvailabilityRuleController;
use UnifiedAppointments\Laravel\Http\Controllers\ServiceController;
use UnifiedAppointments\Laravel\Http\Controllers\WaitlistController;

Route::get('/slots', [AvailabilityController::class, 'index'])->name('slots.index');
Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
Route::post('/availability-rules', [AvailabilityRuleController::class, 'store'])->name('availability-rules.store');
Route::post('/availability-exceptions', [AvailabilityExceptionController::class, 'store'])->name('availability-exceptions.store');
Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
Route::post('/waitlist', [WaitlistController::class, 'store'])->name('waitlist.store');
