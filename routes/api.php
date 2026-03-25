<?php

use App\Http\Controllers\Admin;
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

Route::middleware(['auth:sanctum', 'not_banned'])->group(function () {

    // Auth
    Route::delete('auth/logout', [MagicLinkController::class, 'destroy']);

    // Current user
    Route::get('users/me', [UserController::class, 'show']);
    Route::put('users/me', [UserController::class, 'update']);
    Route::delete('users/me', [UserController::class, 'destroy']);
    Route::get('users/me/recipes', [RecipeController::class, 'myRecipes']);

    // Recipes (CRUD + visibility)
    Route::post('recipes', [RecipeController::class, 'store']);
    Route::put('recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy']);
    Route::patch('recipes/{recipe}/visibility', [RecipeController::class, 'updateVisibility']);

    // Recipe Equipment
    Route::post('recipes/{recipe}/equipment', [RecipeEquipmentController::class, 'store']);
    Route::put('recipes/{recipe}/equipment/{recipeEquipment}', [RecipeEquipmentController::class, 'update']);
    Route::delete('recipes/{recipe}/equipment/{recipeEquipment}', [RecipeEquipmentController::class, 'destroy']);

    // Likes
    Route::post('recipes/{recipe}/likes', [LikeController::class, 'store']);
    Route::delete('recipes/{recipe}/likes', [LikeController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'not_banned', 'admin'])->prefix('admin')->group(function () {

    // Brew Methods (admin manage)
    Route::apiResource('brew-methods', BrewMethodController::class)->only(['store', 'update', 'destroy']);

    // Equipment (admin manage global equipment)
    Route::apiResource('equipment', EquipmentController::class)->only(['store', 'update', 'destroy']);

    // User management
    Route::get('users', [Admin\UserController::class, 'index']);
    Route::post('users/{user}/ban', [Admin\UserController::class, 'ban']);
    Route::delete('users/{user}/ban', [Admin\UserController::class, 'unban']);

    // Magic links status
    Route::get('magic-links', [Admin\MagicLinkController::class, 'index']);
});
