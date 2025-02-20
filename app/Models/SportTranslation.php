<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportTranslation extends Model
{
    //
    use HasFactory;
    protected $fillable = ['sport_id','locale','name','description'];

    //relations
    public function sport(){
        return $this->belongsTo(Sports::class,'sport_id');
    }
}