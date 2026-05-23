<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\CreativityType;
use App\Models\MasterClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private $type;
    private $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = CreativityType::create([
            'name' => 'Architecture',
            'description' => 'Test description for architecture category'
        ]);

        $this->instructor = User::factory()->create(['role' => 'instructor']);
    }

    /** @test */
    public function it_displays_category_page_with_upcoming_classes()
    {
        $futureClass = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Future Class',
            'description' => 'Description',
            'date' => now()->addDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $pastClass = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Past Class',
            'description' => 'Description',
            'date' => now()->subDays(5),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $response = $this->get(route('category.show', $this->type->id));

        $response->assertStatus(200);
        $response->assertViewIs('category');
        $response->assertViewHas('type', $this->type);

        $classes = $response->viewData('classes');
        $this->assertTrue($classes->contains($futureClass));
        $this->assertFalse($classes->contains($pastClass));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_category()
    {
        $response = $this->get(route('category.show', 99999));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_orders_classes_by_date_and_time()
    {
        $class1 = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Class 1',
            'description' => 'Description',
            'date' => now()->addDays(2),
            'start_time' => '13:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $class2 = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Class 2',
            'description' => 'Description',
            'date' => now()->addDays(1),
            'start_time' => '09:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $class3 = MasterClass::create([
            'instructor_id' => $this->instructor->id,
            'type_id' => $this->type->id,
            'title' => 'Class 3',
            'description' => 'Description',
            'date' => now()->addDays(1),
            'start_time' => '11:00',
            'max_participants' => 10,
            'price' => 1000,
        ]);

        $response = $this->get(route('category.show', $this->type->id));

        $classes = $response->viewData('classes')->pluck('title')->toArray();

        $this->assertEquals(['Class 2', 'Class 3', 'Class 1'], $classes);
    }
}
