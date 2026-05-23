<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\MasterClass;
use App\Models\Booking;
use App\Models\CreativityType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_user()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $booking->user);
        $this->assertEquals($user->id, $booking->user->id);
    }

    /** @test */
    public function it_belongs_to_master_class()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $type = CreativityType::create(['name' => 'Test', 'description' => 'Desc']);
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
        
        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'master_class_id' => $masterClass->id,
        ]);
        
        $this->assertInstanceOf(MasterClass::class, $booking->masterClass);
        $this->assertEquals($masterClass->id, $booking->masterClass->id);
    }

    /** @test */
    public function is_already_booked_returns_true_when_booking_exists()
    {
        $user = User::factory()->create();
        $instructor = User::factory()->create(['role' => 'instructor']);
        $type = CreativityType::create(['name' => 'Test', 'description' => 'Desc']);
        
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
        
        $this->assertTrue(Booking::isAlreadyBooked($user->id, $masterClass->id));
    }

    /** @test */
    public function is_already_booked_returns_false_when_booking_does_not_exist()
    {
        $user = User::factory()->create();
        $instructor = User::factory()->create(['role' => 'instructor']);
        $type = CreativityType::create(['name' => 'Test', 'description' => 'Desc']);
        
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
        
        $this->assertFalse(Booking::isAlreadyBooked($user->id, $masterClass->id));
    }
}