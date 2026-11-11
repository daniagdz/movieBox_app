<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Override;

class MovieListTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tmdb.token' => 'token-falso']);

        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response([
                'id' => 603,
                'title' => 'Matrix',
                'release_date' => '1999-03-31',
                'poster_path' => '/matrix.jpg',
            ]),
            'api.themoviedb.org/3/movie/604*' => Http::response([
                'id' => 604,
                'title' => 'Matrix Reloaded',
                'release_date' => '2003-05-15',
                'poster_path' => '/reloaded.jpg',
            ]),

            'api.themoviedb.org/3/movie/*' => Http::response([], 404),
        ]);
    }

    public function test_needs_auth(): void
    {
        $this->getJson('/api/lists')->assertUnauthorized();
        $this->postJson('/api/lists', ['name' => 'Terror'])->assertUnauthorized();
    }

    public function test_user_can_create_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/lists', ['name' => 'Terror', 'description' => 'Para noches de miedo'])
            ->assertCreated()
            ->assertJsonPath('name', 'Terror')
            ->assertJsonPath('movies_count', 0);

        $this->assertDatabaseHas('movie_lists', [
            'user_id' => $user->id,
            'name' => 'Terror',
        ]);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/lists', ['description' => 'Sin nombre'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_name_cannot_exceed_100_characters(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/lists', ['name' => str_repeat('a', 101)])
            ->assertStatus(422) //se espera error de validacion, por eso 422
            ->assertJsonValidationErrors('name');
    }

    public function test_user_id_is_ignored(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1)
            ->postJson('/api/lists', ['name' => 'Mía', 'user_id' => $user2->id])
            ->assertCreated(); //se espera un create correcto -> status code 201 -> este metodo confirma ese estado

        $this->assertDatabaseHas('movie_lists', ['name' => 'Mía', 'user_id' => $user1->id]);
        $this->assertDatabaseMissing('movie_lists', ['name' => 'Mía', 'user_id' => $user2->id]);
    }

    public function test_getLists_returns_only_own_lists(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->movieLists()->create(['name' => 'Mi lista']);
        $user2->movieLists()->create(['name' => 'Lista ajena']);

        $this->actingAs($user1)
            ->getJson('/api/lists')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Mi lista');
    }

    public function test_getLists_includes_movies_count(): void
    {
        $user = User::factory()->create();
        $movieList = $user->movieLists()->create(['name' => 'Sci-fi']);

        $movie = Movie::create(['tmdb_id' => 603, 'title' => 'Matrix']);
        $movieList->movies()->attach($movie->id, ['position' => 1]);

        $this->actingAs($user)
            ->getJson('/api/lists')
            ->assertJsonPath('0.movies_count', 1);
    }

    public function test_show_requires_auth(): void
    {

        $user = User::factory()->create();
        $movieList = $user->movieLists()->create([
            'name' => 'Para halloween',
        ]);
        $this->getJson("/api/lists/{$movieList->id}")->assertUnauthorized();
    }

    public function test_user_view_his_movieList(): void
    {
        $user = User::factory()->create();
        $movieList = $user->movieLists()->create(['name' => 'Horror']);

        $movie = Movie::create(['tmdb_id' => 377, 'title' => 'Pesadilla en Elm street']);
        $movieList->movies()->attach($movie->id, ['position' => 1]);

        $this->actingAs($user)
            ->getJson("/api/lists/{$movieList->id}")
            ->assertOk()
            ->assertJsonPath('name', 'Horror')
            ->assertJsonPath('movies_count', 1)
            ->assertJsonPath('movies.0.title', 'Pesadilla en Elm street');
    }

    public function test_user_cant_see_others_lists(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $movieList = $user1->movieLists()->create(['name' => 'Lista de otro']);

        $this->actingAs($user2)
            ->getJson("/api/lists/{$movieList->id}")
            ->assertForbidden();
    }

    public function test_show_404_not_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->getJson('/api/lists/999999')
            ->assertNotFound();
    }

    //https://laravel.com/framework/docs/12.x/database-testing
    // === Para Testear DB ===
    public function test_owner_update_list(): void
    {
        $user = User::factory()->create();
        $movieList = $user->movieLists()->create(['name' => 'Antigua', 'description' => 'Peli previa']);

        $this->actingAs($user)
            ->patchJson("/api/lists/{$movieList->id}", ['name' => 'Nueva'])
            ->assertOk()
            ->assertJsonPath('name', 'Nueva')
            //->assertJsonPath('description', 'Texto'); probemos a ver si da error por comprobar -> da error
            ->assertJsonPath('description', 'Peli previa');

        $this->assertDatabaseHas('movie_lists', ['id' => $movieList->id, 'name' => 'Nueva']);
    }

    public function test_update_rejects_empty_name(): void
    {
        $user = User::factory()->create();
        $movieList = $user->movieLists()->create(['name' => 'Antigua']);

        $this->actingAs($user)
            ->patchJson("/api/lists/{$movieList->id}", ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_user_cannot_update_another_users_list(): void
    {
        $movieList = User::factory()->create()->movieLists()->create(['name' => 'De otro']);

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/lists/{$movieList->id}", ['name' => 'Intento de cambio'])
            ->assertForbidden();

        $this->assertDatabaseHas('movie_lists', ['id' => $movieList->id, 'name' => 'De otro']);
    }

    public function test_owner_can_delete_list_and_movies(): void
    {
        $user = User::factory()->create();
        $movieList = $user->movieLists()->create(['name' => 'Lista a Borrar']);

        $movie = Movie::create(['tmdb_id' => 603, 'title' => 'Matrix']);
        $movieList->movies()->attach($movie->id, ['position' => 1]);

        $this->actingAs($user)
            ->deleteJson("/api/lists/{$movieList->id}")
            ->assertNoContent();

        //comprobar que se borro la lista
        $this->assertDatabaseMissing('movie_lists', ['id' => $movieList->id]);
        //comprobar que en tabla "pivote" no hay registro
        $this->assertDatabaseCount('movie_list_items', 0);
        //comprobar que no se elimino de 'movies'
        $this->assertDatabaseHas('movies', ['tmdb_id' => 603]);
    }

    public function test_user_cannot_delete_another_users_list(): void
    {
        $movieList = User::factory()->create()->movieLists()->create(['name' => 'Ajena']);

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/lists/{$movieList->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('movie_lists', ['id' => $movieList->id]);
    }

    public function test_add_movie_with_auth(): void
    {

        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);

        $this->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 603])->assertUnauthorized();
    }

    public function test_add_movie_import_and_attach(): void
    {
        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);

        $this->actingAs($user)
            ->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 603])
            ->assertOk()
            ->assertJsonPath('movies_count', 1)
            ->assertJsonPath('movies.0.title', 'Matrix');

        $movie = Movie::where('tmdb_id', 603)->first();
        $this->assertNotNull($movie);
        $this->assertDatabaseHas('movie_list_items', [
            'movie_list_id' => $list->id,
            'movie_id' => $movie->id,
            'position' => 1,
        ]);
    }

    public function test_add_movie_twice_dont_duplicate(): void
    {
        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);

        $this->actingAs($user)->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 603]);
        $this->actingAs($user)->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 604]);
        $this->actingAs($user)
            ->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 603]) //probamos a añadir matrix por 2a vez
            ->assertOk()
            ->assertJsonPath('movies_count', 2);

        // Al añadir la peli por segunda vez no modifica su posicion original:
        $matrix = Movie::where('tmdb_id', 603)->first();
        $this->assertDatabaseHas('movie_list_items', ['movie_id' => $matrix->id, 'position' => 1]);
    }

    public function test_consecutive_positions(): void
    {
        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);

        $this->actingAs($user)->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 603]);


        $this->actingAs($user)
            ->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 604])
            ->assertJsonPath('movies.0.title', 'Matrix')
            ->assertJsonPath('movies.1.title', 'Matrix Reloaded')
            ->assertJsonPath('movies.1.pivot.position', 2);
    }







    //==================================

    public function test_add_movie_requires_tmdb_id(): void
    {
        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);

        $this->actingAs($user)
            ->postJson("/api/lists/{$list->id}/movies", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tmdb_id');
    }

    public function test_add_unknown_movie_404(): void
    {
        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);

        $this->actingAs($user)
            ->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 999999999])
            ->assertNotFound();

        $this->assertDatabaseCount('movie_list_items', 0);
    }

    public function test_user_cant_add_to_another_users_list(): void
    {
        $list = User::factory()->create()->movieLists()->create(['name' => 'De otro']);

        $this->actingAs(User::factory()->create())
            ->postJson("/api/lists/{$list->id}/movies", ['tmdb_id' => 603])
            ->assertForbidden();

        $this->assertDatabaseCount('movie_list_items', 0);
        $this->assertDatabaseCount('movies', 0);   // no se importó nada
        // Http::assertNothingSent();                 // ni se llamó a TMDB
    }

    public function test_remove_movie_but_keeps_it(): void
    {
        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);
        $movie = Movie::create(['tmdb_id' => 603, 'title' => 'Matrix']);
        $list->movies()->attach($movie->id, ['position' => 1]);

        $this->actingAs($user)
            ->deleteJson("/api/lists/{$list->id}/movies/603")
            ->assertNoContent();

        $this->assertDatabaseCount('movie_list_items', 0);
        $this->assertDatabaseHas('movies', ['tmdb_id' => 603]);
    }

    public function test_remove_movie_is_idempotent(): void
    {
        $user = User::factory()->create();
        $list = $user->movieLists()->create(['name' => 'Sci-fi']);

        $this->actingAs($user)->deleteJson("/api/lists/{$list->id}/movies/603")->assertNoContent();
    }

    public function test_user_cannot_remove_movie_from_another_users_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $list = $user1->movieLists()->create(['name' => 'Ajena']);
        $movie = Movie::create(['tmdb_id' => 603, 'title' => 'Matrix']);
        $list->movies()->attach($movie->id, ['position' => 1]);

        $this->actingAs($user2)
            ->deleteJson("/api/lists/{$list->id}/movies/603")
            ->assertForbidden();

        $this->assertDatabaseCount('movie_list_items', 1);
    }

    public function test_non_owner_gets_403_even_with_invalid_data(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $list = $user1->movieLists()->create(['name' => 'Lista innaccesible']);

        $this->actingAs($user2)
            ->patchJson("/api/lists/{$list->id}", ['name' => ''])
            ->assertForbidden();
    }

    public function test_non_owner_gets_403_when_adding_with_invalid_data(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $list = $user1->movieLists()->create(['name' => 'Lista innaccesible']);

        $this->actingAs($user2)
            ->postJson("/api/lists/{$list->id}/movies", [])
            ->assertForbidden();
    }
}
