<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A Formation represents a regional or organizational unit
 * that groups several personnel.
 */
class Formation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'type', 'parent_id'];

    /**
     * One Formation has many Users (Personnel).
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function offices()
    {
        return $this->hasMany(Office::class);
    }

    public function parent()
    {
        return $this->belongsTo(Formation::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Formation::class, 'parent_id');
    }
}
