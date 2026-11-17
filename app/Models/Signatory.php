<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signatory extends Model
{
    protected $fillable = ['name', 'position', 'role_key', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}