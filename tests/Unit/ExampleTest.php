<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTruncation;

    public function test_that_true_is_true()
    {
        $this->assertTrue(true);
    }
}
