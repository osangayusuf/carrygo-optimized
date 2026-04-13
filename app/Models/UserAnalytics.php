<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['msisdn', 'action', 'action_id', 'activity_date'])]
class UserAnalytics extends Model
{
    protected $table = 'carrygo_user_analytics';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'activity_date' => 'datetime',
        ];
    }
}
