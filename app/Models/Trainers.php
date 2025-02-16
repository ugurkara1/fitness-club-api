<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainers extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'email',
        'sport_id',
    ];
    public function sports(){
        return $this->belongsTo(Sports::class, 'sport_id');
    }
    public function packages(){
    return $this->belongsToMany(Package::class,'package_trainer','trainer_id','package_id');
    }
    public function appointments(){
        return $this->hasMany(Appointment::class);
    }


}