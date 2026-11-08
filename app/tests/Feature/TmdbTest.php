<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Models\Movie;

/*
 Laravel añade automaticamente '/api' a las rutas de 'routes/api.php', por eso añadimos 
    la palabra al inicio de los test -> identificamos endpoints de la API
 */

class TmdbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void{
        parent::setUp();

        config(['services.tmdb.token' => 'token-falso']);
    }


    //Comprueba que user sin autenticación NO puede ACCEDER y recibe error 401
    public function test_search_requires_authnetication(): void{
        $this->getJson('/api/tmdb/search?query=matrix')->assertUnauthorized();
    }


    /*
    Comprueba qué pasa si NO pasamos una QUERY -> pasa el test si da error 422
    */
    public function test_search_requires_query(): void{
        $this->actingAs(User::factory()->create())
            ->getJson('/api/tmdb/search')
            ->assertStatus(422)
            ->assertJsonValidationErrors('query');
    }


    /*
    Comprueba que devuelve status code 200 si hacemos busquedda valida
    Aparecería una pelicula (caratula) en la busqueda
    */
    public function test_search_returns_results(): void{

        //con fake hacemos una peticion falsa a una url que coincida con nuestras rutes, con el repsonse results y 
        //total _result emulamos la respuesta esperada
        Http::fake([
            'api.themoviedb.org/3/search/movie*' => Http::response([
                'results' => [['id' => 603, 'title' => 'Matrix']],
                'total_results' => 1,
            ]),
        ]);

        // con actingAs hacemos que un user falso autenticado reciba una respuesta buena con esos datos
        $this->actingAs(User::factory()->create())
            ->getJson('/api/tmdb/search?query=matrix')
            ->assertOk()
            ->assertJsonPath('results.0.title', 'Matrix');

        /*
        results.0.title
            La API nos devolvería un array con peliculas y sus datos
            Cogemos el primer elemento '0'
            Escogemos su título -> debe coincidir con 'Matrix'
        */

        //Obligamos a que la cabecera envie el token
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer token-falso'));    
    }


    //Comprueba que al meter ID correcto nos devuelve info -> obtenemos "el detalle" de una pelicula
    public function test_show_movie(): void{
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'Matrix'
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/tmdb/movies/603')
            ->assertOk()
            ->assertJsonPath('movie.title', 'Matrix');
    }


    //Comprueba que al buscar peli que no existe nos devuelve code status 404
    public function test_show_returns_404(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/*' => Http::response([], 404),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/tmdb/movies/999999999')
            ->assertNotFound()
            ->assertJson(['message' => 'Película no encontrada.']);
    }


    public function test_show_includes_users_rating(): void{
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response(['id' => 603, 'title' => 'Matrix']),
        ]);

        $movie = Movie::create(['tmdb_id' => 603, 'title' => 'Matrix']);

        User::factory()->create()->movies()->attach($movie->id, ['rating' => 8]);
        User::factory()->create()->movies()->attach($movie->id, ['rating' => 9]);
        User::factory()->create()->movies()->attach($movie->id, ['liked' => true]); // sin nota, no cuenta

        $this->actingAs(User::factory()->create())
            ->getJson('/api/tmdb/movies/603')
            ->assertOk()
            ->assertJsonPath('movie.title', 'Matrix')
            ->assertJsonPath('users_rating.average', 8.5)
            ->assertJsonPath('users_rating.count', 2);
    }

    public function test_show_users_rating_is_empty_without_votes(): void{
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response(['id' => 603, 'title' => 'Matrix']),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/tmdb/movies/603')
            ->assertJsonPath('users_rating.average', null)
            ->assertJsonPath('users_rating.count', 0);
    }
}
