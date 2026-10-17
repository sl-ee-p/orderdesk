<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Assets are built by Vite, which is not needed to test server-side behaviour.
        $this->withoutVite();
    }
}
