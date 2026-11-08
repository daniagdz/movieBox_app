<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TmdbController;
use App\Http\Controllers\UserMovieController;
use App\Http\Controllers\MovieListController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/tmdb/search', [TmdbController::class, 'search']);
    Route::get('/tmdb/movies/{tmdbId}', [TmdbController::class, 'show'])->whereNumber('tmdbId');

    Route::patch('/movies/{tmdbId}/state', [UserMovieController::class, 'update'])->whereNumber('tmdbId');
    Route::get('/movies', [UserMovieController::class, 'index']);
    Route::get('/movies/{tmdbId}/state', [UserMovieController::class, 'show'])->whereNumber('tmdbId');

    //Route::get('/lists', [MovieListController::class, 'index']);
    //Route::post('/lists', [MovieListController::class, 'store']);
    Route::apiResource('lists', MovieListController::class)->whereNumber('list');


    Route::post('/lists/{list}/movies', [MovieListController::class, 'addMovie'])
        ->whereNumber('list');
    Route::delete('/lists/{list}/movies/{tmdbId}', [MovieListController::class, 'removeMovie'])
        ->whereNumber(['list', 'tmdbId']);
});
