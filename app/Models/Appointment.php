<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//randevu modeli
class Appointment extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        "user_id",
        "trainer_id",
        "sports_id",
        "appointments_time",
    ];
    //kullanıcı ile ilişki
    public function user(){
        return $this->belongsTo(User::class);
    }
    //eğitmen ile ilişkisi
    public function trainer(){
        return $this->belongsTo(Trainers::class);
    }
    //spor dalı ile ilişkisi
    public function sports(){
        return $this->belongsTo(Sports::class,'sport_id');
    }
}