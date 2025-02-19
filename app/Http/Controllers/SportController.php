<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Sports;

class SportController extends Controller
{

    //Sports tablosuna ekleme
    public function createSport(Request $request){
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        $validator=Validator::make($request->all(), [
            'name'=>'required|string|max:255',
            'description'=>'nullable|string',
        ]);
        //validasyon hataları varsa buraya dön
        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.validation_error'),
                'errors' => $validator->errors(),
            ], 400); // Eksik veya hatalı parametre
        }
        $validated=$validator->validated();
        //Yeni spor dalı ekliyoruz
        $sport=Sports::create([
            'name'=> $request->name,
            'description'=> $request->description ?? null,

        ]);
        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($sport)
            ->withProperties(['attributes'=>$validated])
            ->log("Fonksiyon Adı: $functionName.Spor dalı ekleme yapıldı.");
        return response()->json([
            'message' => __('messages.sport_created'),
            'sport' => $sport
        ], 201);    }
    //spor dallarını listele
    public function getSports(){
        $sports=Sports::all();
        return response()->json($sports,200);
    }
    //spor dalını sil
    public function deleteSport($id){
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        $sport=Sports::where('id', $id)->first();

        if(!$sport){
            return response()->json(['message' => __('messages.sport_not_found')], 404);
        }
        $sportsData=[
            'id'=>$sport->id,
            'name'=>$sport->name,
            'description'=>$sport->description,
        ];
        activity()
            ->causedBy(Auth::user()) // İşlemi yapan kullanıcı (admin)
            ->performedOn($sport) // Silinmeden önceki model kaydını logla
            ->withProperties(['attributes'=>$sportsData])
            ->log("Fonksiyon adı: deleteUser - Kullanıcı ({$sport->name}) silindi");
        $sport->delete();
        return response()->json(['message' => __('messages.sport_deleted')], 200);
    }

}