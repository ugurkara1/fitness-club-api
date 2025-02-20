<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Trainers;
use App\Models\Sports;
use App\Models\Appointment;
use App\Mail\AppointmentCreated;

class AppointmentController extends Controller
{
    public function createAppointments(Request $request)
    {
        try {
            // Kullanıcıyı al
            $user = Auth::user();

            // User tipine dönüştürme
            $user = $user instanceof \App\Models\User ? $user : new \App\Models\User;

            Log::info("Randevu oluşturma işlemi başlatıldı. Kullanıcı ID: {$user->id}");

            // Doğrulama işlemleri
            $validated = $request->validate([
                "trainer_id" => "required|exists:trainers,id",
                "sports_id" => "required|exists:sports,id",
                "appointments_time" => "required|date|after:now"
            ]);
            Log::info("Randevu oluşturma işlemi için doğrulama başarılı. Verilen bilgiler:", $validated);

            // Eğitmeni ve spor dalını al
            $trainer = Trainers::find($validated["trainer_id"]);
            $sport = Sports::find($validated["sports_id"]);

            if (!$trainer) {
                Log::warning("Trainer not found. Trainer ID: {$validated['trainer_id']}");
                return response()->json([
                    'message' => __('messages.trainer_not_found'),
                ], 404);
            }

            if (!$sport) {
                Log::warning("Sport not found. Sport ID: {$validated['sports_id']}");
                return response()->json([
                    'message' => __('messages.sport_not_found'),
                ], 404);
            }

            //randevu dolu mu
            $existingAppointment = Appointment::where('trainer_id', $trainer->id)
                                            ->where('sports_id', $sport->id)
                                            ->where('appointments_time', $validated['appointments_time'])
                                            ->exists();

            // Eğer randevu varsa, yani doluysa, uyarı mesajı döndür
            if ($existingAppointment) {
                Log::warning("Randevu dolu. Eğitmen ID: {$trainer->id}, Spor Dalı ID: {$sport->id}, Zaman: {$validated['appointments_time']}");
                return response()->json([
                    'message' => __('messages.appointment_slot_booked'),
                ], 400);
            }

            // Randevu yoksa, yani boşsa, işlemi yap
            $appointment = Appointment::create([
                'user_id' => $user->id,
                'trainer_id' => $trainer->id,
                'sports_id' => $sport->id,
                'appointments_time' => $validated['appointments_time']
            ]);
            Log::info("Randevu başarıyla oluşturuldu. Randevu ID: {$appointment->id}, Kullanıcı ID: {$user->id}, Eğitmen ID: {$trainer->id}, Spor Dalı ID: {$sport->id}");

            $functionName=__FUNCTION__;
            //activity Log
            activity()
                ->causedBy($user)
                ->performedOn($appointment)
                ->withProperties([
                    'trainer'=>$trainer->name,
                    'sport'=>$sport->name,
                    'appointment_time'=>$validated['appointments_time']

                ])
                ->log("Function Name: $functionName, appointment creation successful. Appointment ID: {$appointment->id} created by user");
            // E-posta gönderimi
            try {
                // AppointmentCreated sınıfına doğru parametreleri gönder
                Mail::to($trainer->email)->send(new AppointmentCreated($appointment, $user));
                Log::info("E-posta gönderildi. Eğitmen E-posta: {$trainer->email}");
                $emailMessage = __('messages.email_sent');
            } catch (\Exception $e) {
                Log::error("E-posta gönderimi sırasında hata: " . $e->getMessage());
                $emailMessage = __('messages.email_failed');
            }

            return response()->json([
                'message' => __('messages.appointment_created'),
                'email_message' => $emailMessage,
                'appointment' => $appointment,
            ]);

        } catch (\Exception $e) {
            Log::error("Randevu oluşturma sırasında hata oluştu: " . $e->getMessage());
            return response()->json([
                'message' => __('messages.appointment_creation_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAppointments(Request $request)
    {
        try {
            Log::info('getAppointments fonksiyonu çalıştırıldı.');

            // Eğer kullanıcı kimlik doğrulaması yapılmışsa, sadece kullanıcıya ait randevuları getirelim
            $user = Auth::user();
            if ($user) {
                $appointments = Appointment::with(['trainer', 'sports'])  // Trainer ve Sports ilişkilerini dahil et
                    ->where('user_id', $user->id)  // Kullanıcıya ait randevuları filtreleyelim
                    ->get();
            } else {
                // Kimlik doğrulama yapılmamışsa, tüm randevuları listele
                $appointments = Appointment::with(['trainer', 'sports'])->get();
                $locale = $request->header('Accept-Language', app()->getLocale());
            }

            // Eğer randevu bulunmazsa, uygun mesaj döndürelim
            if ($appointments->isEmpty()) {
                return response()->json([
                    'message' => __('messages.no_appointments_found'),
                ], 404);
            }

            // Randevuların her birini formatlıyoruz
            $appointments = $appointments->map(function ($appointment) use ($locale) {
                return [
                    'id' => $appointment->id,
                    'appointments_time' => $appointment->appointments_time,
                    'created_at' => $appointment->created_at,
                    'updated_at' => $appointment->updated_at,
                    'trainer' => $appointment->trainer ? [
                        'id' => $appointment->trainer->id,
                        'name' => $appointment->trainer->getTranslatedName($locale),
                        'description' => $appointment->trainer->getTranslatedDescription($locale),
                        'email' => $appointment->trainer->email,
                        'sport' => $appointment->trainer->sports ? [
                            'id' => $appointment->trainer->sports->id,
                            'name' => $appointment->trainer->sports->getTranslatedName($locale),
                            'description' => $appointment->trainer->sports->getTranslatedDescription($locale),
                        ] : null,
                    ] : null,
                    'sport' => $appointment->sports ? [
                        'id' => $appointment->sports->id,
                        'name' => $appointment->sports->getTranslatedName($locale),
                        'description' => $appointment->sports->getTranslatedDescription($locale),
                    ] : null,
                ];
            });

            // Randevuların formatlanmış listesini döndürüyoruz
            return response()->json([
                'appointments' => $appointments,
            ], 200);

        } catch (\Exception $e) {
            // Hata durumunda loglanır ve hata mesajı döndürülür
            Log::error("getAppointments fonksiyonunda hata oluştu: " . $e->getMessage());
            return response()->json([
                'message' => __('messages.appointment_fetch_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function deleteAppointment(Request $request, $id)
    {
        try {
            Log::info('deleteAppointment fonksiyonu çalıştırıldı.');

            // İlgili randevuyu alıyoruz
            $appointment = Appointment::with(['trainer', 'sports'])->where('id', $id)->first();
            if (!$appointment) {
                return response()->json([
                    'message' => __('messages.appointment_not_found')
                ], 404);
            }

            // Dil parametresini alıyoruz
            $locale = $request->query('lang', app()->getLocale());

            // Log için randevu bilgilerini dil desteğine göre hazırlıyoruz
            $logData = [
                'appointment_id'   => $appointment->id,
                'appointments_time'=> $appointment->appointments_time,
                'trainer'          => $appointment->trainer
                                    ? $appointment->trainer->getTranslatedName($locale)
                                    : null,
                'sport'            => $appointment->sports
                                    ? $appointment->sports->getTranslatedName($locale)
                                    : null,
            ];

            // Randevu silmeden önce ilişkileri temizleyebiliriz (varsa)
            // Örneğin, pivot ilişkiler varsa detach edilebilir. Bu örnekte basitçe randevu siliniyor.

            $appointment->delete();

            // Activity log kaydı oluşturma
            activity()
                ->causedBy(Auth::user())
                ->performedOn($appointment)
                ->withProperties($logData)
                ->log("Appointment deleted successfully.");

            return response()->json([
                'message' => __('messages.appointment_deleted')
            ], 200);

        } catch (\Exception $e) {
            Log::error("deleteAppointment fonksiyonunda hata oluştu: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => __('messages.appointment_delete_failed'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }



}