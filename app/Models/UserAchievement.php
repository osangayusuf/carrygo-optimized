<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['msisdn', 'achievement_key', 'progress', 'completed_at'])]
#[Table('carrygo_user_achievements')]
class UserAchievement extends Model
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'progress' => 'integer',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
