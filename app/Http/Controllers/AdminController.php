<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AdminController extends Controller
{
    // Tüm kullanıcıları listeleme
    public function getUsers()
    {
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message'=> 'Yetkisiz işlem.Kullanıcı listlemeyi sadece adminler yapabilir'], 403);
        }
        $users = User::all();
        return response()->json($users, 200);
    }

    //Spatie QUERY ile filtreleme
    public function searchUser(Request $request){
        $users=QueryBuilder::for(User::class)->
        allowedFilters([
            AllowedFilter::exact('name'),
            AllowedFilter::partial('email'),
            AllowedFilter::exact('role_id')
        ])
        ->get();
        if($users->isEmpty()){
            return response()->json(['message'=> 'Kullanıcı bulunamadı'], 404);
        }
        return response()->json($users, 200);
    }

    // Yeni kullanıcı oluşturma
    public function createUser(Request $request)
    {
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message'=> 'Yetkisiz işlem.Kullanıcı eklemeyi sadece adminler yapabilir'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $validated=$validator->validated();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => $request->role_id,
        ]);
        $functionName=__FUNCTION__;
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['attributes'=>$validated])
            ->log("Fonksiyon adı: $functionName Kullanıcı başarıyla eklendi");
        return response()->json(['message' => 'Kullanıcı oluşturuldu', 'user' => $user], 201);
    }

    // Kullanıcı güncelleme
    public function updateUser(Request $request, $id)
    {
        if(!Auth::check()||Auth::user()->role_id!==1){
            return response()->json(['message'=> 'Yetkisiz işlem.Kullanıcı güncellemeyi sadece adminler yapabilir'], 403);
        }
        $user = User::where('id', $id)->first(); // `first()` ile tek bir model döndür

        if (!$user) {
            return response()->json(['message' => 'Kullanıcı bulunamadı'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:6',
            'role_id' => 'sometimes|required|integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $validated=$validator->validated();

        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
            'role_id' => $request->role_id ?? $user->role_id,
        ]);
        $functionName=__FUNCTION__;

        // Activity Log güncellemesi
        activity()
            ->causedBy($user) // Güncelleme yapan admin
            ->performedOn($user) // Kullanıcı modeli verildi
            ->withProperties(['attributes' => $validated])
            ->log("Fonksiyon adı: $functionName - Kullanıcı başarıyla güncellendi");
        return response()->json(['message' => 'Kullanıcı güncellendi', 'user' => $user], 200);
    }

    // Kullanıcı silme
    public function deleteUser($id)
    {
        if (!Auth::check() || Auth::user()->role_id !== 1) {
            return response()->json(['message' => 'Yetkisiz işlem. Kullanıcı silmeyi sadece adminler yapabilir'], 403);
        }

        // Kullanıcıyı getir, yoksa 404 hatası döndür
        $user = User::where('id', $id)->first(); // `first()` ile tek bir model döndür
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
        ];
        // Silme işlemini logla, çünkü delete() çağrıldıktan sonra model kullanılamaz
        activity()
            ->causedBy(Auth::user()) // İşlemi yapan kullanıcı (admin)
            ->performedOn($user) // Silinmeden önceki model kaydını logla
            ->withProperties(['attributes'=>$userData])
            ->log("Fonksiyon adı: deleteUser - Kullanıcı ({$user->email}) silindi");

        // Kullanıcıyı sil
        $user->delete();

        return response()->json(['message' => 'Kullanıcı başarıyla silindi.'], 200);
    }


}