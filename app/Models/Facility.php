<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//tesis modeli
class Facility extends Model
{
    //
    use HasFactory;
    protected $fillable = ['name','description'];
    public function sports(){
        return $this->belongsToMany(Sports::class,'facility_sport');
    }
    public function packages(){
        return $this->belongsToMany(Package::class);
    }
}