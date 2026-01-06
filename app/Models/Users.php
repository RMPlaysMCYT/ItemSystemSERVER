<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Users extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users_accounts'; // Point to your actual table
    
    protected $fillable = [
        'username',
        'email',
        'password',
        'phone_number',
        'role',
        'is_suspended',
        'suspension_note',
        'suspended_until'
    ];
}