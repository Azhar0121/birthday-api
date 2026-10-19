<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PublicGreetingController;
use App\Http\Controllers\Api\V1\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // --- Authentication (Public) ---
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Authenticated Auth Routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // --- Templates & Categories (Public) ---
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::get('/templates/{id}', [TemplateController::class, 'show']);
    Route::get('/categories', [TemplateController::class, 'categories']);

    // --- Public Greeting Endpoint (No Login Required) ---
    Route::get('/g/{slug}', [PublicGreetingController::class, 'show']);

    // --- Protected Project & Media Management Routes ---
    Route::middleware('auth:sanctum')->group(function () {

        // Projects CRUD
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{id}', [ProjectController::class, 'show']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

        // Project Status & Publishing
        Route::post('/projects/{id}/publish', [ProjectController::class, 'publish']);
        Route::post('/projects/{id}/unpublish', [ProjectController::class, 'unpublish']);

        // Media Upload & Slot Management
        Route::post('/projects/{id}/media', [MediaController::class, 'upload']);
        Route::delete('/projects/{id}/media/{slot_key}', [MediaController::class, 'destroy']);
    });
});
