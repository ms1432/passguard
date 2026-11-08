<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PasswordController;
use Illuminate\Support\Facades\Route;

Route::post('/password/check', [PasswordController::class, 'check']);
Route::get('/password/generate', [PasswordController::class, 'generate']);
