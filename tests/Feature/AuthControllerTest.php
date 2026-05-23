<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_login_page()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('login');
    }

    /** @test */
    public function it_displays_register_page()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('register');
    }

    /** @test */
    public function it_logs_in_user_with_valid_credentials()
    {
        $user = User::create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456789',
            'role' => 'visitor',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    /** @test */
    public function it_redirects_instructor_to_cabinet_after_login()
    {
        $instructor = User::create([
            'full_name' => 'Instructor User',
            'email' => 'instructor@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456788',
            'role' => 'instructor',
        ]);

        $response = $this->post('/login', [
            'email' => 'instructor@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('cabinet.index'));
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['email' => 'Неверный email или пароль.']);
        $this->assertGuest();
    }

    /** @test */
    public function it_validates_required_fields_on_login()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /** @test */
    public function it_registers_a_new_visitor_user()
    {
        $response = $this->post('/register', [
            'full_name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '+79123456789',
        ]);

        // Проверяем что редирект на login
        $response->assertRedirect('/login');

        // Проверяем сообщение об успехе
        $response->assertSessionHas('success', 'Регистрация успешна!.');

        // Проверяем что пользователь создан
        $this->assertDatabaseHas('users', [
            'email' => 'ivan@example.com',
            'full_name' => 'Иван Петров',
            'role' => 'visitor',
        ]);
    }

    /** @test */
    public function it_validates_unique_email_on_registration()
    {
        User::create([
            'full_name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456780',
            'role' => 'visitor',
        ]);

        $response = $this->post('/register', [
            'full_name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '+79123456789',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function it_validates_password_complexity_on_registration()
    {
        $response = $this->post('/register', [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'simple',
            'password_confirmation' => 'simple',
            'phone' => '+79123456789',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function it_logs_out_user()
    {
        $user = User::create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456789',
            'role' => 'visitor',
        ]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
