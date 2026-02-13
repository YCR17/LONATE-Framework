<?php

namespace Lonate\Core\Trade;

class Accountability
{
    public function log(): self
    {
        return $this;
    }
    
    public function withBoardResolutionID(string $id = null): string
    {
        return "Logged with Resolution ID: " . ($id ?? '8/2026');
    }
}
