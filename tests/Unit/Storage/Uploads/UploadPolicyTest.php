<?php

namespace Quantum\Tests\Unit\Storage\Uploads;

use Quantum\Storage\Uploads\UploadPolicy;
use Quantum\Tests\Unit\AppTestCase;

class UploadPolicyTest extends AppTestCase
{
    public function testIsAllowedWithDefaultMap(): void
    {
        $policy = new UploadPolicy([
            'image/jpeg' => ['jpg', 'jpeg'],
        ]);

        $this->assertTrue($policy->isAllowed('jpg', 'image/jpeg'));
        $this->assertFalse($policy->isAllowed('png', 'image/jpeg'));
    }

    public function testMergeAddsMoreAllowedEntries(): void
    {
        $policy = new UploadPolicy([
            'image/jpeg' => ['jpg'],
        ]);

        $policy->merge([
            'image/jpeg' => ['jpeg'],
            'image/png' => ['png'],
        ]);

        $this->assertTrue($policy->isAllowed('jpeg', 'image/jpeg'));
        $this->assertTrue($policy->isAllowed('png', 'image/png'));
    }

    public function testReplaceOverridesExistingRules(): void
    {
        $policy = new UploadPolicy([
            'image/jpeg' => ['jpg'],
        ]);

        $policy->replace([
            'text/plain' => ['txt'],
        ]);

        $this->assertFalse($policy->isAllowed('jpg', 'image/jpeg'));
        $this->assertTrue($policy->isAllowed('txt', 'text/plain'));
    }
}
