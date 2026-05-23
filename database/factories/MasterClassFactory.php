<?php

namespace Database\Factories;

use App\Models\MasterClass;
use App\Models\User;
use App\Models\CreativityType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class MasterClassFactory extends Factory
{
    protected $model = MasterClass::class;

    public function definition(): array
    {
        return [
            'instructor_id' => User::factory()->create(['role' => 'instructor']),
            'type_id' => CreativityType::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'date' => Carbon::now()->addDays(rand(1, 30)),
            'start_time' => $this->faker->randomElement(['09:00', '11:00', '13:00', '15:00']),
            'max_participants' => rand(5, 20),
            'price' => rand(500, 5000),
        ];
    }
}
