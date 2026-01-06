<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'is_approved'
    ];
    public function user()
    {
        return $this->belongsTo(UserAccount::class);
    }
}
