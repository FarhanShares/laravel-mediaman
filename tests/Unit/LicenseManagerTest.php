<?php

namespace FarhanShares\MediaMan\Tests\Unit;

use Tests\TestCase;
use FarhanShares\MediaMan\UI\License\LicenseManager;

class LicenseManagerTest extends TestCase
{
    /** @test */
    public function it_detects_localhost()
    {
        $this->app['request']->server->set('SERVER_NAME', 'localhost');

        $manager = new LicenseManager();

        $this->assertTrue($manager->isLocalhost());
    }

    /** @test */
    public function it_allows_all_features_on_localhost()
    {
        $this->app['request']->server->set('SERVER_NAME', 'localhost');

        $manager = new LicenseManager();

        $this->assertTrue($manager->hasFeature('ui'));
        $this->assertTrue($manager->hasFeature('chunked_upload'));
        $this->assertTrue($manager->hasFeature('any_feature'));
    }

    /** @test */
    public function it_validates_license_in_production()
    {
        config(['app.env' => 'production']);
        config(['mediaman.license_key' => null]);

        $this->app['request']->server->set('SERVER_NAME', 'example.com');

        $manager = new LicenseManager();

        $this->assertFalse($manager->validate());
    }
}
