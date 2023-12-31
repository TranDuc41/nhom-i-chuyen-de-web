<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenities extends Model
{
    protected $table = 'amenities';
    protected $primaryKey = 'amenities_id';
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];
}
