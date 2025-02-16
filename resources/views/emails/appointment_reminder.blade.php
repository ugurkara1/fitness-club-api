<h1>Merhaba {{ $appointment->user ? $appointment->user->name : 'Bilinmeyen Kullanıcı' }}</h1>
<p>Bu e-posta, yaklaşan randevunuz hakkında bir hatırlatmadır:</p>
<p><strong>Randevu Tarihi ve Saati:</strong>
    {{ \Carbon\Carbon::parse($appointment->appointments_time)->format('d-m-Y H:i') }}
</p>
<p><strong>Sport:</strong> {{ $appointment->sports ? $appointment->sports->name : 'No Sport Assigned' }}</p>
<p>Randevunuza zamanında katılmanızı öneririz.</p>
