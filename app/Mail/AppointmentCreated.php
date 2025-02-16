<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Models\User;

class AppointmentCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $user;

    // Constructor'da parametreleri alıyoruz
    public function __construct(Appointment $appointment, User $user)
    {
        $this->appointment = $appointment;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Yeni Randevu Oluşturuldu')
                    ->view('emails.appointment_created');
    }
}