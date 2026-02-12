<?php

namespace App\Models;

use Aksa\Database\Model;

class User extends Model
{
    // Table name (optional, defaults to 'users')
    protected $table = 'users';
    
    // Primary key (optional, defaults to 'id')
    protected $primaryKey = 'id';
    
    // Mass assignable attributes
    protected $fillable = [
        'name',
        'email',
        'password'
    ];
    
    // Attributes that should be hidden
    protected $guarded = [];
    
    
    // Example relationship (if you want to add relations later)
    // public function role()
    // {
    //     // This would require implementing relationships
    //     // For now, just a placeholder
    // }
}
