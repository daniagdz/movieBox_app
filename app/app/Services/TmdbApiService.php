<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TmdbApiService{

    //https://laravel.com/framework/docs/http-client
    private function get(string $path, array $params = []): array{
        return(
            Http::baseUrl(config('services.tmdb.base_url')) //en '/app/config/services' tenemos la 'base_url'
                ->withToken(config('services.tmdb.token')) //agrega token de autenticacion a la cabecera
                ->acceptJson()
                ->timeout(10)
                ->retry(2, 200)
                ->get($path, $params + ['language' => 'es-ES'])
                ->throw()
                ->json()
        );
    }

    //https://laravel.com/framework/docs/12.x/cache
    public function searchMovie(string $query, int $page = 1): array{
        return (
            Cache::remember(
                'tmdb:search' . urlencode($query) . ":$page",
                now()->addHours(6),
                fn() => $this->get('/search/movie', [
                    'query' => $query,
                    'page' => $page,
                ])
            )
        );
    }

    //cogemos todos los datos de una peli a partir del ID -> USAR cuando clickemos en una peli concreta
    public function getMovie(int $tmdbId): array{
        return(
            Cache::remember(
                'tmdb:movie:$tmdbId',
                now()->addDay(),
                fn() => $this->get("/movie/$tmdbId", [
                    'append_to_response' => 'credits', //añadimos los "creditos" como extra 
                ])
            )
        );
    }



}
