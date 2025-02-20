<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerTranslation extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'trainer_id',
        'locale',
        'name',
        'description'
    ] ;
    public function trainer(){
        return $this->belongsTo(Trainers::class,'trainer_id');
    }
}