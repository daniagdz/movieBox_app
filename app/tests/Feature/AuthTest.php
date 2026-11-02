<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;


class AuthTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void{
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:8080');
    }


    public function test_user_register(): void {
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'dani1234',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'test@test.com']);
        $this->assertAuthenticated();
    }


    public function test_user_can_login(): void{
        $user = User::factory()->create(['password' => 'dani1234']);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'dani1234',
        
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
    }


    public function test_login_wrong_pass(): void{
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'contramala1'
        
        ])->assertStatus(422);

        $this->assertGuest();
    }


    /*
    Peticion a la ruta definida para obtener datos
    'assertUnauthorized()' -> debe devolver 401 porque estamos haciendo la peticion sin habernos
        registrado, si da otro valor cualquiera puede ver los datos

    */
    public function test_user_endpoint_requires_authentication(): void{
        $this->getJson('/api/user')->assertUnauthorized();
    }


    public function test_user_can_logout(): void{
        $user = User::factory()->create(); //crea user random en la bd

        $this->actingAs($user) //metodo mágico Laravel -> emula que el user hizo login
            ->postJson('/api/logout')
            ->assertNoContent(); //comprueba que server devuelve HTTP 204 (se procesa peti pero no devuleve valor)
    }
}
