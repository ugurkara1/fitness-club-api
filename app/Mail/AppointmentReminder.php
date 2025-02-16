<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\Appointment;
use Carbon\Carbon;
//hatırlatma e-postasını göndermek üzere yapılandırma
class AppointmentReminder extends Mailable
{
    public $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    //email içeriğini yapılandırır
    public function build()
    {
        return $this->subject("Randevu Hatırlatma")
                    ->view('emails.appointment_reminder')
                    ->with([
                        'appointment' => $this->appointment,
                        'formatted_date' => Carbon::parse($this->appointment->appointments_time)->format('d.m.Y H:i')
                    ]);
    }
}