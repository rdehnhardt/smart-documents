<?php

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

test('non-admin users cannot access user management', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $response = $this->get(route('users.index'));

    $response->assertForbidden();
});

test('admin can view users index', function () {
    $response = $this->get(route('users.index'));

    $response->assertOk();
});

test('admin can view create user page', function () {
    $response = $this->get(route('users.create'));

    $response->assertOk();
});

test('admin can create new user', function () {
    $response = $this->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'is_admin' => false,
    ]);
});

test('admin can create another admin', function () {
    $response = $this->post(route('users.store'), [
        'name' => 'New Admin',
        'email' => 'newadmin@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
        'is_admin' => true,
    ]);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'newadmin@example.com',
        'is_admin' => true,
    ]);
});

test('admin can view edit user page', function () {
    $user = User::factory()->create();

    $response = $this->get(route('users.edit', $user));

    $response->assertOk();
});

test('admin can update user', function () {
    $user = User::factory()->create();

    $response = $this->patch(route('users.update', $user), [
        'name' => 'Updated Name',
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
    ]);
});

test('admin can update user password', function () {
    $user = User::factory()->create();

    $response = $this->put(route('users.update-password', $user), [
        'password' => 'NewPassword1',
        'password_confirmation' => 'NewPassword1',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('admin can delete user', function () {
    $user = User::factory()->create();

    $response = $this->delete(route('users.destroy', $user));

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('admin cannot delete themselves', function () {
    $currentUser = auth()->user();

    $response = $this->delete(route('users.destroy', $currentUser));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('users', [
        'id' => $currentUser->id,
    ]);
});

test('admin cannot remove their own admin status', function () {
    $currentUser = auth()->user();

    $response = $this->patch(route('users.update', $currentUser), [
        'name' => $currentUser->name,
        'email' => $currentUser->email,
        'is_admin' => false,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('users', [
        'id' => $currentUser->id,
        'is_admin' => true,
    ]);
});
