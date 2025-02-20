<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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
    /*public function getSports(){
        $sports=Sports::all();
        return response()->json($sports,200);
    }*/
    //spor dalını sil
    public function deleteSport(Request $request, $id)
    {
        // Yetkilendirme kontrolü
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        // Spor kaydını buluyoruz
        $sport = Sports::where('id', $id)->first();
        if (!$sport) {
            return response()->json(['message' => __('messages.sport_not_found')], 404);
        }

        // İstekten dil parametresini alıyoruz
        $locale = $request->query('lang');

        if (!$locale || $locale === 'tr') {
            // Dil belirtilmemiş ya da Türkçe isteniyorsa orijinal verileri kullanıyoruz.
            $name = $sport->name;
            $description = $sport->description;
        } elseif ($locale === 'en') {
            // İngilizce istenmişse, translations tablosundan İngilizce veriyi çekiyoruz.
            $translation = $sport->translations()->where('locale', 'en')->first();
            if ($translation) {
                $name = $translation->name;
                $description = $translation->description;
            } else {
                // Eğer çeviri bulunamazsa orijinal (Türkçe) veriler kullanılır.
                $name = $sport->name;
                $description = $sport->description;
            }
        } else {
            // Diğer diller için de fallback olarak orijinal veriler kullanılabilir.
            $name = $sport->name;
            $description = $sport->description;
        }

        // Loglanacak verileri hazırlıyoruz
        $sportsData = [
            'id'          => $sport->id,
            'name'        => $name,
            'description' => $description,
        ];

        activity()
            ->causedBy(Auth::user())      // İşlemi yapan kullanıcı (admin)
            ->performedOn($sport)         // Silinmeden önceki model kaydını logla
            ->withProperties(['attributes' => $sportsData])
            ->log("Fonksiyon adı: deleteSport - Spor dalı ($name) silindi");

        // Ana tablodan silme işlemi yapılıyor
        $sport->delete();

        return response()->json(['message' => __('messages.sport_deleted')], 200);
    }


    //English translations add
    public function addEnglishTranslation(Request $request, $id)
    {
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            Log::error("Unauthorized access attempt by user: " . Auth::user()->id); // Log error
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error("Validation error while adding translation for sport ID {$id}", [
                'errors' => $validator->errors(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'message' => __('messages.validation_error'),
                'errors' => $validator->errors(),
            ], 400);
        }

        // İlgili spor kaydını buluyoruz
        $sport = Sports::where('id', $id)->first();
        if (!$sport) {
            Log::error("Sport not found for ID: {$id}");
            return response()->json(['message' => __('messages.sport_not_found')], 404);
        }

        // İngilizce çeviriyi ekliyoruz veya güncelliyoruz
        try {
            $translation = $sport->translations()->updateOrCreate(
                ['locale' => 'en'],
                [
                    'name' => $request->name,
                    'description' => $request->description ?? null,
                ]
            );

            Log::info("Translation added/updated successfully for sport ID: {$id}", [
                'translation' => $translation
            ]);

            return response()->json([
                'message' => __('messages.translation_updated'),
                'translation' => $translation,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error occurred while updating translation for sport ID: {$id}", [
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred while updating the translation.'], 500);
        }
    }

    public function getSports(Request $request)
    {
        $locale = $request->query('lang', app()->getLocale());

        // Eğer istenen dil Türkçe ise orijinal sports tablosundan çekiyoruz
        if ($locale === 'tr') {
            $sports = Sports::all();
            $result = $sports->map(function($sport) {
                return [
                    'id'          => $sport->id,
                    'name'        => $sport->name,
                    'description' => $sport->description,
                ];
            });
        } else {
            // Diğer diller için translations tablosundan veriyi çekiyoruz.
            $sports = Sports::with(['translations' => function($query) use ($locale) {
                $query->where('locale', $locale);
            }])->get();

            $result = $sports->map(function($sport) {
                $translation = $sport->translations->first();
                return [
                    'id'          => $sport->id,
                    // Eğer ilgili dilde çeviri yoksa fallback olarak orijinal (Türkçe) verileri kullanabilirsiniz.
                    'name'        => $translation ? $translation->name : $sport->name,
                    'description' => $translation ? $translation->description : $sport->description,
                ];
            });
        }

        return response()->json($result, 200);
    }

}