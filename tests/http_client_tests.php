<?php

use MiniLaravel\Http\Client;
use MiniLaravel\Http\Response as HttpResponse;

// Simple unit tests for HTTP client facade/helper (no external network calls)

// 1) helper and alias return proper instance
$ok = true;
$client = http();
if (!($client instanceof Client)) {
    echo "http() helper did not return Client instance\n";
    $ok = false;
}

// alias class (Http) should exist and be instantiable
if (!class_exists('Http')) {
    echo "Http alias class not found\n";
    $ok = false;
} else {
    $alias = new Http();
    if (!($alias instanceof Client)) {
        echo "Http alias is not instance of Client\n";
        $ok = false;
    }
}

// 2) API methods are fluent and return Response when request() is overridden in a stub
class TestClientStub extends Client {
    public $last = [];
    public function request(string $method, string $url, array $options = [])
    {
        $this->last = compact('method', 'url', 'options');
        return new HttpResponse('{"stub":true}', 200, ['Content-Type' => 'application/json']);
    }
}

$stub = new TestClientStub();
$res = $stub->json('http://example.test/api', ['a' => 1]);
if (!($res instanceof HttpResponse)) {
    echo "json() did not return HttpResponse\n";
    $ok = false;
}

// response instance JSON decode uses jsonData()
$decoded = $res->jsonData();
if (!is_array($decoded) || $decoded['stub'] !== true) {
    echo "Response::jsonData() did not decode stub response\n";
    $ok = false;
}
$res2 = $stub->post('http://example.test/submit', ['x' => 'y']);
if (!($res2 instanceof HttpResponse)) {
    echo "post() did not return HttpResponse\n";
    $ok = false;
}

$res3 = $stub->get('http://example.test/');
if (!($res3 instanceof HttpResponse)) {
    echo "get() did not return HttpResponse\n";
    $ok = false;
}

// verify stub captured options for json() -> should set Content-Type via request handling in client
$last = $stub->last;
if ($last['method'] !== 'POST' || strpos($last['url'], 'example.test') === false) {
    echo "Stub did not capture expected request data\n";
    $ok = false;
}

return $ok;