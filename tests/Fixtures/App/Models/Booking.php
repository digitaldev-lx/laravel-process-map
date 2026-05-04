<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = ['lead_id', 'slot', 'status'];

    public $timestamps = false;
}
