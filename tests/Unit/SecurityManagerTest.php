<?php

namespace FarhanShares\MediaMan\Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use FarhanShares\MediaMan\Security\SecurityManager;
use FarhanShares\MediaMan\Exceptions\SecurityException;

class SecurityManagerTest extends TestCase
{
    /** @test */
    public function it_sanitizes_filenames()
    {
        $manager = new SecurityManager();

        $sanitized = $manager->sanitizeFilename('../../../etc/passwd.jpg');

        $this->assertStringNotContainsString('..', $sanitized);
        $this->assertStringNotContainsString('/', $sanitized);
    }

    /** @test */
    public function it_validates_file_size()
    {
        config(['mediaman.max_file_size' => 1024]);

        $manager = new SecurityManager();

        $smallFile = UploadedFile::fake()->image('small.jpg')->size(500);
        $this->assertTrue($manager->validateFileSize($smallFile));

        $largeFile = UploadedFile::fake()->image('large.jpg')->size(2048);
        $this->assertFalse($manager->validateFileSize($largeFile));
    }

    /** @test */
    public function it_generates_signed_urls()
    {
        config(['mediaman.signed_urls' => true]);

        $manager = new SecurityManager();
        $url = 'https://example.com/media/test.jpg';

        $signedUrl = $manager->signUrl($url, 60);

        $this->assertStringContainsString('expires=', $signedUrl);
        $this->assertStringContainsString('signature=', $signedUrl);
    }

    /** @test */
    public function it_verifies_signed_urls()
    {
        config(['mediaman.signed_urls' => true]);

        $manager = new SecurityManager();
        $url = 'https://example.com/media/test.jpg';

        $signedUrl = $manager->signUrl($url, 60);

        // Extract parameters
        parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

        $this->assertTrue(
            $manager->verifySignedUrl($url, $params['signature'], $params['expires'])
        );
    }
}
