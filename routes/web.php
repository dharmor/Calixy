<?php

use Illuminate\Support\Facades\Route;
use UnifiedAppointments\Laravel\Http\Controllers\AboutController;

Route::get('/about', AboutController::class)->name('about');
