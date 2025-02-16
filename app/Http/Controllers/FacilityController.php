<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Facility;

class FacilityController extends Controller
{
    //
    /*public function getFacilities(){
        $Facilities=Facility::all();
        return response()->json($Facilities,200);
    }*/
    public function getFacilities(){
        $facilities = Facility::with('sports')->get();

        // Her tesise ait spor bilgisini sadece id ve name olarak dönüştürüyoruz.
        $facilities = $facilities->map(function($facility) {
            return [
                'id' => $facility->id,
                'name' => $facility->name,
                'description' => $facility->description,
                'sports' => $facility->sports->map(function($sport) {
                    return [
                        'id' => $sport->id,
                        'name' => $sport->name
                    ];
                })
            ];
        });

        return response()->json($facilities, 200);
    }


    public function createFacility(Request $request){
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message'=> 'Yetkisiz işlem.Tesis eklemesini sadece adminler yapabilir'], 403);
        }
        // Gelen verilerin doğrulanması
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sport_ids' => 'required|array',
            'sport_ids.*' => 'exists:sports,id',  // Her id, sports tablosunda mevcut olmalı
        ]);
        $validated=$validator->validated();
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Yeni tesis oluştur
        $facility = Facility::create([
            'name' => $request->name,
            'description' => $request->description ?? null,
        ]);

        // Loglama yapalım
        Log::info('Facility created: ', $facility->toArray());

        // Pivot tabloya spor dallarını ekleyelim
        try {
            Log::info('Attempting to attach sports: ', $request->sport_ids);
            $facility->sports()->attach($request->sport_ids);
            Log::info('Sports attached successfully.');
        } catch (\Exception $e) {
            Log::error('Error attaching sports: ', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error attaching sports', 'details' => $e->getMessage()], 500);
        }
        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($facility)
            ->withProperties(['attributes'=>$validated ])
            ->log("Fonksiyon adı: $functionName.Tesis ekleme işlemi yapıldı. ({$facility->name})");
        return response()->json(['message' => 'Tesis eklendi', 'facility' => $facility->load('sports')], 201);
    }
    public function updateFacility(Request $request,$id){
        Log::info('Update metodu çağırıldı');
        $validated=$request->validate([
            'name'=> 'required|string|max:255',
            'description'=> 'nullable|string',
            'sport_ids'=> 'required|array',
            'sports_ids.*'=> 'exists:sports,id'
        ]);
        Log::info('Doğrulanmış veriler', $validated);
        $facility=Facility::where('id',$id)->first();
        if(!$facility){
            return response()->json(['message'=> 'Paket bulunamadı'], 404);
        }
        // Tesisin temel bilgilerinin güncellenmesi
        $facility->name = $validated['name'];
        $facility->description = $validated['description'] ?? $facility->description;
        $facility->save();
        Log::info("Tesis güncellendi",['facility'=>$facility]);

        // İlişkili spor dallarını pivot tablo üzerinden güncelle (sync)
        try {
            $facility->sports()->sync($validated['sport_ids']);
        } catch (\Exception $e) {
            Log::error('Spor dalları güncellenirken hata oluştu', ['error' => $e->getMessage()]);
            return response()->json([
                'error'   => 'Spor dalları güncellenirken hata oluştu',
                'details' => $e->getMessage()
            ], 500);
        }
        $facilityData=[
            'id'=> $facility->id,
            'name'=> $facility->name,
            'description'=> $facility->description

        ];
        $functionName=__FUNCTION__;
        $facility->load('sports');
        Log::info("Tesis güncellendi",['facility'=>$facilityData]);
        activity()
            ->causedBy(Auth::user())
            ->performedOn($facility)
            ->withProperties(["attributes"=> $facilityData])
            ->log("Fonksiyon adı: $functionName.Tesis güncellendi.({$facility->name})");
        return response()->json([
            'message'=> 'Tesis güncellendi',
            'facility'=> $facility
        ],200);

    }

}