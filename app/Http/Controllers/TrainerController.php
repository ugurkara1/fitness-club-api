<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TrainerController extends Controller
{
    //
    //eğitmen ekleme
    public function createTrainer(Request $request){
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'required|email|unique:trainers,email',
            'sport_id' => 'required|exists:sports,id',  // Dikkat: exists:sports,id şeklinde yazılmalı
        ]);

        //validasyon hatası varsa
        if ($validator->fails()) {
            // Burada validasyon hatalarını loglamak faydalı olabilir
            Log::error('Validation failed: ' . json_encode($validator->errors()));
            return response()->json([
                'message' => __('messages.operation_failed'),
                'errors' => $validator->errors(),
            ], 400);
        }
        $validated=$validator->validated();

        //Yeni eğitmen ekliyoruz
        $trainers=Trainers::create([
            'name'=> $request->name,
            'description'=> $request->description ?? null,
            'email'=> $request->email ?? null,
            'sport_id'=> $request->sport_id ?? null,


        ]);
        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($trainers)
            ->withProperties(['attributes'=>$validated])
            ->log("Function name: $functionName Trainer created successfully.");
        return response()->json([
            'message' => __('messages.trainer_created'),
            'trainers' => $trainers
        ], 201);    }
    //trainer listletme
    /*public function getTrainer(){
        $trainers=Trainers::all();
        return response()->json($trainers,200);
    }*/
    //trainer silme
    public function deleteTrainer(Request $request,$id){
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $trainer=Trainers::where('id',$id)->first();
        if(!$trainer){
            return response()->json(['message' => __('messages.trainer_not_found')], 404);

        }
        $locale = $request->query('lang');

        if (!$locale || $locale === 'tr') {
            // Dil belirtilmemiş ya da Türkçe isteniyorsa orijinal verileri kullanıyoruz.
            $name = $trainer->name;
            $description = $trainer->description;
        } elseif ($locale === 'en') {
            // İngilizce istenmişse, translations tablosundan İngilizce veriyi çekiyoruz.
            $translation = $trainer->translations()->where('locale', 'en')->first();
            if ($translation) {
                $name = $translation->name;
                $description = $translation->description;
            } else {
                // Eğer çeviri bulunamazsa orijinal (Türkçe) veriler kullanılır.
                $name = $trainer->name;
                $description = $trainer->description;
            }
        } else {
            // Diğer diller için de fallback olarak orijinal veriler kullanılabilir.
            $name = $trainer->name;
            $description = $trainer->description;
        }
        $trainersData=[
            'id'=> $trainer->id,
            'name'=> $trainer->name,
            'description'=> $trainer->description,
        ];
        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($trainer)
            ->withProperties(['attributes'=>$trainersData])
            ->log("Function Name: $functionName.Trainer deleted successfully.");
        $trainer->delete();
        return response()->json(['message' => __('messages.trainer_deleted')], 200);

    }
    /*public function searchTrainer(Request $request)
    {
        // 'query' parametresini alıyoruz.
        $searchTerm = $request->query('query');

        if (!$searchTerm) {
            return response()->json(['message' => 'Arama parametresi gereklidir.'], 400);
        }

        // Eğitmenler arasında arama yapıyoruz.
        $trainers = Trainers::where('name', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('email', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('description', 'LIKE', '%' . $searchTerm . '%')
            ->get();

        if ($trainers->isEmpty()) {
            return response()->json(['message' => 'Hiçbir eğitmen bulunamadı.'], 404);
        }

        return response()->json(['trainers' => $trainers], 200);
    }*/

    //spatie queryy kullanılarak filtreleme
    public function searchTrainer(Request $request){
        try {
            Log::info('searchTrainer fonksiyonu çalıştırıldı.');

            // Geçerli dilin alınması
            $locale = app()->getLocale();  // Bu satır mevcut dil ayarını alır

            // Spatie QueryBuilder kullanarak eğitmenleri filtreliyoruz
            $trainers = QueryBuilder::for(Trainers::class)
                ->allowedFilters([
                    AllowedFilter::exact('name'), // Tam eşleşme
                    AllowedFilter::partial('email'), // Kısmi eşleşme
                    AllowedFilter::partial('description'), // Kısmi eşleşme
                ])
                ->get();

            // Eğer dil İngilizce ise, çevirileri de dikkate alarak filtreleme yapıyoruz
            if ($locale === 'en') {
                // İngilizce verilerle arama yapmak için, çeviri tablosunu da göz önünde bulunduruyoruz
                $trainers = $trainers->filter(function($trainer) {
                    return $trainer->translations()->where('locale', 'en')->exists();
                });
            }

            // Eğer sonuçlar boşsa, uygun mesaj dönülür
            if ($trainers->isEmpty()) {
                return response()->json(['message' => __('messages.no_trainer_found')], 404);
            }

            // Eğitmenlerin her biri için çeviri ve spor bilgilerini alıyoruz
            $trainers = $trainers->map(function ($trainer) use ($locale) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->getTranslatedName($locale), // İlgili dilde isim
                    'description' => $trainer->getTranslatedDescription($locale), // İlgili dilde açıklama
                    'email' => $trainer->email,
                    'sport' => $trainer->sports ? [
                        'id' => $trainer->sports->id,
                        'name' => $trainer->sports->getTranslatedName($locale), // Spor adı ilgili dilde
                        'description' => $trainer->sports->getTranslatedDescription($locale), // Spor açıklaması ilgili dilde
                    ] : null,
                    'created_at' => $trainer->created_at,
                    'updated_at' => $trainer->updated_at,
                ];
            });

            Log::info('searchTrainer fonksiyonu başarıyla çalıştı. Eğitmenler getirildi.', ['trainer_count' => count($trainers)]);

            // Eğitmenleri ve spor bilgilerini döndürüyoruz
            return response()->json($trainers, 200);

        } catch (\Exception $e) {
            // Hata durumunda loglanır ve hata mesajı döndürülür
            Log::error('searchTrainer fonksiyonunda hata oluştu: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => __('messages.server_error')], 500);
        }
    }


    public function addEnglishTranslation(Request $request,$id){
        if(!Auth::check() || Auth::user()->role_id!=1){
            return response()->json(['message'=> __('messages.unauthorized')],403);
        }
        $validator=Validator::make($request->all(), [
            'name'=>'required|string|max:255',
            'description'=> 'nullable|string',
        ]);
        if($validator->fails()){
            Log::error("Validation error while adding translation for trainer ID {$id}",[
                'errors'=>$validator->errors(),
                'request'=>$request->all(),
            ]);
            return response()->json([
                'message' => __('messages.validation_error'),
                'errors' => $validator->errors(),
            ], 400);
        }
        //ilgili trainer kaydını buluyoruz
        $trainer=Trainers::where('id',$id)->first();
        if(!$trainer){
            Log::error("Trainer not found for ID:{$id}");
            return response()->json(["message"=> __("messages.trainer_not_found")],400);

        }
        //ingilizce çeviriyi ekliyoruz veya güncelliyoruz
        try{
            $translation=$trainer->translations()->updateOrCreate(
                ['locale'=>'en'],
                [
                    'name'=> $request->name,
                    'description'=> $request->description ?? null,
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
            return response()->json(['message' => __('messages.server_error')], 500);
        }
    }

    public function getTrainer(){
        try {
            Log::info('getTrainer fonksiyonu çalıştırıldı.');

            $trainers = Trainers::with(['sports'])->get();
            $locale = app()->getLocale();

            $trainers = $trainers->map(function ($trainer) use ($locale) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->getTranslatedName($locale),
                    'description' => $trainer->getTranslatedDescription($locale),
                    'email' => $trainer->email,
                    'sport' => $trainer->sports ? [
                        'id' => $trainer->sports->id,
                        'name' => $trainer->sports->getTranslatedName($locale),
                        'description' => $trainer->sports->getTranslatedDescription($locale),
                    ] : null,
                    'created_at' => $trainer->created_at,
                    'updated_at' => $trainer->updated_at,
                ];
            });

            Log::info('getTrainer fonksiyonu başarıyla çalıştı. Eğitmenler getirildi.', ['trainer_count' => count($trainers)]);

            return response()->json($trainers, 200);
        } catch (\Exception $e) {
            Log::error('getTrainer fonksiyonunda hata oluştu: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => __('messages.server_error')], 500);
        }
    }




}