<?php

namespace App\Models\File;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasUuids;

    protected $table = 'uploads';
    protected $fillable = ['user_id', 'filename', 'size', 'status'];
}
