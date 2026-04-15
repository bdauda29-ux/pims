<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a Nigerian State.
 */
class State extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * One State has many Local Government Areas (LGAs).
     */
    public function lgas()
    {
        return $this->hasMany(Lga::class);
    }
}
