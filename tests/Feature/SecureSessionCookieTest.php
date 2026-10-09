<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecureSessionCookieTest extends TestCase
{
    public function test_session_cookie_is_secure_when_secure_cookie_config_is_enabled(): void
    {
        config(['session.secure' => true]);

        $response = $this->get(route('login'));

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertTrue(config('session.http_only'));
    }
}
