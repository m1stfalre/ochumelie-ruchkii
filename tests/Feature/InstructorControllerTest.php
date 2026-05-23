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

class InstructorControllerTest extends TestCase
{
    use RefreshDatabase;

    private $instructor;
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /** @test */
    public function instructor_can_view_their_cabinet()
    {
        $this->actingAs($this->instructor);

        $response = $this->get(route('cabinet.index'));

        $response->assertStatus(200);
        $response->assertViewIs('cabinet');
    }

    /** @test */
    public function visitor_cannot_access_instructor_cabinet()
    {
        $visitor = User::create([
            'full_name' => 'Visitor',
            'email' => 'visitor@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456789',
            'role' => 'visitor',
        ]);

        $this->actingAs($visitor);

        $response = $this->get(route('cabinet.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function instructor_can_view_create_form()
    {
        $this->actingAs($this->instructor);

        $response = $this->get(route('cabinet.create'));

        $response->assertStatus(200);
        $response->assertViewIs('create');
    }

    /** @test */
    public function instructor_can_create_master_class()
    {
        $this->actingAs($this->instructor);

        $response = $this->post(route('cabinet.store'), [
            'type_id' => $this->type->id,
            'title' => 'New Master Class',
            'description' => 'This is a valid description for the master class',
            'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'start_time' => '11:00',
            'max_participants' => 15,
            'price' => 2000,
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('message', 'Мастер-класс "New Master Class" успешно добавлен!');

        $this->assertDatabaseHas('master_classes', [
            'title' => 'New Master Class',
            'instructor_id' => $this->instructor->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_class()
    {
        $this->actingAs($this->instructor);

        $response = $this->post(route('cabinet.store'), []);

        $response->assertSessionHasErrors([
            'type_id', 'title', 'description', 'date', 'start_time', 'max_participants', 'price'
        ]);
    }

    /** @test */
    public function it_prevents_creating_class_on_past_date()
    {
        $this->actingAs($this->instructor);

        $response = $this->post(route('cabinet.store'), [
            'type_id' => $this->type->id,
            'title' => 'Past Class',
            'description' => 'Valid description here',
            'date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $response->assertSessionHasErrors(['date']);
    }

    /** @test */
    public function instructor_can_edit_their_class()
    {
        $this->actingAs($this->instructor);

        $masterClass = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Original Title',
            'description' => 'Original description that is long enough',
            'date' => Carbon::now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $response = $this->put(route('cabinet.update', $masterClass->id), [
            'description' => 'Updated description that is long enough',
            'price' => 1500,
        ]);

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('message', 'Мастер-класс "Original Title" успешно обновлен!');

        $this->assertDatabaseHas('master_classes', [
            'id' => $masterClass->id,
            'description' => 'Updated description that is long enough',
            'price' => 1500,
        ]);
    }

    /** @test */
    public function instructor_cannot_edit_other_instructors_class()
    {
        $otherInstructor = User::create([
            'full_name' => 'Other Instructor',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456780',
            'role' => 'instructor',
        ]);

        $this->actingAs($this->instructor);

        $masterClass = MasterClass::create([
            'instructor_id' => $otherInstructor->id,
            'type_id' => $this->type->id,
            'title' => 'Other Class',
            'description' => 'Description',
            'date' => Carbon::now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $response = $this->get(route('cabinet.edit', $masterClass->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function instructor_can_delete_class_without_bookings()
    {
        $this->actingAs($this->instructor);

        $masterClass = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Delete Me',
            'description' => 'Description',
            'date' => Carbon::now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $response = $this->delete(route('cabinet.destroy', $masterClass->id));

        $response->assertRedirect(route('cabinet.index'));
        $response->assertSessionHas('message', 'Мастер-класс "Delete Me" успешно удален!');

        $this->assertDatabaseMissing('master_classes', ['id' => $masterClass->id]);
    }

    /** @test */
    public function instructor_cannot_delete_class_with_bookings()
    {
        $this->actingAs($this->instructor);

        $masterClass = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Has Bookings',
            'description' => 'Description',
            'date' => Carbon::now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $user = User::create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+79123456789',
            'role' => 'visitor',
        ]);

        Booking::create([
            'user_id' => $user->id,
            'master_class_id' => $masterClass->id,
        ]);

        $response = $this->delete(route('cabinet.destroy', $masterClass->id));

        $response->assertSessionHas('error', 'Нельзя удалить мастер-класс, на который уже есть записи.');
        $response->assertRedirect();

        $this->assertDatabaseHas('master_classes', ['id' => $masterClass->id]);
    }
}
