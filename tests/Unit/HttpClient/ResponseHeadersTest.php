<?php

namespace Quantum\Tests\Unit\HttpClient;

use Quantum\HttpClient\ResponseHeaders;
use Quantum\Tests\Unit\AppTestCase;

class ResponseHeadersTest extends AppTestCase
{
    public function testResponseHeadersProvidesCaseInsensitiveAccess(): void
    {
        $headers = new ResponseHeaders(['Content-Type' => 'application/json']);

        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('application/json', $headers['CONTENT-TYPE']);
        $this->assertTrue(isset($headers['Content-Type']));
    }

    public function testResponseHeadersPreservesLatestOriginalKeyWhenIterating(): void
    {
        $headers = new ResponseHeaders(['Content-Type' => 'application/json']);
        $headers['content-type'] = 'text/plain';

        $this->assertSame(['content-type' => 'text/plain'], iterator_to_array($headers));
    }

    public function testResponseHeadersCanUnsetCaseInsensitively(): void
    {
        $headers = new ResponseHeaders(['Content-Type' => 'application/json']);

        unset($headers['content-type']);

        $this->assertCount(0, $headers);
        $this->assertNull($headers['Content-Type']);
    }
}
