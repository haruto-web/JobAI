<?php

use Tests\TestCase;

class GoogleOAuthRedirectTest extends TestCase
{
    public function test_redirects_to_google()
    {
        $response = $this->get('/api/auth/google');

        $this->assertEquals(302, $response->getStatusCode());

        $location = $response->headers->get('Location');

        // For the redirect test we expect a Location header. Assert it exists and contains Google.
        $this->assertNotNull($location, 'Expected a Location header for redirect to Google OAuth');
        $this->assertStringContainsString('accounts.google.com', $location);
    }
}
