<?php

namespace App\Policies;

use App\Models\MovieList;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MovieListPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MovieList $movieList): bool
    {
        return $user->id === $movieList->user_id;
    }

    /**
     * Determine whether the user can create models.
     * 
     * Haven't changed yet
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MovieList $movieList): bool
    {
        return $user->id === $movieList->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MovieList $movieList): bool
    {
        return $user->id === $movieList->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     * 
     * Haven't changed yet -> maybe delete this (?)
     */
    public function restore(User $user, MovieList $movieList): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MovieList $movieList): bool
    {
        return false;
    }
}
