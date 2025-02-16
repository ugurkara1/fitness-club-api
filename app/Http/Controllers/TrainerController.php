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
            return response()->json(['message'=> 'Yetkisiz işlem.Trainer eklemeyi sadece adminler yapabilir'], 403);
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
            return response()->json($validator->errors(), 400);
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
            ->log("Fonksiyon adı: $functionName Eğitmen ekleme başarılı.");
        return response()->json(['message'=> 'Trainer başarıyla eklendi','trainers'=> $trainers],201);
    }
    //trainer listletme
    public function getTrainer(){
        $trainers=Trainers::all();
        return response()->json($trainers,200);
    }
    //trainer silme
    public function deleteTrainer($id){
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message'=> 'Yetkisiz işlem.Trainer silmeyi sadece adminler yapabilir'], 403);
        }

        $trainer=Trainers::where('id',$id)->first();
        if(!$trainer){
            return response()->json(['message'=> 'Trainer bulunamadı'],404);

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
            ->log("Fonksiyon adı: $functionName.Eğitmen silme işlemi başarıyla gerçekleştirildi.");
        $trainer->delete();
        return response()->json(['message'=> 'Trainer başarıyla silindi'],200);


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
        $trainers=QueryBuilder::for(Trainers::class)->
        allowedFilters([
            AllowedFilter::exact('name'), //tam eşleşme
            AllowedFilter::partial('email'),  //kısmi eşleşme
            AllowedFilter::partial('description'), //kısmi eşleşme
        ])
        ->get();
        if($trainers->isEmpty()){
            return response()->json(['message'=> 'Hiçbir eğitmen bulunamadı'],404);
        }
        return response()->json($trainers,200);
    }
}