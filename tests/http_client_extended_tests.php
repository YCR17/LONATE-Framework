<?php

use MiniLaravel\Http\Client;
use MiniLaravel\Http\Response as HttpResponse;

// Extended tests for HTTP client features: retry, throw, response helpers
$ok = true;

// 1) Response helpers
$res = new HttpResponse('{"a":1}', 201, ['X-Foo' => 'bar']);
if (!$res->ok() || !$res->successful() || $res->getStatusCode() !== 201) {
    echo "Response helper methods failed\n";
    $ok = false;
}
$decoded = $res->jsonData();
if (!is_array($decoded) || $decoded['a'] !== 1) {
    echo "Response::jsonData() failed\n";
    $ok = false;
}
if ($res->headerValue('X-Foo') !== 'bar') {
    echo "Response::headerValue failed\n";
    $ok = false;
}

// 2) retry behaviour: simulate first failure then success
class RetryStub extends Client {
    public $calls = 0;
    protected function doRequest(string $method, string $url, array $headers, $body = null, array $options = [])
    {
        $this->calls++;
        if ($this->calls === 1) {
            return new HttpResponse('error', 500, []);
        }
        return new HttpResponse('{"ok":true}', 200, ['Content-Type' => 'application/json']);
    }
}

$stub = new RetryStub();
$stub->retry(2, 0, function ($attempt, $response) {
    // retry if status >= 500
    return $response->getStatusCode() >= 500;
});

$res = $stub->post('http://example.test/');
if ($stub->calls !== 2 || !$res->ok()) {
    echo "Retry logic did not work as expected\n";
    $ok = false;
}

// 3) throw() should raise on 4xx/5xx
class ThrowStub extends Client {
    protected function doRequest(string $method, string $url, array $headers, $body = null, array $options = [])
    {
        return new HttpResponse('bad', 502, []);
    }
}

$th = new ThrowStub();
$th->retry(1);
$th->throw();
$raised = false;
try {
    $th->get('http://example.test/');
} catch (\Throwable $e) {
    $raised = true;
}
if (!$raised) {
    echo "throw() did not raise on HTTP error\n";
    $ok = false;
}

return $ok;