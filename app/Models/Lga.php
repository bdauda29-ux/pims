<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a Local Government Area (LGA) belonging to a specific State.
 */
class Lga extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'state_id'];

    /**
     * An LGA belongs to one specific State.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
