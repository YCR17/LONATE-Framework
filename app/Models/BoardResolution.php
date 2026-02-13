<?php

namespace App\Models;

use Lonate\Core\Database\Model;

class BoardResolution extends Model
{
    protected ?string $table = 'resolutions';
    
    protected $fillable = ['year', 'number', 'title', 'quorum_present'];
}
