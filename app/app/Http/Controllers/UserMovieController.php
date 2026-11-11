<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMovieStateRequest;
use App\Services\MovieService;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\MovieResource;


class UserMovieController extends Controller
{

    private function emptyState(): array
    {
        return [
            'watched' => false,
            'liked' => false,
            'watchlisted' => false,
            'rating' => null,
            'review' => null,
        ];
    }

    private function stateFromPivot($pivot): array
    {
        return [
            'watched' => (bool) $pivot->watched,
            'liked' => (bool) $pivot->liked,
            'watchlisted' => (bool) $pivot->watchlisted,
            'rating' => $pivot->rating,
            'review' => $pivot->review,
        ];
    }


    public function update(UpdateMovieStateRequest $request, int $tmdbId, MovieService $movies)

    {
        $data = $request->validated();

        $movie = $movies->getOrImportMovie($tmdbId);

        if (!empty($data['rating'])) {
            $data['watched'] = true;
        }

        $user = $request->user();

        //https://olegkreimerpublished.medium.com/laravel-sync-relationship-method-a-word-of-caution-300eece787a6
        //https://medium.com/@rajvir.ahmed.shuvo/understanding-sync-attach-and-detach-in-laravel-managing-relationships-with-eloquent-394a7cf7fabd
        //https://laravel.com/framework/docs/12.x/eloquent-relationships
        /*
        'syncWithoutDetaching()': se usa en relaciones N-M para actualizar valores en un registro sin eliminar los ya
                                existentes
        
        Aqui le estamos asignando a un user los datos de una peli concreta
        Actualizamos solo los datos enviados
        */
        $user->movies()->syncWithoutDetaching([$movie->id => $data]);

        /*
        el 'pivot' al final de la linea permite acceder a la tabla pivote/intermedia de la bd y 
            coger el primer resultado (objeto en este caso -> tipo Movie) que coincida con la condicion
        */
        $pivot = $user->movies()->where('movie_id', $movie->id)->first()->pivot;

        $isWatched = (bool) $pivot->watched;
        $isLiked = (bool) $pivot->liked;
        $isWatchlisted = (bool) $pivot->watchlisted;
        $hasRating = $pivot->rating !== null;
        $hasReview = ! blank($pivot->review);

        //si no hya un estado activo se elimina de lista
        if (! $isWatched && ! $isLiked && ! $isWatchlisted && ! $hasRating && ! $hasReview) {
            $user->movies()->detach($movie->id);

            return response()->json(['movie' => new MovieResource($movie), 'state' => $this->emptyState()]);
        }

        return response()->json([
            'movie' => new MovieResource($movie),
            'state' => [
                'watched' => $isWatched,
                'liked' => $isLiked,
                'watchlisted' => $isWatchlisted,
                'rating' => $pivot->rating,
                'review' => $pivot->review,
            ],
        ]);
    }


    /*
    con '$request->user()' accedemos directamente al user que hace la peticion gracias a
        su token. Evitaríamos usar el tipico User::findId($id)
    */
    public function show(Request $request, int $tmdbId): JsonResponse
    {
        $movie = Movie::where('tmdb_id', $tmdbId)->first();

        $userMovie = $movie
            ? $request->user()->movies()->where('movie_id', $movie->id)->first()
            : null;

        return (
            response()->json([
                'state' => $userMovie
                    ? $this->stateFromPivot($userMovie->pivot) : $this->emptyState()
            ])
        );
    }


    public function index(Request $request): JsonResponse
    {
        //en la url se manda uno de estos estados para que se puedan mostrar filtrado -> wherePivot(...)
        $data = $request->validate([
            'state' => 'required|in:watched,liked,watchlisted,rated',
        ]);

        $query = $request->user()->movies();

        if ($data['state'] === 'rated') {
            $query->wherePivotNotNull('rating');
        } else {
            $query->wherePivot($data['state'], true);
        }

        /*
        return(
            response()->json(
                $query->orderByPivot('updated_at', 'desc')->paginate(20)
            )
        );
        */
        return MovieResource::collection(
            $query->orderByPivot('updated_at', 'desc')->paginate(20)
        )->response();
    }
}
