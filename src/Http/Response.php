<?php

namespace Aksa\Http;

class Response
{
    protected $content;
    protected $statusCode;
    protected $headers;
    
    public function __construct($content = '', $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    
    public function getContent()
    {
        return $this->content;
    }

    // alias
    public function body()
    {
        return $this->getContent();
    }
    
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    public function header($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }

    public function headerValue(string $name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }

    public function ok()
    {
        $s = $this->getStatusCode();
        return $s >= 200 && $s < 300;
    }

    public function successful()
    {
        return $this->ok();
    }

    /**
     * Decode response JSON body. Use jsonData() to avoid collision with static Response::json()
     */
    public function jsonData($assoc = true)
    {
        $body = $this->getContent();
        if ($body === null || $body === '') return null;
        $decoded = json_decode($body, $assoc);
        return $decoded;
    }
    
    public function send()
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        echo $this->content;
        
        return $this;
    }
    
    public static function json($data, $statusCode = 200)
    {
        return new static(
            json_encode($data),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }
    
    public static function view($view, $data = [], $statusCode = 200)
    {
        $viewEngine = new \Aksa\View\ViewEngine();
        $content = $viewEngine->render($view, $data);
        
        return new static($content, $statusCode);
    }
    
    public static function redirect($url, $statusCode = 302)
    {
        return new static('', $statusCode, ['Location' => $url]);
    }
}
