<?php

namespace Quantum\Tests\Unit\Http;

use Quantum\Libraries\Storage\Exceptions\FileUploadException;
use Quantum\Libraries\Session\Factories\SessionFactory;
use Quantum\Libraries\Storage\UploadedFile;
use Quantum\Http\Request\HttpRequest;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Request;
use Mockery;

class RequestTest extends AppTestCase
{

    private $session;

    public function setUp(): void
    {
        parent::setUp();

        $this->session = SessionFactory::get();

        $server = Mockery::mock('Quantum\Environment\Server');

        $server->shouldReceive('method')->andReturn('GET');

        $server->shouldReceive('method')->andReturn('GET');
    }

    public function tearDown(): void
    {
        HttpRequest::flush();
    }

    public function testSetGetMethod()
    {
        $request = new Request();

        $request->create('GET', '/');

        $this->assertEquals('GET', $request->getMethod());

        $request->setMethod('POST');

        $this->assertEquals('POST', $request->getMethod());
    }

    public function testIsMethod()
    {
        $request = new Request();

        $request->create('GET', '/');

        $this->assertTrue($request->isMethod('GET'));

        $this->assertTrue($request->isMethod('get'));

        $this->assertFalse($request->isMethod('POST'));

        $request->setMethod('POST');

        $this->assertTrue($request->isMethod('POST'));

        $this->assertTrue($request->isMethod('post'));
    }

    public function testSetGetProtocol()
    {
        $request = new Request();

        $request->create('GET', 'https://test.com');

        $this->assertEquals('https', $request->getProtocol());

        $request->setProtocol('http');

        $this->assertEquals('http', $request->getProtocol());
    }

    public function testSetGetHost()
    {
        $request = new Request();

        $request->create('GET', 'https://test.com/dashboard');

        $this->assertEquals('test.com', $request->getHost());

        $request->setHost('tester.com');

        $this->assertEquals('tester.com', $request->getHost());
    }

    public function testSetGetPort()
    {
        $request = new Request();

        $request->create('GET', 'https://test.com:8080/dashboard');

        $this->assertEquals('8080', $request->getPort());

        $request->setPort('9000');

        $this->assertEquals('9000', $request->getPort());
    }

    public function testSetGetUri()
    {
        $request = new Request();

        $request->create('GET', 'http://test.com/post/12');

        $this->assertEquals('post/12', $request->getUri());

        $request->setUri('post/edit/12');

        $this->assertEquals('post/edit/12', $request->getUri());
    }

    public function testSetGetQuery()
    {
        $request = new Request();

        $request->create('GET', 'http://test.com:8080/user?firstname=john&lastname=doe');

        $this->assertEquals('firstname=john&lastname=doe', $request->getQuery());

        $request->create('GET', 'http://test.com:8080/?firstname=john&lastname=doe');

        $this->assertEquals('firstname=john&lastname=doe', $request->getQuery());

        $request->setQuery('age=30&gender=male');

        $this->assertEquals('age=30&gender=male', $request->getQuery());
    }

    public function testRequestSetHasGetDelete()
    {
        $request = new Request();

        $this->assertFalse($request->has('name'));

        $request->set('name', 'John');

        $this->assertTrue($request->has('name'));

        $this->assertEquals('John', $request->get('name'));

        $request->delete('name');

        $this->assertNotEquals('John', $request->get('name'));

        $request->create('POST', '/', ['content' => '<h1>Big text</h1>']);

        $this->assertEquals('Big text', $request->get('content'));

        $this->assertEquals('<h1>Big text</h1>', $request->get('content', null, true));

        $request->create('POST', '/', ['content' => ['status' => 'ok', 'message' => '<h1>Big text</h1>']]);

        $content = $request->get('content');

        $this->assertEquals('Big text', $content['message']);

        $content = $request->get('content', null, true);

        $this->assertEquals('<h1>Big text</h1>', $content['message']);
    }

    public function testRequestAll()
    {
        $request = new Request();

        $this->assertEmpty($request->all());

        $file = [
            'image' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => __FILE__ . 'php8fe1.tmp',
                'type' => 'image/jpg',
                'error' => 0,
            ],
        ];

        $request->create('POST', '/upload', ['name' => 'John'], $file);

        $request->set('name', 'John');

        $this->assertNotEmpty($request->all());

        $this->assertIsArray($request->all());
    }

    public function testHasGetFile()
    {
        $request = new Request();

        $file = [
            'image' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => '/tmp/php8fe2.tmp',
                'type' => 'image/jpg',
                'error' => 0,
            ],
        ];

        $request->create('POST', '/upload', null, $file);

        $this->assertTrue($request->hasFile('image'));

        $this->assertInstanceOf(UploadedFile::class, $request->getFile('image'));

        $fileWithError = [
            'image' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => '/tmp/php8fe2.tmp',
                'type' => 'image/jpg',
                'error' => 4,
            ],
        ];

        $request->create('POST', '/upload', null, $fileWithError);

        $this->assertFalse($request->hasFile('image'));

        $this->expectException(FileUploadException::class);

        $this->expectExceptionMessage('The file `image` not found');

        $request->getFile('image');
    }

    public function testGetMultipleFiles()
    {
        $request = new Request();

        $this->assertFalse($request->hasFile('image'));

        $files = [
            'image' => [
                'size' => [500, 800],
                'name' => ['foo.jpg', 'bar.png'],
                'tmp_name' => ['/tmp/php8fe2.tmp', '/tmp/php8fe3.tmp'],
                'type' => ['image/jpg', 'image/png'],
                'error' => [0, 0],
            ],
        ];

        $request->create('POST', '/upload', null, $files);

        $this->assertTrue($request->hasFile('image'));

        $image = $request->getFile('image');

        $this->assertIsArray($image);

        $this->assertInstanceOf(UploadedFile::class, $image[0]);

        $this->assertEquals('foo.jpg', $image[0]->getNameWithExtension());

        $this->assertEquals('bar.png', $image[1]->getNameWithExtension());
    }

    public function testRequestHeaderSetHasGetDelete()
    {
        $request = new Request();

        $this->assertFalse($request->hasHeader('name'));

        $request->setHeader('X-CUSTOM', 'Custom');

        $this->assertTrue($request->hasHeader('X-CUSTOM'));

        $this->assertEquals('Custom', $request->getHeader('X-CUSTOM'));

        $request->delete('X-CUSTOM');

        $this->assertNotEquals('Custom', $request->get('X-CUSTOM'));
    }

    public function testRequestHeaderAll()
    {
        $request = new Request();

        $this->assertEmpty($request->allHeaders());

        $request->setHeader('X-CUSTOM', 'Custom');

        $this->assertNotEmpty($request->allHeaders());

        $this->assertIsArray($request->allHeaders());
    }

    public function testGetSegments()
    {
        $request = new Request();

        $request->create('GET', 'post/12/notes');

        $this->assertIsArray($request->getAllSegments());

        $this->assertEquals('post', $request->getSegment(1));

        $this->assertEquals('12', $request->getSegment(2));

        $this->assertNull($request->getSegment(10));
    }

    public function testGetCsrfToken()
    {
        $request = new Request();

        $this->assertNull($request->getCsrfToken());

        $request->create('PATCH', '/', ['csrf-token' => csrf_token()]);

        $this->assertNotNull($request->getCsrfToken());

    }

    public function testGetAuthorizationBearer()
    {
        $request = new Request();

        $bearerToken = md5('random');

        $this->assertNull($request->getAuthorizationBearer());

        $request->setHeader('Authorization', 'Bearer ' . $bearerToken);

        $this->assertNotNull($request->getAuthorizationBearer());

        $this->assertEquals($bearerToken, $request->getAuthorizationBearer());
    }

    public function testGetBasicAuthCredentialsFromSuperGlobal()
    {
        $request = new Request();

        $credentials = [
            'username' => "testGlobalUsername",
            'password' => "testGlobalPassword",
        ];

        $this->assertNull($request->getBasicAuthCredentials());

        $_SERVER['PHP_AUTH_USER'] = $credentials['username'];

        $_SERVER['PHP_AUTH_PW'] = $credentials['password'];

        $result = $request->getBasicAuthCredentials();

        $this->assertNotNull($result);

        $this->assertEquals($credentials, $result);

        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    public function testGetBasicAuthCredentialsFromHeader()
    {
        $request = new Request();

        $username = 'testHeaderName';

        $password = 'testHeaderPassword';

        $credentials = base64_encode("$username:$password");

        $this->assertNull($request->getBasicAuthCredentials());

        $request->setHeader('Authorization', 'Basic ' . $credentials);

        $result = $request->getBasicAuthCredentials();

        $this->assertNotNull($result);

        $this->assertEquals($username, $result['username']);
        $this->assertEquals($password, $result['password']);
    }

    public function testIsAjax()
    {
        $request = new Request();

        $request->create('POST', '/save');

        $request->setHeader('X-REQUESTED-WITH', 'xmlhttprequest');

        $this->assertTrue($request->isAjax());
    }

    public function testSetGetQueryParam()
    {
        $request = new Request();

        $request->setQueryParam('name', 'John');

        $request->setQueryParam('age', 36);

        $this->assertEquals('John', $request->getQueryParam('name'));

        $this->assertEquals(36, $request->getQueryParam('age'));

        $this->assertEquals(null, $request->getQueryParam('otherKey'));

        $this->assertEquals('name=John&age=36', $request->getQuery());

        $request->setQuery('phone=055090607&email=test@test.com');

        $this->assertEquals('test@test.com', $request->getQueryParam('email'));
    }

}
