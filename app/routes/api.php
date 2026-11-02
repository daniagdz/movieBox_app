<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TmdbController;
use App\Http\Controllers\UserMovieController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/tmdb/search', [TmdbController::class, 'search']);
    Route::get('/tmdb/movies/{tmbdId}', [TmdbController::class, 'show'])->whereNumber('tmbdId');

    Route::patch('/movies/{tmdbId}/state', [UserMovieController::class, 'update'])->whereNumber('tmdbId');
    Route::get('/movies', [UserMovieController::class, 'index']);
    Route::get('/movies/{tmdbId}/state', [UserMovieController::class, 'show'])->whereNumber('tmdbId');
});
