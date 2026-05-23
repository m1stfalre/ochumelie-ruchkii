<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

abstract class TestCase extends BaseTestCase
{
    use DatabaseMigrations;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестовые данные если таблица пуста
        if (\App\Models\CreativityType::count() === 0) {
            \App\Models\CreativityType::create([
                'name' => 'Test Type',
                'description' => 'Test Description'
            ]);
        }
    }
}