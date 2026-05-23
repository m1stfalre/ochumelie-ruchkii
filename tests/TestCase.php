<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Отключаем транзакции для SQLite
        DB::statement('PRAGMA foreign_keys = OFF');
        
        // Создаём базовые данные
        if (\App\Models\CreativityType::count() === 0) {
            \App\Models\CreativityType::create([
                'name' => 'Test Type',
                'description' => 'Test Description'
            ]);
        }
    }
    
    protected function tearDown(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        parent::tearDown();
    }
}