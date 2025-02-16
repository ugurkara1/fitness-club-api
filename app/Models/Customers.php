<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
//müşteri modeli
class Customers extends Model
{
    //
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'height',
        'weight',
        'age',
    ];
    //User ile ilişki
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function packages(){
        return $this->belongsToMany(Package::class,'customer_package','customer_id','package_id');//ilişkili iki modelin bir araya geldiği tabloyu temsil eder
    }
    protected static $logAttributes = ['name', 'email', 'password'];  // İzlenecek alanlar
    protected static $logName = 'customers';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()  // Tüm özelliklerin loglanması
            ->logOnly(['name', 'email', 'phone']);  // Belirli alanları loglamak isterseniz
    }


}