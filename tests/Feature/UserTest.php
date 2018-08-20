<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
	use RefreshDatabase;


    public function testCreateUser()
    {
    	$userData = [
		    'currency' => 'USD',
		    'country' => 'country',
		    'name' => 'SomeName',
		    'city' => 'SomeCity'
	    ];
	    $this->post('/api/user', $userData)
		    ->assertStatus(200)
		    ->assertJson([
			    'status' => true
		    ]);

	    $user = User::query()->where(array_only($userData, ['name','city']))->first();
	    $this->assertNotNull($user);
    }
}
