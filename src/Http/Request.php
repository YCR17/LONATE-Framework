<?php

namespace MiniLaravel\Http;

class Request
{
    protected $query;
    protected $request;
    protected $server;
    protected $files;
    protected $cookies;
    protected $headers;
    protected $routeParameters = [];
    
    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->getHeaders();
    }
    
    public static function capture()
    {
        return new static();
    }
    
    public function method()
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }
    
    public function uri()
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $uri;
    }
    
    public function path()
    {
        return $this->uri();
    }
    
    public function url()
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . $this->uri();
    }
    
    public function isSecure()
    {
        return !empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }
    
    public function input($key = null, $default = null)
    {
        $input = array_merge($this->query, $this->request);
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }
    
    public function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }
    
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request;
        }
        
        return $this->request[$key] ?? $default;
    }
    
    public function all()
    {
        return array_merge($this->query, $this->request);
    }
    
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = $this->all();
        $results = [];
        
        foreach ($keys as $key) {
            if (isset($input[$key])) {
                $results[$key] = $input[$key];
            }
        }
        
        return $results;
    }
    
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = $this->all();
        
        foreach ($keys as $key) {
            unset($input[$key]);
        }
        
        return $input;
    }
    
    public function has($key)
    {
        $input = $this->all();
        return isset($input[$key]);
    }
    
    public function filled($key)
    {
        return $this->has($key) && !empty($this->input($key));
    }
    
    public function file($key)
    {
        return $this->files[$key] ?? null;
    }
    
    public function hasFile($key)
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }
    
    public function header($key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        
        return $this->headers[$key] ?? $default;
    }
    
    protected function getHeaders()
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
    
    public function ip()
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    public function userAgent()
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }
    
    public function setRouteParameters($parameters)
    {
        $this->routeParameters = $parameters;
    }
    
    public function route($key = null, $default = null)
    {
        if ($key === null) {
            return $this->routeParameters;
        }
        
        return $this->routeParameters[$key] ?? $default;
    }
    
    public function isMethod($method)
    {
        return $this->method() === strtoupper($method);
    }
    
    public function ajax()
    {
        return $this->header('X-REQUESTED-WITH') === 'XMLHttpRequest';
    }
    
    public function json()
    {
        return json_decode(file_get_contents('php://input'), true);
    }
}
