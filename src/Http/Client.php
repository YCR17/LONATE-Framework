<?php

namespace MiniLaravel\Http;

class Client
{
    protected $defaultHeaders = [];
    protected $timeout = 30;
    protected $options = [];

    // per-request flags
    protected $shouldThrow = false;
    protected $retryTimes = 0;
    protected $retrySleep = 0; // ms
    protected $retryWhen = null; // callable|null

    public function withHeaders(array $headers)
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    public function header(string $name, string $value)
    {
        $this->defaultHeaders[$name] = $value;
        return $this;
    }

    public function withOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function timeout(int $seconds)
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function asJson()
    {
        $this->header('Content-Type', 'application/json');
        return $this;
    }

    public function asForm()
    {
        $this->header('Content-Type', 'application/x-www-form-urlencoded');
        return $this;
    }

    public function acceptJson()
    {
        $this->header('Accept', 'application/json');
        return $this;
    }

    public function retry(int $times, int $sleepMs = 0, callable $when = null)
    {
        $this->retryTimes = $times;
        $this->retrySleep = $sleepMs;
        $this->retryWhen = $when;
        return $this;
    }

    public function throw()
    {
        $this->shouldThrow = true;
        return $this;
    }

    public function get(string $url, array $query = [], array $options = [])
    {
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $data = [], array $options = [])
    {
        $options['form_params'] = $data;
        return $this->request('POST', $url, $options);
    }

    public function json(string $url, array $data = [], array $options = [])
    {
        $options['json'] = $data;
        return $this->request('POST', $url, $options);
    }

    public function request(string $method, string $url, array $options = [])
    {
        $headers = $this->defaultHeaders;
        $opts = array_merge($this->options, $options);

        $body = null;
        if (isset($opts['json'])) {
            $body = json_encode($opts['json']);
            $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
        } elseif (isset($opts['form_params'])) {
            $body = http_build_query($opts['form_params']);
            $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/x-www-form-urlencoded';
        } elseif (isset($opts['body'])) {
            $body = $opts['body'];
        }

        $attempt = 0;
        $lastException = null;
        $responses = [];

        $times = max(1, $this->retryTimes ?: 1);
        for ($attempt = 1; $attempt <= $times; $attempt++) {
            try {
                $response = $this->doRequest($method, $url, $headers, $body, $opts);
                $responses[] = $response;

                // check retry-when callback
                if ($this->retryWhen && is_callable($this->retryWhen)) {
                    $shouldRetry = call_user_func($this->retryWhen, $attempt, $response);
                    if ($shouldRetry && $attempt < $times) {
                        if ($this->retrySleep) {
                            usleep($this->retrySleep * 1000);
                        }
                        continue;
                    }
                }

                // if not retrying, check for throw-on-error
                if ($this->shouldThrow && $response->getStatusCode() >= 400) {
                    throw new \RuntimeException('HTTP request failed with status ' . $response->getStatusCode());
                }

                // success or non-throwing
                return $response;
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($attempt < $times) {
                    if ($this->retrySleep) {
                        usleep($this->retrySleep * 1000);
                    }
                    continue;
                }
                throw $e;
            }
        }

        // fallback: return last response if any, otherwise rethrow
        if (!empty($responses)) {
            return end($responses);
        }

        if ($lastException) {
            throw $lastException;
        }

        return new Response(null, 0, []);
    }

    protected function doRequest(string $method, string $url, array $headers, $body = null, array $options = [])
    {
        if (!function_exists('curl_init')) {
            $ctxOptions = [
                'http' => [
                    'method' => $method,
                    'header' => $this->formatHeaders($headers),
                    'timeout' => $this->timeout,
                ]
            ];

            if ($body !== null) {
                $ctxOptions['http']['content'] = $body;
            }

            $context = stream_context_create($ctxOptions);
            $response = @file_get_contents($url, false, $context);
            $status = 200;
            if (isset($http_response_header) && preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }

            return new Response($response === false ? null : $response, $status, $this->parseHeadersFromTransport($http_response_header ?? []));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if (!empty($headers)) {
            $curlHeaders = [];
            foreach ($headers as $k => $v) $curlHeaders[] = $k . ': ' . $v;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        }

        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($resp === false && $curlErr) {
            throw new \RuntimeException('HTTP client error: ' . $curlErr);
        }

        return new Response($resp, $status, []);
    }

    protected function formatHeaders(array $headers)
    {
        $lines = [];
        foreach ($headers as $k => $v) $lines[] = "{$k}: {$v}";
        return implode("\r\n", $lines) . "\r\n";
    }

    protected function parseHeadersFromTransport(array $transport)
    {
        $out = [];
        foreach ($transport as $line) {
            if (strpos($line, ':') !== false) {
                list($k, $v) = explode(':', $line, 2);
                $out[trim($k)] = trim($v);
            }
        }
        return $out;
    }
}
