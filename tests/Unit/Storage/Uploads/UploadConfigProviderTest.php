<?php

namespace Quantum\Tests\Unit\Storage\Uploads;

use Quantum\Storage\Uploads\UploadConfigProvider;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\AppContext;
use Quantum\App\App;

class UploadConfigProviderTest extends AppTestCase
{
    public function testLoadsSharedUploadConfigWhenNotPreloaded(): void
    {
        config()->flush();

        $map = (new UploadConfigProvider())->getAllowedMimeTypesMap();

        $this->assertSame(['jpg', 'jpeg'], $map['image/jpeg']);
        $this->assertTrue(config()->has('uploads'));
    }

    public function testMissingOptionalUploadConfigReturnsEmptyMap(): void
    {
        config()->flush();
        $context = App::getContext();

        try {
            App::setContext(new AppContext(PROJECT_ROOT . DS . 'cron-command-tests-empty', $context->getContainer()));

            $this->assertSame([], (new UploadConfigProvider())->getAllowedMimeTypesMap());
            $this->assertFalse(config()->has('uploads'));
        } finally {
            App::setContext($context);
        }
    }

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
