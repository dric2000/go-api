<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $fillable = ['name', 'email', 'phone', 'password', 'is_admin'];
    protected $casts = ['is_admin' => 'boolean'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_identity_verified' => 'boolean'
        ];
    }

    public function isAdmin():bool{
        return $this->is_admin; 
    }

    public function identityDocument() {
        return $this->hasOne(IdentityDocument::class); 
    }

    public function driverProfile() {
        return $this->hasOne(DriverProfile::class); 
    }

    public function travelerProfile() {
        return $this->hasOne(TravelerProfile::class);
    }
}
