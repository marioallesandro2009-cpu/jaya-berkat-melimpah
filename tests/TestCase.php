<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Favicon uploads rebuild favicon.ico: never overwrite the real public/favicon.ico.
        config(['site.favicon_ico_path' => sys_get_temp_dir().'/jbm-test-favicon-'.getmypid().'.ico']);

        // Seeded photos are downscaled copies (same names): full-size WebP conversions are slow.
        config(['site.seed_image_max_width' => 480]);
    }
}
