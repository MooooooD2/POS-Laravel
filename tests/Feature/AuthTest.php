<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** @test */
    public function login_page_is_accessible()
    {
        $this->get('/login')->assertStatus(200);
    }

    /** @test */
    public function active_user_can_login()
    {
        $user = User::factory()->create(['username' => 'testuser', 'password' => bcrypt('Secret123'), 'is_active' => true]);
        $user->assignRole('cashier');

        $this->postJson('/login', ['username' => 'testuser', 'password' => 'Secret123'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function disabled_user_cannot_login()
    {
        User::factory()->create(['username' => 'disabled', 'password' => bcrypt('Secret123'), 'is_active' => false]);

        $this->postJson('/login', ['username' => 'disabled', 'password' => 'Secret123'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function wrong_password_returns_401()
    {
        User::factory()->create(['username' => 'active', 'password' => bcrypt('CorrectPass1'), 'is_active' => true]);

        $this->postJson('/login', ['username' => 'active', 'password' => 'WrongPass1'])
            ->assertStatus(401);
    }

    /** @test */
    public function login_is_rate_limited_after_5_attempts()
    {
        User::factory()->create(['username' => 'ratetest', 'password' => bcrypt('Secret123'), 'is_active' => true]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['username' => 'ratetest', 'password' => 'Wrong1Pass']);
        }

        $this->postJson('/login', ['username' => 'ratetest', 'password' => 'Wrong1Pass'])
            ->assertStatus(429);
    }

    /** @test */
    public function session_info_requires_auth()
    {
        $this->getJson('/session-info')->assertStatus(302); // redirect to login
    }

    /** @test */
    public function authenticated_user_can_get_session_info()
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('cashier');

        $this->actingAs($user)->getJson('/session-info')
            ->assertStatus(200)
            ->assertJsonStructure(['logged_in', 'username', 'full_name']);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->postJson('/logout')->assertStatus(302);
        $this->assertGuest();
    }
}
