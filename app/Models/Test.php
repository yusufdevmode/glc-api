<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Test extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function packages()
    {
        return $this->belongsToMany(
            Package::class,
            'package_test'
        )
        ->withPivot('sort_order')
        ->withTimestamps();
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function stimulus()
    {
        return $this->hasMany(stimulus::class);
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }
}
