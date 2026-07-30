<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'user_id',
        'full_name',
        'client_type',
        'phone',
        'email',
        'status',
        'comment',
        'file_path',
        'file_paths',
        'link',
    ];

    public function objects()
    {
        return $this->hasMany(PassportObject::class);
    }

    public function projects()
    {
        return $this->hasManyThrough(Project::class, PassportObject::class, 'client_id', 'object_id');
    }
}
