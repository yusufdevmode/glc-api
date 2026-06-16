<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function tests()
    {
        return $this->belongsToMany(
            Test::class,
            'package_test'
        )
        ->withPivot('sort_order')
        ->withTimestamps();
    }

    public function packageEnrollments()
    {
        return $this->hasMany(PackageEnrollment::class);
    }
}
