<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_avatar_url_column_accepts_long_urls(): void
    {
        $this->assertSame('text', Schema::getColumnType('users', 'google_avatar_url'));
    }
}
