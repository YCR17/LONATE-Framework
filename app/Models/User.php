<?php

namespace App\Models;

use Lonate\Core\Database\Model;

class User extends Model
{
    protected ?string $table = 'users';
    
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];
    
    protected array $guarded = [];
}
