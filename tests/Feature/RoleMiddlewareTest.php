<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_redirects_unauthenticated_user_to_login()
    {
        $response = $this->get('/cabinet');
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_allows_instructor_to_access_instructor_routes()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $this->actingAs($instructor);
        
        $response = $this->get('/cabinet');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function it_blocks_visitor_from_accessing_instructor_routes()
    {
        $visitor = User::factory()->create(['role' => 'visitor']);
        $this->actingAs($visitor);
        
        $response = $this->get('/cabinet');
        
        $response->assertStatus(403);
    }
}