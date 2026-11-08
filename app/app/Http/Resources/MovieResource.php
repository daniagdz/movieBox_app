<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


//https://notasweb.me/entrada/laravel-que-son-los-resources/
//https://laravel.com/framework/docs/eloquent-resources#introduction

/*
Esta clase nos permite seleccionar qué campos del json de un Model 
    podemos mostrar en el front
Al hacer simplemente 'response()->json($movie)' devolveriamos todas las columnas de
    ese modelo. ACTÚA COMO "filtro" para el getter
*/

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    //{
    //  return parent::toArray($request);
    //}


    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'title' => $this->title,
            'release_date' => $this->release_date?->toDateToString(),
            'poster_path' => $this->poster_path,
            'poster_url' => $this->poster_path ?
                'https://image.tmdb.org/t/p/w500' . $this->poster_path
                : null,
            'state' => $this->whenPivotLoaded('movie_user', fn() => [
                'watched' => (bool) $this->pivot->watched,
                'liked' => (bool) $this->pivot->liked,
                'watchlisted' => (bool) $this->pivot->watchlisted,
                'rating' => $this->pivot->rating,
            ]),
        ];
    }
}
