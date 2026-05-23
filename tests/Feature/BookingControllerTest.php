<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterClass;
use App\Models\CreativityType;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $instructor;
    private $type;
    private $masterClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'full_name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456789',
            'role' => 'visitor',
        ]);

        $this->instructor = User::create([
            'full_name' => 'Instructor',
            'email' => 'instructor@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456788',
            'role' => 'instructor',
        ]);

        $this->type = CreativityType::create([
            'name' => 'Art',
            'description' => 'Description'
        ]);

        $this->masterClass = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Test Class',
            'description' => 'This is a test description that is long enough',
            'date' => Carbon::now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);
    }

    /** @test */
    public function it_shows_confirmation_page_for_valid_booking()
    {
        $this->actingAs($this->user);

        // Используем правильное имя маршрута: booking.confirm
        $response = $this->get(route('booking.confirm', $this->masterClass->id));

        $response->assertStatus(200);
        $response->assertViewIs('confirm');
        $response->assertViewHas('masterClass', $this->masterClass);
    }

    /** @test */
    public function it_redirects_when_no_free_seats()
    {
        $this->actingAs($this->user);

        $this->masterClass->update(['max_participants' => 0]);

        $response = $this->get(route('booking.confirm', $this->masterClass->id));

        $response->assertSessionHas('error', 'К сожалению, свободных мест больше нет.');
        $response->assertRedirect();
    }

    /** @test */
    public function it_redirects_when_user_already_booked()
    {
        $this->actingAs($this->user);

        Booking::create([
            'user_id' => $this->user->id,
            'master_class_id' => $this->masterClass->id,
        ]);

        $response = $this->get(route('booking.confirm', $this->masterClass->id));

        $response->assertSessionHas('error', 'Вы уже записаны на этот мастер-класс.');
        $response->assertRedirect();
    }

    /** @test */
    public function it_processes_confirmed_booking()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('booking.process', $this->masterClass->id), [
            'action' => 'confirm'
        ]);

        $response->assertRedirect(route('category.show', $this->masterClass->type_id));
        $response->assertSessionHas('message', 'Вы успешно записаны на мастер-класс!');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'master_class_id' => $this->masterClass->id,
        ]);
    }

    /** @test */
    public function it_cancels_booking_when_action_is_cancel()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('booking.process', $this->masterClass->id), [
            'action' => 'cancel'
        ]);

        $response->assertRedirect(route('category.show', $this->masterClass->type_id));
        $response->assertSessionHas('message', 'Запись была отменена.');

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $this->user->id,
            'master_class_id' => $this->masterClass->id,
        ]);
    }

    /** @test */
    public function it_prevents_booking_when_no_seats_left_during_process()
    {
        $this->actingAs($this->user);

        $this->masterClass->update(['max_participants' => 0]);

        $response = $this->post(route('booking.process', $this->masterClass->id), [
            'action' => 'confirm'
        ]);

        $response->assertSessionHas('error', 'Мест больше нет. Попробуйте другой мастер-класс.');
        $response->assertRedirect();
    }

    /** @test */
    public function it_prevents_double_booking_during_process()
    {
        $this->actingAs($this->user);

        Booking::create([
            'user_id' => $this->user->id,
            'master_class_id' => $this->masterClass->id,
        ]);

        $response = $this->post(route('booking.process', $this->masterClass->id), [
            'action' => 'confirm'
        ]);

        $response->assertSessionHas('error', 'Вы уже записаны на этот мастер-класс.');
        $response->assertRedirect();
    }

    /** @test */
    public function it_requires_authentication_for_booking()
    {
        $response = $this->get(route('booking.confirm', $this->masterClass->id));

        $response->assertRedirect('/login');
    }
}
