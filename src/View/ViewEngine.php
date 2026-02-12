<?php

namespace Aksa\View;

class ViewEngine
{
    protected $viewPath;
    protected $cachePath;
    protected $parent = null;
    protected $sections = [];
    protected $sectionStack = [];
    protected $isRendering = false;
    
    public function __construct()
    {
        $basePath = dirname(__DIR__, 2);
        $this->viewPath = $basePath . '/resources/views';
        $this->cachePath = $basePath . '/storage/cache/views';
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public function render($view, $data = [])
    {
        $viewFile = $this->viewPath . '/' . str_replace('.', '/', $view) . '.aksa.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View {$view} not found");
        }
        
        $cachedFile = $this->getCachedFile($viewFile);
        
        if (!file_exists($cachedFile) || filemtime($viewFile) > filemtime($cachedFile)) {
            $compiled = $this->compile(file_get_contents($viewFile));
            file_put_contents($cachedFile, $compiled);
        }
        
        // Only reset sections when starting a top-level render call
        if (!$this->isRendering) {
            $this->sections = [];
            $this->sectionStack = [];
            $this->parent = null;
            $this->isRendering = true;
        } else {
            // for nested (parent) rendering keep existing sections and clear only stack
            $this->sectionStack = [];
            $this->parent = null;
        }

        $output = $this->evaluatePath($cachedFile, $data);

        if ($this->parent) {
            $parentView = $this->parent;
            $result = $this->render($parentView, $data);
            // only clear rendering flag when unwinding back to top-level
            if ($this->isRendering && $parentView === $view) {
                $this->isRendering = false;
            }
            return $result;
        }

        // finished top-level render
        $this->isRendering = false;

        return $output;
    }
    
    protected function getCachedFile($viewFile)
    {
        return $this->cachePath . '/' . md5($viewFile) . '.php';
    }

    protected function evaluatePath($file, $data)
    {
        extract($data);
        ob_start();
        include $file;
        return ob_get_clean();
    }
    
    protected function compile($content)
    {
        // @extends
        $content = preg_replace('/@extends\([\'"](.+)[\'"]\)/', '<?php $this->extend("$1"); ?>', $content);
        
        // @section
        $content = preg_replace('/@section\([\'"](.+)[\'"]\)/', '<?php $this->startSection("$1"); ?>', $content);
        
        // @endsection
        $content = preg_replace('/@endsection/', '<?php $this->endSection(); ?>', $content);
        
        // @yield
        $content = preg_replace('/@yield\([\'"](.+)[\'"]\)/', '<?php echo $this->yieldSection("$1"); ?>', $content);
        
        // @if
        $content = preg_replace('/@if\((.+)\)/', '<?php if($1): ?>', $content);
        
        // @elseif
        $content = preg_replace('/@elseif\((.+)\)/', '<?php elseif($1): ?>', $content);
        
        // @else
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        
        // @endif
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);
        
        // @foreach
        $content = preg_replace('/@foreach\((.+)\)/', '<?php foreach($1): ?>', $content);
        
        // @endforeach
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        
        // @for
        $content = preg_replace('/@for\((.+)\)/', '<?php for($1): ?>', $content);
        
        // @endfor
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);
        
        // @while
        $content = preg_replace('/@while\((.+)\)/', '<?php while($1): ?>', $content);
        
        // @endwhile
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);
        
        // {{ $variable }} - escaped
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);
        
        // {!! $variable !!} - unescaped
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
        
        // @php
        $content = preg_replace('/@php/', '<?php', $content);
        
        // @endphp
        $content = preg_replace('/@endphp/', '?>', $content);
        
        // @include
        $content = preg_replace('/@include\([\'"](.+)[\'"]\)/', '<?php echo (new \\Aksa\\View\\ViewEngine())->render("$1", get_defined_vars()); ?>', $content);
                // @csrf -> echo CSRF hidden input
        $content = preg_replace('/@csrf/', '<?php echo csrf_field(); ?>', $content);

        // @method('PUT') -> hidden method spoofing input
        $content = preg_replace('/@method\([\'\"](.+)[\'\"]\)/', '<?php echo "<input type=\"hidden\" name=\"_method\" value=\"$1\">"; ?>', $content);
        return $content;
    }
    // Aksa-style section handling (compatible with previous Blade-like directives)
    public function extend($view)
    {
        $this->parent = $view;
    }

    public function startSection($name)
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection()
    {
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    public function yieldSection($name)
    {
        return $this->sections[$name] ?? '';
    }}
