<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    /**
     * Yeni bir paket oluşturur ve seçilen spor, tesis hizmetleri ve eğitmenleri ilişkilendirir.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function index()
    {/*
        // Paketlerin tümünü ve ilişkili sporları, tesisleri ve eğitmenleri yükleyerek al
        $packages = Package::with('sports', 'facilities', 'trainers')->get();

        // Response döndürme
        return response()->json([
            'message' => 'Paketler başarıyla getirildi.',
            'packages' => $packages
        ], 200);
    */
        $packages=Package::with("sports","facilities","trainers")->get();
        return response()->json([
            'message'  => __('messages.packages_retrieved'),
            'packages'=>$packages
        ],200);
    }

    public function getPackages(Request $request){
        try{
            Log::info('getPackages fonksiyonu çalıştırıldı.');
            //eğer kullanıcı kimlik doğrulaması yapılmışsa
            $user=Auth::user();
            if($user){
                $packages=Package::with(['sports','trainers','facilities'])
                    ->where('user_id',$user->id)
                    ->get();

            }else{
                $packages=Package::with(['sports','trainers','facilities'])->get();
                $locale=app()->getLocale();
            }
            //eğer paket bulunmuyorsa
            if($packages->isEmpty()){
                return response()->json([
                    'message'=> __('messages.no_appointments'),
                ],404);
            }
            $packages=$packages->map(function($package) use ($locale){
                return[
                    'id'=>$package->id,
                    'type'        => $package->type, // Dil desteği ekleniyor
                    'created_at'=>$package->created_at,
                    'updated_at'=>$package->updated_at,
                    'sports'=>$package->sports->map(function($sport) use ($locale){
                        return[
                            'id'          => $sport->id,
                            'name'        => $sport->getTranslatedName($locale), // Dil desteği ekleniyor
                            'description' => $sport->getTranslatedDescription($locale), // Dil desteği ekleniyor
                        ];
                    }),
                    'facilities'  => $package->facilities->map(function ($facility) use ($locale) {
                        return [
                            'id'          => $facility->id,
                            'name'        => $facility->getTranslatedName($locale), // Dil desteği ekleniyor
                            'description' => $facility->getTranslatedDescription($locale), // Dil desteği ekleniyor
                        ];
                    }),
                    'trainers'=>$package->trainers->map(function($trainers) use ($locale){
                        return [
                            'id'=> $trainers->id,
                            'name'=> $trainers->getTranslatedName($locale),
                            'description'=> $trainers->getTranslatedDescription($locale),
                        ];
                    }),
                ];
            });
            return response()->json([
                'message'  => __('messages.packages_retrieved'),
                'packages' => $packages
            ], 200);

        }catch (\Exception $e) {
            // Hata durumunda loglanır ve hata mesajı döndürülür
            Log::error("getPackages fonksiyonunda hata oluştu: " . $e->getMessage());
            return response()->json([
                'message' => __('messages.package_fetch_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //package add
    public function store(Request $request)
    {
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json([
                'message' => __('messages.unauthorized')
            ], 403);        }
        // Gelen verilerin doğrulanması
        Log::info('store metodu çağrıldı');

        $validated = $request->validate([
            'type'         => 'required|string|max:255',
            'sports'       => 'sometimes|array',
            'sports.*'     => 'exists:sports,id',
            'facilities'   => 'sometimes|array',
            'facilities.*' => 'exists:facilities,id',
            'trainers'     => 'sometimes|array',
            'trainers.*'   => 'exists:trainers,id',
        ]);

        Log::info('Validated data', $validated);

        // Paket verisinin oluşturulması
        $package = Package::create([
            'type' => $validated['type'],
        ]);

        // İlişkili sporları ekleme (varsa)
        if (isset($validated['sports'])) {
            $package->sports()->attach($validated['sports']);
            Log::info('Attached sports', ['sports' => $validated['sports']]);
        }

        // İlişkili tesis hizmetlerini ekleme (varsa)
        if (isset($validated['facilities'])) {
            $package->facilities()->attach($validated['facilities']);
            Log::info('Attached facilities', ['facilities' => $validated['facilities']]);
        }

        // İlişkili eğitmenleri ekleme (varsa)
        if (isset($validated['trainers'])) {
            Log::info('Validated trainers', ['trainers' => $validated['trainers']]);
            $package->trainers()->attach($validated['trainers']);

            // Attach işleminden sonra pivot tablonun içeriğini loglayalım:
            $attachedTrainers = $package->trainers()->get()->toArray();
            Log::info('Attached trainers after attach', $attachedTrainers);
        }

        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($package)
            ->withProperties(['attributes'=>$validated])
            ->log("Function name: $functionName. New package created.");

        // İlişkiler yüklendikten sonra response döndürme
        return response()->json([
            'message' => __('messages.package_created'),
            'package' => $package->load('sports', 'facilities', 'trainers')
        ], 201);
    }
    //paket silme
    public function deletePackage($id){
        $package=Package::where('id', $id)->first();
        if(!$package){
            return response()->json([
                'message' => __('messages.package_not_found')
            ], 404);
        }
        //ilişkili verileri silmeden ara tabloları kaldırıyoruz
        $package->sports()->detach();
        $package->facilities()->detach();
        $package->trainers()->detach();

        //paket silme işlemi
        $package->delete();

        // Activity kaydını oluşturma
        $functionName = __FUNCTION__;
        activity()
            ->causedBy(Auth::user()) // Etkinlik kaydını kullanıcı ile ilişkilendir
            ->performedOn($package) // Etkinliği belirtilen paketle ilişkilendir
            ->withProperties([  // Silinen paketle ilgili detaylar ekleniyor
                'package_id' => $package->id,
                'package_type' => $package->type,
                'deleted_by' => Auth::user()->id,  // Silen kullanıcı ID'si
            ])
            ->log("Function name: $functionName. Package deleted.");

            return response()->json([
                'message' => __('messages.package_deleted')
            ], 200);
    }
    //delete methoduna dil desteği eklenmiş hali
    /*
    public function deletePackage(Request $request,$id){
        if(!Auth::check() || Auth::user()->role_id!==1){
            return response()->json([
                'message'=> __('messages.unauthorized'),
            ] ,403);
        }
        $package=Package::where('id',$id)->first();
        if(!$package){
            return response()->json([
                'message'=> __('messages.package_not_found'),
            ] ,404);
        }
        $locale=$request->query('lang');
        if(!$locale || $locale=='tr'){
            $name=$package->name;
            $description=$package->description;
        }elseif($locale==='en'){
            $translation=$package->translations()->where('locale','en')->first();
            if($translation){
                $name=$translation->name;
                $description=$translation->description;
            }else{
                $name=$package->name;
                $description=$package->description;
            }
        }else{
            $name=$package->name;
            $description=$package->description;
        }
        $packageData=[
            'id'=> $package->id,
            'name'=> $name,
            'description'=> $description,
        ];
        //ilişkili verileri kaldırıyoruz
        $package->sports()->detach();
        $package->facilities()->detach();
        $package->trainers()->detach();

        //paket silme işlemi
        $package->delete();

        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($package)
            ->withProperties(['attributes' => $packageData])
            ->log("Function Name: $functionName. Package deleted successfully.");

        return response()->json([
            'message' => __('messages.package_deleted')
        ], 200);
    }*/
    //paket güncelleme
    public function updatePackage(Request $request, $id){

        Log::info('update metodu çağırıldı');
        $validated=$request->validate([
            'sports'=>'sometimes|array',
            'sports.*'=>'exists:sports,id',
            'facilities'=>'sometimes|array',
            'facilities.*'=>'exists:facilities,id',
            'trainers'=>'sometimes|array',
            'trainers.*'=> 'exists:trainers,id',
        ]);
        Log::info('Validated data', $validated);
        $package=Package::where('id', $id)->first();
        if(!$package){
            return response()->json([
                'message' => __('messages.package_not_found')
            ], 404);
        }
        $package->save();
        Log::info('Package güncellendi',['package'=>$package]);

        //ilişkili sporları güncelleme
        if(isset($validated['sports'])){
            $package->sports()->detach();
            //yeni ilişkileri ekle
            $package->sports()->attach($validated['sports']);
            Log::info('Spor ilişkisi güncellendi',['sports'=>$validated['sports']]);
        }
        //ilişkili tesisi güncelleme
        if(isset($validated['facilities'])){
            $package->facilities()->detach();

            //yeni ilişkiyi ekle
            $package->facilities()->attach($validated['facilities']);
            Log::info('Tesis ilişkisi güncellendi',['facilities'=>$validated['facilities']]);
        }
        //ilişkili eğitmenleri güncelle
        if(isset($validated['trainers'])){
            $package->trainers()->detach();
            //yeni ilişki ekle
            $package->trainers()->attach($validated['trainers']);
            Log::info('Eğitmen ilişkisi güncellendi',['trainers'=>$validated['trainers']]);
        }
        $functionName=__FUNCTION__;
        activity()
            ->causedBy(Auth::user())
            ->performedOn($package)
            ->withProperties(['attributes'=>$validated])
            ->log("Function name: $functionName. Package update operation successful.");
        return response()->json([
            'message' => __('messages.package_updated'),
            'package' => $package->load('sports', 'facilities', 'trainers')
        ], 200);
    }

}