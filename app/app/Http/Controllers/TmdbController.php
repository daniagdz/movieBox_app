<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use App\Services\TmdbApiService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;

//Clase encargada de conectar el front con TmdbApiService
class TmdbController extends Controller
{
    /*
    Constructor Promotion: 
        https://laravel.com/learn/php-fundamentals/modern-php
        https://roman-huliak.medium.com/constructor-property-promotion-in-php-cleaner-and-more-elegant-classes-12c7524be55a
    */
    public function __construct(
        private TmdbApiService $tmdb,
        private MovieService $movieService,
    ) {
        //$this->tmdb = $tmdb; -> la sintaxis del parentesis hace lo mismo que esta linea
    }

    public function search(Request $request): JsonResponse{
        $data = $request->validate([
            'query' => 'required|string|max:100',
            'page' => 'integer|min:1|max:500',
        ]);

        return(
            response()->json(
                $this->tmdb->searchMovie($data['query'], $data['page'] ?? 1)
            )
        );
    }


    //muestra datos generales de la peli, sin añadidos personales del user, extraido de la BD y/o API
    public function show(int $tmdbId): JsonResponse{
        try{
            return(
                response()->json([

                    'movie' => $this->tmdb->getMovie($tmdbId), //datos de la BD se añaden al objeto 'movie'
                    'users_rating' => $this->movieService->getUsersRating($tmdbId), 
                
                    /* Tendriamos un json tipo:
                    {
                        "movie": {
                            "id": 603,
                            "title": "Matrix",
                            "release_date": "1999-03-31"
                        },
                        "community_rating": {
                            "average": 7.5,
                            "count": 26
                        }
                    }
                    */
                ])
            );

        }catch(RequestException $e){
            if($e->response->status() === 404){
                return response()->json([
                    'message' => 'Película no encontrada.'
                ],404);
            }

            throw $e;
        }
    }

}