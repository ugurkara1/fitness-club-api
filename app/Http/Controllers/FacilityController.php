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

    /*
    //facility list
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

        return response()->json([
            'messages'=>__('messages.facilities_retrieved')
            ,$facilities
        ], 200);
    }
    */
    public function getFacilities(Request $request)
    {
        // İstenen dilin alınması, varsayılan dil olarak mevcut dil alınır
        $locale = $request->query('lang', app()->getLocale());

        // Eğer istenen dil Türkçe ise orijinal facilities tablosundan çekiyoruz
        if ($locale === 'tr') {
            $facilities = Facility::with('sports')->get();
            $result = $facilities->map(function($facility) {
                return [
                    'id'          => $facility->id,
                    'name'        => $facility->name,
                    'description' => $facility->description,
                    'sports'      => $facility->sports->map(function($sport) {
                        return [
                            'id'   => $sport->id,
                            'name' => $sport->name
                        ];
                    })
                ];
            });
        } else {
            // Diğer diller için translations tablosundan veriyi çekiyoruz
            $facilities = Facility::with(['translations' => function($query) use ($locale) {
                $query->where('locale', $locale);
            }, 'sports.translations' => function($query) use ($locale) {
                $query->where('locale', $locale);
            }])->get();

            $result = $facilities->map(function($facility) use ($locale) {
                // Facility çevirisi
                $facilityTranslation = $facility->translations->first();

                return [
                    'id'          => $facility->id,
                    'name'        => $facilityTranslation ? $facilityTranslation->name : $facility->name,
                    'description' => $facilityTranslation ? $facilityTranslation->description : $facility->description,
                    'sports'      => $facility->sports->map(function($sport) use ($locale) {
                        $sportTranslation = $sport->translations->first();
                        return [
                            'id'          => $sport->id,
                            'name'        => $sportTranslation ? $sportTranslation->name : $sport->name
                        ];
                    })
                ];
            });
        }

        return response()->json([
            'messages' => __('messages.facilities_retrieved'),
            'facilities' => $result
        ], 200);
    }


    public function createFacility(Request $request){
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json([
                'message' => __('messages.unauthorized')
            ], 403);
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
            return response()->json(['error' => __('messages.sports_attach_failed'), 'details' => $e->getMessage()], 500);
        }
        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($facility)
            ->withProperties(['attributes'=>$validated ])
            ->log("Function name: $functionName. Facility added. ({$facility->name})");
        return response()->json([
            'message' => __('messages.facility_created'),
            'facility' => $facility->load('sports')
        ], 201);
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
            return response()->json(['message' => __('messages.facility_not_found')], 404);
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
                'error' => __('messages.sports_update_failed'),
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
            ->log("Function name: $functionName. Facility updated. ({$facility->name})");
        return response()->json([
            'message' => __('messages.facility_updated'),
            'facility' => $facility
        ], 200);

    }
    public function addEnglishTranslation(Request $request,$id){
        if(!Auth::check() || Auth::user()->role_id!=1){
            return response()->json([
                'message'=>__('messages.unauthorized'),
            ],403);
        }
        $validator=Validator::make($request->all(), [
            'name'=>'required|string|max:255',
            'description'=>'nullable|string',
        ]);
        if($validator->fails()){
            Log::error("Validation error while adding translation for trainer ID {$id}",[
                'errors'=>$validator->errors(),
                'request'=>$request->all(),
            ]);
            return response()->json([
                'message'=>__('messages.validation_error'),
            ] ,400);
        }
        //ilgili facility buluyoruz
        $facility=Facility::where('id',$id)->first();
        if(!$facility){
            Log::error("Facility not found for ID:{$id}");
            return response()->json(["message"=>'messages.facility_not_found'],400);
        }
        try{
            $translation=$facility->translations()->updateOrCreate(
                ['locale'=>'en'],
                [
                    'name'=>$request->name,
                    'description'=>$request->description ?? null,
                ]
            );
            Log::info("Translation added/updated successfully for sport ID: {$id}", [
                'translation' => $translation
            ]);
            return response()->json([
                'message' => __('messages.translation_updated'),
                'translation' => $translation,
            ], 200);
        }catch (\Exception $e) {
            Log::error("Error occurred while updating translation for trainer ID: {$id}", [
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred while updating the translation.'], 500);
        }
    }


}