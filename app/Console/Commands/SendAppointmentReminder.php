<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Appointment;
use App\Mail\AppointmentReminder;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendAppointmentReminder extends Command
{
    protected $signature = 'appointment:reminder'; //komutun nasıl çalıştıracağını belirler
    protected $description = 'Kullanıcıların yaklaşan randevuları için hatırlatma e-postası gönderir';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $now = Carbon::now();//zaman ve tarih işlemlerini kullanan kütüphane
        $oneHourLater = Carbon::now()->addHour();

        Log::info("Şu anki zaman: " . $now);
        Log::info("1 saat sonrası: " . $oneHourLater);

        // 1 saat içinde başlayacak randevuları al
        $appointments = Appointment::with('user', 'sports')
            ->whereBetween('appointments_time', [$now, $oneHourLater])
            ->get();

        Log::info("Toplam randevu sayısı: " . $appointments->count());

        // Randevuları döngü ile işleyelim
        foreach ($appointments as $appointment) {
            Log::info('Randevu Bilgileri:', [
                'appointment_id' => $appointment->id,
                'user' => $appointment->user?->name ?? 'Bilinmeyen Kullanıcı',
                'sport' => $appointment->sports?->name ?? 'Bilinmeyen Spor'
            ]);

            try {
                if ($appointment->user) {
                    Mail::to($appointment->user->email)->send(new AppointmentReminder($appointment));
                    Log::info("Randevu hatırlatması gönderildi: Randevu ID - {$appointment->id}, Kullanıcı ID - {$appointment->user->id}");
                } else {
                    Log::error("Kullanıcı bilgisi bulunamadı! Randevu ID: {$appointment->id}");
                }
            } catch (\Exception $e) {
                Log::error("Mail gönderilirken hata oluştu: " . $e->getMessage());
            }
        }

        Log::info("Tüm hatırlatmalar tamamlandı.");
    }
}