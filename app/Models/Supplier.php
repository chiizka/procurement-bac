<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'address', 'tin', 'contact_no', 'vat_registered'];

    protected function casts(): array
    {
        return ['vat_registered' => 'boolean'];
    }
}