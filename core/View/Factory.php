<?php

namespace Lonate\Core\View;

/**
 * Class Factory
 * 
 * Simple view rendering engine that supports:
 * - Standard .php templates
 * - .aksa.php templates (framework convention)
 * - Basic @extends / @section / @yield directives
 * 
 * @package Lonate\Core\View
 */
class Factory
{
    protected string $viewPath;

    public function __construct(string $viewPath = '')
    {
        if (empty($viewPath)) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
            $this->viewPath = $basePath . '/resources/views';
        } else {
            $this->viewPath = $viewPath;
        }
    }

    /**
     * Render a view template with data.
     * 
     * @param string $view Dot-notation view name (e.g., 'welcome' or 'pages.home')
     * @param array $data Variables to extract into the view scope
     * @return string Rendered HTML
     * @throws \RuntimeException If view file is not found
     */
    public function make(string $view, array $data = []): string
    {
        $path = $this->resolvePath($view);

        if (!$path) {
            throw new \RuntimeException("View [{$view}] not found.");
        }

        // Render the view
        $content = $this->renderFile($path, $data);

        // Process @extends/@section/@yield directives
        $content = $this->processDirectives($content, $data);

        return $content;
    }

    /**
     * Resolve a dot-notation view name to an absolute file path.
     * Priority: .aksa.php > .php
     *
     * @param string $view
     * @return string|null
     */
    protected function resolvePath(string $view): ?string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $view);
        
        // Try .aksa.php first (framework convention), then .php
        $candidates = [
            $this->viewPath . '/' . $relative . '.aksa.php',
            $this->viewPath . '/' . $relative . '.php',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Render a PHP file with extracted data.
     *
     * @param string $path
     * @param array $data
     * @return string
     */
    protected function renderFile(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Process template directives: @extends, @section, @yield.
     * 
     * @param string $content
     * @param array $data
     * @return string
     */
    protected function processDirectives(string $content, array $data): string
    {
        // Check for @extends('layout_name')
        if (preg_match('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', $content, $m)) {
            $layoutName = $m[1];
            
            // Remove the @extends directive from content
            $content = preg_replace('/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/', '', $content);
            
            // Extract all @section('name') ... @endsection blocks
            $sections = [];
            preg_match_all(
                '/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endsection/s',
                $content,
                $matches,
                PREG_SET_ORDER
            );
            
            foreach ($matches as $match) {
                $sections[$match[1]] = trim($match[2]);
            }
            
            // Load the layout
            $layoutPath = $this->resolvePath($layoutName);
            if (!$layoutPath) {
                return $content; // Fallback if layout not found
            }
            
            $layout = $this->renderFile($layoutPath, $data);
            
            // Replace @yield('name') with section content
            $layout = preg_replace_callback(
                '/@yield\s*\(\s*[\'"](.+?)[\'"]\s*\)/',
                function ($m) use ($sections) {
                    return $sections[$m[1]] ?? '';
                },
                $layout
            );
            
            return $layout;
        }

        return $content;
    }
}
