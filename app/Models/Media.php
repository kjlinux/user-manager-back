<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Media extends Model
{
    use HasUuids;

    protected $guarded = ['id'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
