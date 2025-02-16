<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sports extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
    ];
    public function trainers(){
        return $this->hasMany(Trainers::class);
    }
    public function facilities(){
        return $this->belongsToMany(Facility::class,'facility_sport');
    }
    public function packages(){
        return $this->belongsToManyl(Package::class);
    }
    //spor dalının sahip olduğu randevular
    public function appointments(){
        return $this->hasMany(Appointment::class);
    }
}