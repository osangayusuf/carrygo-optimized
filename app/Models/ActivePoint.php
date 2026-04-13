<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['msisdn', 'points'])]
#[Table('carrygo_active_points')]
class ActivePoint extends Model
{
    public const CREATED_AT = null;

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}
