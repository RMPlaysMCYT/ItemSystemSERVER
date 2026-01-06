<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'description', 
        'price',
        'quantity',
        'user_id'
    ];
    public function user()
    {
        return $this->belongsTo(UserAccount::class, 'user_id');
    }
}