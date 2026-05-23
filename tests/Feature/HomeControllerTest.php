<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CreativityType;
use App\Models\MasterClass;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_home_page_for_guest()
    {
        $type = CreativityType::create(['name' => 'Art', 'description' => 'Desc']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('types');
        $response->assertViewHas('allClasses');
        $response->assertViewHas('myBookings');

        $this->assertTrue($response->viewData('myBookings')->isEmpty());
    }

    /** @test */
    public function it_shows_my_bookings_for_authenticated_user()
    {
        $user = User::factory()->create();
        $instructor = User::factory()->create(['role' => 'instructor']);
        $type = CreativityType::create(['name' => 'Art', 'description' => 'Desc']);

        $masterClass = MasterClass::create([
            'instructor_id' => $instructor->id,
            'type_id' => $type->id,
            'title' => 'Test Class',
            'description' => 'Description',
            'date' => now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        Booking::create([
            'user_id' => $user->id,
            'master_class_id' => $masterClass->id,
        ]);

        $this->actingAs($user);

        $response = $this->get('/');

        $myBookings = $response->viewData('myBookings');
        $this->assertCount(1, $myBookings);
        $this->assertEquals($masterClass->id, $myBookings->first()->masterClass->id);
    }

    /** @test */
    public function it_only_shows_future_classes_on_home_page()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $type = CreativityType::create(['name' => 'Art', 'description' => 'Desc']);

        $futureClass = MasterClass::create([
            'instructor_id' => $instructor->id,
            'type_id' => $type->id,
            'title' => 'Future Class',
            'description' => 'Description',
            'date' => now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $pastClass = MasterClass::create([
            'instructor_id' => $instructor->id,
            'type_id' => $type->id,
            'title' => 'Past Class',
            'description' => 'Description',
            'date' => now()->subDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $todayClass = MasterClass::create([
            'instructor_id' => $instructor->id,
            'type_id' => $type->id,
            'title' => 'Today Class',
            'description' => 'Description',
            'date' => now()->startOfDay(),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $response = $this->get('/');

        $allClasses = $response->viewData('allClasses');
        $this->assertTrue($allClasses->contains($futureClass));
        $this->assertTrue($allClasses->contains($todayClass));
        $this->assertFalse($allClasses->contains($pastClass));
    }
}
