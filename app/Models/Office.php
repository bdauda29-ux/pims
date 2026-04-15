<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $fillable = ['formation_id', 'directorate_id', 'name', 'type', 'parent_id'];

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    public function directorate()
    {
        return $this->belongsTo(Directorate::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
