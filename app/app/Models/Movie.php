<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = ['tmdb_id', 'title', 'release_date', 'poster_path'];

    protected function casts(): array {
        return [ 'release_date' => 'date'];
    }

    /*
    Hay realcion N-M entre Movie y User / MovieList
    Para gestionarla, indicamos la relacion 'belongsToMany' e indicamos
        la tabla intermedia
    
    'withPivot' -> permite extraer los datos relacionados de, por ej,
        un User y una Peli que haya puesto él

    'withTimestamps' -> asigna fecha y hora automaticamente a las mods hechas
        en las tablas intermedias 
            ->UTILIDAD: visto recientemente, añadido recientemente etc
    */
    public function users(){
        return(
            $this->belongsToMany(User::class, 'movie_user')
                ->withPivot(['wathced', 'liked', 'watchlisted', 'rating', 'review'])
                ->withTimestamps()
        );
    }

    public function lists(){
        return(
            $this->belongsToMany(MovieList::class, 'movie_list_items')
                ->withPivot('position')
                ->withTimestamps()
        );
    }
}
