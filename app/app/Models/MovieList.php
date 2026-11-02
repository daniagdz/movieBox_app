<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieList extends Model
{
    //'$fillable' -> palabra reservada que indica a laravel qué campos puede guardar en BD
    protected $fillable  = ['user_id', 'name', 'description'];

    public function user(){
        return(
            $this->belongsTo(User::class)
        );
    }

    public function movies(){
        return(
            $this->belongsToMany(Movie::class, 'movie_list_items')
                ->withPivot('position')
                ->withTimestamps()
                ->orderBy('movie_list_items.position')
        );
    }
}
