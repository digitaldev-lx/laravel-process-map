<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractEntity extends Model
{
    abstract public function entityName(): string;
}
