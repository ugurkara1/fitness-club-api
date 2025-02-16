<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//satılacak paket bilgileri
class Package extends Model
{
    //
    use HasFactory;
    protected $fillable = ['type'];

    public function sports()
    {
        // Eğer varsayılan pivot tablo adını kullanmıyorsanız, ikinci parametre olarak tablo adını belirtin.
        return $this->belongsToMany(Sports::class, 'package_sport', 'package_id', 'sport_id');
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'package_facility', 'package_id', 'facility_id');
    }

    public function trainers()
    {
        return $this->belongsToMany(Trainers::class, 'package_trainer', 'package_id', 'trainer_id');
    }
    public function customers()
    {
        return $this->belongsToMany(Customers::class, 'customer_package', 'package_id', 'customer_id');
    }

}