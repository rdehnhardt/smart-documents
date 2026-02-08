<?php

test('registration is disabled', function () {
    // Registration has been disabled - users are created by admins
    $response = $this->get('/register');

    $response->assertNotFound();
});
