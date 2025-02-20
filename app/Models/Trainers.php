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
    public function translations(){
        return $this->hasMany(TrainerTranslation::class,'trainer_id');
    }

    public function getTranslatedName($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();

        if (!$translation) {
            $translation = $this->translations()->where('locale', 'tr')->first();
        }

        return $translation ? $translation->name : $this->name;
    }

    public function getTranslatedDescription($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();

        if (!$translation) {
            $translation = $this->translations()->where('locale', 'tr')->first();
        }

        return $translation ? $translation->description : $this->description;
    }


}