<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityDocument extends Model
{
    protected $fillable = ['user_id', 'file_path', 'document_type', 'status_verification'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
