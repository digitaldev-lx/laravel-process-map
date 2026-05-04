<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'leads';

    protected $fillable = ['name', 'email', 'status'];

    protected $casts = [
        'status' => 'string',
    ];
}
