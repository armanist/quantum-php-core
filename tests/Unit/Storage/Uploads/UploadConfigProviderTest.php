<?php

namespace Quantum\Tests\Unit\Storage\Uploads;

use Quantum\Storage\Uploads\UploadConfigProvider;
use Quantum\Tests\Unit\AppTestCase;

class UploadConfigProviderTest extends AppTestCase
{
    public function testReturnsConfiguredAllowedMimeTypesMap(): void
    {
        config()->set('uploads', ['allowed_mime_types' => ['text/plain' => ['txt']]]);

        $provider = new UploadConfigProvider();
        $map = $provider->getAllowedMimeTypesMap();

        $this->assertSame(['text/plain' => ['txt']], $map);
    }

    public function testThrowsWhenConfiguredMimeTypesAreInvalid(): void
    {
        config()->set('uploads', ['allowed_mime_types' => 'invalid']);

        $provider = new UploadConfigProvider();

        try {
            $provider->getAllowedMimeTypesMap();
            $this->fail('Expected an exception for invalid uploads.allowed_mime_types config');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('uploads', $e->getMessage());
        }
    }
}
