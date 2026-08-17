<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_security_headers_to_responses()
    {
        $response = $this->get('/');
        // dump($response->headers->all());
        $response->assertHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self' https://*.supabase.co wss://*.supabase.co;");
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_it_does_not_add_hsts_header_in_local_environment()
    {
        // By default, testing environment is 'testing', which is not 'production'
        $response = $this->get('/');
        
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_it_adds_hsts_header_in_production_environment()
    {
        $this->app['env'] = 'production';

        $response = $this->get('/');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
