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
    public function translations(){
        return $this->hasMany(FacilityTranslation::class,'facility_id');
    }
    public function getTranslatedName($locale = null)
    {
        // Varsayılan dil
        $locale = $locale ?? app()->getLocale();

        // Çeviri var mı kontrol et
        $translation = $this->translations()->where('locale', $locale)->first();

        // Eğer çeviri yoksa, varsayılan dilde (tr) çeviriye bak
        if (!$translation) {
            $translation = $this->translations()->where('locale', 'tr')->first();
        }

        return $translation ? $translation->name : $this->name;  // Eğer çeviri yoksa, orijinal ismi döndür
    }
    public function getTranslatedDescription($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        $translation = $this->translations()->where('locale', $locale)->first();

        if (!$translation) {
            $translation = $this->translations()->where('locale', 'tr')->first();
        }

        return $translation ? $translation->description : $this->description;  // Eğer çeviri yoksa, orijinal açıklamayı döndür
    }
}