<?php

use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use App\Models\Media;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Middleware\JwtFromCookie;
use App\Mail\UserCredentialsUpdateMail;
use Illuminate\Support\Facades\Storage;
use App\Mail\UserCredentialsRegistrationMail;

beforeEach(function () {
    $this->withoutMiddleware(JwtFromCookie::class);
    $admin = actingAsAdmin();
    $this->actingAs($admin, 'api');
    Storage::fake('public');
    Mail::fake();
});


function actingAsAdmin()
{
    $adminRole = Role::create([
        'id' => Str::uuid(),
        'name' => 'admin',
        'code' => 'admin',
        'guard_name' => 'api',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $admin = User::create([
        'id' => Str::uuid(),
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
        'status' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    $admin->assignRole($adminRole);

    return $admin;
}

describe('UserController - Index', function () {
    it('can retrieve all users', function () {

        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'id' => Str::uuid(),
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => Hash::make('password'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $response = $this->getJson('/api/auth/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'email']
                ]
            ]);
    });
});

describe('UserController - Store', function () {
    it('can create a new user', function () {
        $role = Role::create([
            'id' => Str::uuid(),
            'name' => 'user',
            'code' => 'user',
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role_id' => $role->id,
        ];

        $response = $this->postJson('/api/auth/users', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'code' => 201,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe'
        ]);

        Mail::assertSent(UserCredentialsRegistrationMail::class);


        $user = User::where('email', 'john@example.com')->first();
        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'file' => 'photos/pic.jpg'
        ]);
    });

    it('can create user with custom password', function () {
        $role = Role::create([
            'id' => Str::uuid(),
            'name' => 'user',
            'code' => 'user',
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role_id' => $role->id,
            'password' => 'custompassword123'
        ];

        $response = $this->postJson('/api/auth/users', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('custompassword123', $user->password));
    });
});

describe('UserController - Update', function () {
    it('can update a user', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('password'),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/auth/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    });

    it('can update user password', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123'
        ];

        $response = $this->putJson("/api/auth/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        Mail::assertSent(UserCredentialsUpdateMail::class);
    });
});

describe('UserController - Profile Update', function () {
    it('can update own profile', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'password' => Hash::make('password'),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->actingAs($user, 'api');

        $updateData = [
            'name' => 'Updated Profile Name',
            'email' => 'updated.profile@example.com',
        ];

        $response = $this->postJson("/api/auth/users/update-profile/{$user->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Profile Name',
            'email' => 'updated.profile@example.com'
        ]);
    });
});

describe('UserController - Profile Photo', function () {
    it('can update profile photo', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Photo User',
            'email' => 'photo@example.com',
            'password' => Hash::make('password'),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->postJson("/api/auth/users/update-profile-photo/{$user->id}", [
            'photo' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
            ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
        ]);


        $media = Media::where('user_id', $user->id)->first();
        Storage::disk('public')->assertExists($media->file);
    });
});

describe('UserController - Authentication', function () {
    it('can login with valid credentials', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Login User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
                'message' => 'Connexion réussie'
            ])
            ->assertJsonStructure([
                'profile',
                'roles',
                'permissions'
            ]);


        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    });

    it('fails login with disabled account', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Disabled User',
            'email' => 'disabled@example.com',
            'password' => Hash::make('password123'),
            'status' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'disabled@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'code' => 403,
                'message' => 'Compte désactivé. Contactez l\'administrateur.'
            ]);
    });
});

describe('UserController - Role Management', function () {
    it('can update user role', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Role User',
            'email' => 'role@example.com',
            'password' => Hash::make('password'),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $newRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'manager',
            'code' => 'manager',
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->postJson("/api/auth/users/update-role/{$user->id}", [
            'role_id' => $newRole->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
            ]);

        $this->assertTrue($user->fresh()->hasRole($newRole->name));
    });
});

describe('UserController - Status Management', function () {
    it('can toggle user status', function () {
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'Status User',
            'email' => 'status@example.com',
            'password' => Hash::make('password'),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->patchJson("/api/auth/users/toggle-status/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'new_status' => false
                ]
            ]);

        $this->assertTrue(!$user->fresh()->status);
    });
});

describe('UserController - Logging', function () {
    it('logs events correctly', function () {
        $role = Role::create([
            'id' => Str::uuid(),
            'name' => 'user',
            'code' => 'user',
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role_id' => $role->id,
        ];

        $response = $this->postJson('/api/auth/users', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('logs', [
            'event' => auth()->user()->name . ' a créé l\'utilisateur "John Doe"'
        ]);
    });

    it('can retrieve logs', function () {
        Log::create([
            'id' => Str::uuid(),
            'event' => 'Test event',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->getJson('/api/auth/users/logs/get');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    '*' => ['id', 'event', 'created_at', 'created_at_human']
                ]
            ]);
    });
});
