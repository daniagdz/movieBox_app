<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddMovieToListRequest;
use App\Models\MovieList;
use App\Models\Movie;
use App\Services\MovieService;
use App\Http\Requests\StoreMovieListRequest;
use App\Http\Requests\UpdateMovieListRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate; //https://laravel.com/framework/docs/authorization
use Illuminate\Support\Facades\DB;


class MovieListController extends Controller
{

    //https://laravel.com/framework/docs/eloquent-relationships
    /*
    como accede directo con ID user solo muestra listas de ese user, evitando también
    que se muestren listas de otros usuarios
    */
    public function index(Request $request): JsonResponse
    {
        $lists = $request->user()
            ->movieLists()
            ->withCount('movies') //solo cuenta listas de pelis -> evita
            ->latest()
            ->get();

        return response()->json($lists);
    }

    //validar datos y crear una lista
    /*
    '$request->validated()': validamos los parametros de la peticion que coincidan con las 'rule'
                            definidas en el Resquest correspondiente. Los demás params son ignorados/descartados
                            
    */
    public function store(StoreMovieListRequest $request): JsonResponse
    {
        $list = $request->user()->movieLists()->create($request->validated());
        $list->loadCount('movies');

        return response()->json($list, 201);
    }

    /*
    Los parametros 'list' deben de coincidir con el nombre dado en el routing para
        poder generar el enlace entre ruta y método
    */
    public function show(MovieList $list): JsonResponse
    {
        Gate::authorize('view', $list);

        //https://laravel.com/framework/docs/10.x/eloquent-relationships#eager-loading
        // revisar link -> https://oussama-mater.tech/laravel-eager-loading-is-bad/
        /*
        'Eager Loading': traemos todos los datos necesarios desde la BD de "una sola vez"
        Aqui cargamos los datos de las 'movies' y contamos cuantas hay
        */
        $list->load('movies')->loadCount('movies');

        return response()->json($list);
    }


    public function update(UpdateMovieListRequest $request, MovieList $list): JsonResponse
    {
        $list->update($request->validated());
        $list->loadCount('movies');

        return response()->json($list);
    }


    public function destroy(MovieList $list): JsonResponse
    {
        Gate::authorize('delete', $list);
        $list->delete();
        return response()->json(null, 204);
    }

    public function addMovie(AddMovieToListRequest $request, MovieList $list, MovieService $movieService): JsonResponse
    {

        $data = $request->validated();

        $movie = $movieService->getOrImportMovie($data['tmdb_id']);

        $existInList = $list->movies()->where('movies.id', $movie->id)->exists();

        if (!$existInList) {
            $lastPosi = DB::table('movie_list_items')
                ->where('movie_list_id', $list->id)
                ->max('position') ?? 0;

            $list->movies()->attach($movie->id, ['position' => $lastPosi + 1]);
        }

        $list->load('movies')->loadCount('movies');

        return response()->json($list);
    }


    public function removeMovie(MovieList $list, int $tmdbId): JsonResponse
    {
        Gate::authorize('update', $list);

        $movie = Movie::where('tmdb_id', $tmdbId)->first();

        if ($movie) {
            $list->movies()->detach($movie->id);
        }
        return response()->json(null, 204);
    }
}
