<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Package;

class CustomerController extends Controller
{
    // Kullanıcı profili göster
    public function showProfile(Request $request)
    {
        $user = Auth::user();
        // Kullanıcının customer bilgilerini alıyoruz
        $customer = $user->customer;

        // Eğer customer kaydı yoksa hata mesajı döndürüyoruz
        if (!$customer) {
            return response()->json(['message' => __('messages.customer_not_found')], 404);
        }

        // customer bilgilerini döndürüyoruz
        return response()->json(['customer' => $customer]);
    }

    // Kullanıcıya ait customer bilgilerini ekler
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validasyon işlemleri
        $validated = $request->validate([
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
            'age' => 'required|numeric',
        ]);

        // Kullanıcıya ait customer kaydını alıyoruz
        $customer = $user->customer;

        // Eğer customer kaydı yoksa, yeni müşteri kaydedeceğiz
        if (!$customer) {
            $customer = new Customers();
            $customer->user_id = $user->id;
        }

        // customer bilgilerini güncelliyoruz
        $customer->height = $validated['height'] ?? $customer->height;
        $customer->weight = $validated['weight'] ?? $customer->weight;
        $customer->age = $validated['age'] ?? $customer->age;

        // customer bilgilerini kaydediyoruz (varsa güncellenir, yoksa yeni eklenir)
        $customer->save();
        $functionName = __FUNCTION__;

        activity()
            ->causedBy($user) // Hangi kullanıcı tarafından yapıldığını belirtiyoruz
            ->performedOn($customer) // Hangi model üzerinde işlem yapıldığını belirtiyoruz
            ->withProperties(['attributes' => $validated]) // Yapılan işlemlerin verilerini ekleyebilirsiniz
            ->log("Function name: $functionName - Customer information saved successfully");

        return response()->json(['message' => __('messages.customer_info_saved')], 201);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Kullanıcıya ait müşteri bilgilerini alıyoruz
        $customer = $user->customer;

        // Eğer customer kaydı yoksa, 404 döndürelim
        if (!$customer) {
            return response()->json([
                'message' => __('messages.customer_not_found')
            ], 404);
        }

        // Validation işlemi yapalım
        $validated = $request->validate([
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
            'age' => 'required|numeric',
        ]);

        // customer bilgilerini güncelliyoruz
        $customer->update($validated);
        $functionName = __FUNCTION__;

        activity()
            ->causedBy($user)  // Hangi kullanıcı tarafından yapıldığını belirtiyoruz
            ->performedOn($customer)  // Hangi model üzerinde işlem yapıldığını belirtiyoruz
            ->withProperties(['attributes' => $validated])  // Yapılan işlemin verilerini ekleyebilirsiniz
            ->log("Function name: $functionName - Customer information updated");

        return response()->json([
            'message' => __('messages.customer_info_updated'),
            'customer' => $customer
        ]);
    }

    // Müşteri paket (abonelik) alıyor
    public function addPackage(Request $request)
    {
        $user = Auth::user();

        // Kullanıcının müşteri bilgilerini al
        $customer = $user->customer;

        // Müşteri bulunamadıysa hata döndür
        if (!$customer) {
            Log::warning("Müşteri bulunamadı. Kullanıcı ID: {$user->id}");
            return response()->json([
                'message' => __('messages.customer_not_found'),
            ], 404);
        }

        // Paket ID'sini doğrula
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        // Paket bilgisi alınıyor
        $package = Package::find($validated['package_id']);

        // Paket bulunamazsa hata döndür
        if (!$package) {
            Log::warning("Paket bulunamadı. Paket ID: {$validated['package_id']}");
            return response()->json([
                'message' => __('messages.package_not_found'),
            ], 404);
        }

        // customer bu paketi daha önce almış mı kontrol et
        if ($customer->packages->contains($package)) {
            Log::info("Müşteri bu paketi zaten almış. Kullanıcı ID: {$user->id}, Paket ID: {$package->id}");
            return response()->json([
                'message' => __('messages.package_already_purchased'),
            ], 400);
        }

        // Paketi müşteriye ekle
        $customer->packages()->attach($package->id);

        // Log: Paket başarıyla alındı
        Log::info("Müşteri başarıyla paket aldı. Kullanıcı ID: {$user->id}, Paket ID: {$package->id}");
        $functionName = __FUNCTION__;

        activity()
            ->causedBy($user)
            ->performedOn($customer)
            ->withProperties(["package_id" => $package->id])
            ->log("Function name: $functionName - Package purchased successfully");

        return response()->json([
            'message' => __('messages.package_purchased_successfully'),
            'package' => $package,
        ], 200);
    }
}