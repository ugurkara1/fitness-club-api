<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//user ve admin modeli
class Role extends Model
{
    //
    use HasFactory;
    protected $fillable = ['name'];
    public function users(){
        return $this->hasMany(User::class);
    }
}