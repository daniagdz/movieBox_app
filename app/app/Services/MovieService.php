<?php

namespace App\Services;

use App\Models\Movie;

/*
Clase encargada de sincronizar la BD con la API de TMDB

La idea: gestiona las peticiones comprobando si ya existe data en la BD, sino se hace
        petición a la API
*/
class MovieService{

    public function __construct(private TmdbApiService $tmdb){}


    public function getOrImportMovie(int $tmdbId): Movie{
        
        //petición a BD
        $movie = Movie::where('tmdb_id', $tmdbId)->first();

        if($movie){
            return $movie;
        }

        /*
        Si no hay esa pelicula en Bd se hace petición a la API, a través del getter del
            tmdbapiservice
        */
        $data = $this->tmdb->getMovie($tmdbId);

        //https://laravel-news.com/firstornew-firstorcreate-firstor-updateorcreate
        /*
         En el caso de peticiones simultáneas, para evitar errores con 'create', se usa 'firstOrCreate',
            que evalua si ya existe el elemento buscado, y si no, lo crea
         */
        return(
            Movie::firstOrCreate(
                ['tmdb_id' => $data['id']],
                [
                    'title' => $data['title'],
                    'release_date' => ($data['release_date'] ?? null) ?: null,
                    'poster_path' => $data['poster_path'] ?? null,
                ]
            )
        );
    }
}