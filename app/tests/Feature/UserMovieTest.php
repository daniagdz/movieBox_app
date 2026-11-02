<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserMovieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void{
        parent::setUp();

        config(['services.tmdb.token' => 'token-falso']);

        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'Matrix',
                'release_date' => '1999-03-31',
                'poster_path' => 'matrix.jpg',
            ]),
            'api.themoviedb.org/3/movie/*' => Http::response([],  404),
        ]);
    }


    //===============
    // TEST UPDATE
    //===============
    public function test_requires_authentication(): void{
        $this->patchJson('/api/movies/603/state', ['liked' => true])->assertUnauthorized();
    }


    public function test_like_movie_and_import(): void{
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/movies/603/state', ['liked' => true])
            ->assertOk()
            ->assertJsonPath('state.liked', true)
            ->assertJsonPath('state.watched', false);

        $movie = Movie::where('tmdb_id', 603)->first();
        $this->assertNotNull($movie);
        $this->assertDatabaseHas('movie_user', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'liked' => true,
        ]);
    }


    public function test_rating_watched_movie(): void{
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/movies/603/state', ['rating' => 8]) //realiza la accion
            ->assertOk()
            ->assertJsonPath('state.rating', 8) //comprueba la acción
            ->assertJsonPath('state.watched', true); //se comprueba que al puntuar se marca como vista
    }


    public function test_some_updates(): void{
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/movies/603/state', ['liked' => true]); //primera accion damos like

        $this->actingAs($user)
            ->patchJson('/api/movies/603/state',['rating' => 6]) //segunda accion damos puntuacion
            ->assertJsonPath('state.liked', true) //comprobamos que los cambios son acumulativos -> actualiza N cambios
            ->assertJsonPath('state.rating', 6);  
    }


    public function test_remove_states_keeps_movie(): void{
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/movies/603/state', ['liked' => true]);

        $this->actingAs($user)
            ->patchJson('/api/movies/603/state', ['liked' => false])
            ->assertOk()
            ->assertJsonPath('state.liked', false);

        $this->assertDatabaseCount('movie_user', 0);
        $this->assertDatabaseHas('movies', ['tmdb_id' => 603]);
    }


    public function test_rating_oorange(): void{
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/movies/603/state', ['rating' => 11])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating');
    }


    public function test_unknown_movie(): void{
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/movies/9999999/state', ['liked' => true])
            ->assertNotFound();
    }



    //==================
    // TEST SHOW e INDEX
    //==================

    public function test_show_returns_empty_state_for_unknown_movie(): void{
        $this->actingAs(User::factory()->create())
            ->getJson('/api/movies/603/state')
            ->assertOk()
            ->assertJsonPath('state.watched', false)
            ->assertJsonPath('state.rating', null);

        // Leer no importa la película
        $this->assertDatabaseCount('movies', 0);
    }


    public function test_show_returns_user_state(): void{
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/movies/603/state', ['liked' => true, 'rating' => 9]);

        $this->actingAs($user)
            ->getJson('/api/movies/603/state')
            ->assertOk()
            ->assertJsonPath('state.liked', true)
            ->assertJsonPath('state.watched', true)
            ->assertJsonPath('state.rating', 9);
    }


    public function test_show_does_not_leak_state_from_other_users(): void{
        $user1 = User::factory()->create();
        $this->actingAs($user1)->patchJson('/api/movies/603/state', ['liked' => true]);

        $user2 = User::factory()->create();
        $this->actingAs($user2)
            ->getJson('/api/movies/603/state')
            ->assertJsonPath('state.liked', false);
    }


    public function test_index_valid_state(): void{
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/movies')->assertStatus(422);
        $this->actingAs($user)->getJson('/api/movies?state=otro')->assertStatus(422);
    }

    public function test_index_filters_by_state(): void{
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/movies/603/state', ['watchlisted' => true]);

        $this->actingAs($user)
            ->getJson('/api/movies?state=watchlisted')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tmdb_id', 603);

        $this->actingAs($user)
            ->getJson('/api/movies?state=liked')
            ->assertJsonCount(0, 'data');
    }

    public function test_index_returns_rated_movies(): void{
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/movies/603/state', ['liked' => true]);

        $this->actingAs($user)
            ->getJson('/api/movies?state=rated')
            ->assertJsonCount(0, 'data');

        $this->actingAs($user)->patchJson('/api/movies/603/state', ['rating' => 8]);

        $this->actingAs($user)
            ->getJson('/api/movies?state=rated')
            ->assertJsonCount(1, 'data');
    }

    public function test_index_returns_own_movies(): void{
        $other = User::factory()->create();
        $this->actingAs($other)->patchJson('/api/movies/603/state', ['liked' => true]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/movies?state=liked')
            ->assertJsonCount(0, 'data');
    }
}
