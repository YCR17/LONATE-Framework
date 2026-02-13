<?php

namespace App\Http\Controllers;

use Lonate\Core\Http\Request;
use Lonate\Core\Http\Response;
use App\Facades\Asset;

class SawitController
{
    public function reclassify(Request $request): Response
    {
        // PATCH /api/sawit/unlicensed
        
        // Satire: "Unlicensed to Enterprise Queue"
        Asset::mine('sawit')
            ->reclassify('unlicensed')
            ->legitimize() // Implicitly calls Policy
            ->queueForEnterprise();
            
        return new Response(json_encode([
            'luas' => '5jt ha',
            'status' => 'enterprise_queue',
            'note' => 'Hilirisasi in progress'
        ]), 200, ['Content-Type' => 'application/json']);
    }
}
