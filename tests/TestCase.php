<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // I test non devono mai scrivere documenti nello storage reale.
        Storage::fake(config('vestiario.documenti.disco'));
    }
}
