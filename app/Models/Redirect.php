<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $path
 * @property string $target
 */
class Redirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'target',
    ];
}
