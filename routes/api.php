<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BrewMethodController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeEquipmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

// Auth — Magic Link
Route::prefix('auth')->group(function () {
    Route::post('magic-link', [MagicLinkController::class, 'store']);
    Route::get('magic-link/{token}', [MagicLinkController::class, 'show']);
});

// Brew Methods
Route::apiResource('brew-methods', BrewMethodController::class)->only(['index', 'show']);

// Equipment (global only, publicly visible)
Route::apiResource('equipment', EquipmentController::class)->only(['index', 'show']);

// Recipes (public feed + public detail)
Route::get('recipes', [RecipeController::class, 'index']);
Route::get('recipes/{recipe}', [RecipeController::class, 'show']);
Route::get('recipes/{recipe}/likes', [LikeController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::delete('auth/logout', [MagicLinkController::class, 'destroy']);

    // Current user
    Route::get('users/me', [UserController::class, 'show']);
    Route::put('users/me', [UserController::class, 'update']);
    Route::delete('users/me', [UserController::class, 'destroy']);
    Route::get('users/me/recipes', [RecipeController::class, 'myRecipes']);
    Route::get('users/me/equipment', [EquipmentController::class, 'myEquipment']);

    // Brew Methods (management)
    Route::apiResource('brew-methods', BrewMethodController::class)->only(['store', 'update', 'destroy']);

    // Equipment (management)
    Route::post('equipment', [EquipmentController::class, 'store']);
    Route::put('equipment/{equipment}', [EquipmentController::class, 'update']);
    Route::delete('equipment/{equipment}', [EquipmentController::class, 'destroy']);

    // Recipes (CRUD + visibility)
    Route::post('recipes', [RecipeController::class, 'store']);
    Route::put('recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy']);
    Route::patch('recipes/{recipe}/visibility', [RecipeController::class, 'updateVisibility']);

    // Recipe Equipment
    Route::post('recipes/{recipe}/equipment', [RecipeEquipmentController::class, 'store']);
    Route::put('recipes/{recipe}/equipment/{equipment}', [RecipeEquipmentController::class, 'update']);
    Route::delete('recipes/{recipe}/equipment/{equipment}', [RecipeEquipmentController::class, 'destroy']);

    // Likes
    Route::post('recipes/{recipe}/likes', [LikeController::class, 'store']);
    Route::delete('recipes/{recipe}/likes', [LikeController::class, 'destroy']);
});
