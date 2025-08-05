<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\UserController;

Route::post('login', [UserController::class, 'login']);

Route::prefix('users')->middleware('jwt.cookie')->group(function () {
    Route::post('register', [UserController::class, 'store']);
    Route::get('trash', [UserController::class, 'trashed']);
    Route::post('restore/{user}', [UserController::class, 'restore'])->withTrashed();

    Route::get('profile/get', [UserController::class, 'profile']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('refresh', [UserController::class, 'refresh']);

    Route::patch('toggle-status/{user}', [UserController::class, 'toggleStatus']);
    Route::post('update-profile/{user}', [UserController::class, 'updateProfile']);
});

Route::middleware('jwt.cookie')->group(function () {
    Route::apiResource('users', UserController::class);
});
