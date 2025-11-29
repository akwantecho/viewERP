<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'code', 'floors_count', 'notes'];

    public function floors()
{
    return $this->hasMany(Floor::class);
}
public function units()
{
    return $this->hasManyThrough(Unit::class, Floor::class);
}


}



