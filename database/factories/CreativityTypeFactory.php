<?php

namespace Database\Factories;

use App\Models\CreativityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreativityTypeFactory extends Factory
{
    protected $model = CreativityType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->paragraph(),
        ];
    }
}