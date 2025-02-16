<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Randevu Oluşturuldu</title>
</head>
<body>
    <p>Merhaba,</p>
    <p>{{ $user->name }} tarafından yeni bir randevu oluşturuldu.</p>
    <p>Randevu Detayları:</p>
    <ul>
        <li>Randevu Saati: {{ $appointment->appointments_time }}</li>
        <li>Eğitmen ID: {{ $appointment->trainer_id }}</li>
        <!-- İsteğe bağlı olarak diğer detayları da ekleyebilirsiniz -->
    </ul>
    <p>Lütfen randevu detaylarını kontrol ediniz.</p>
    <p>Saygılar,</p>
    <p>{{ config('app.name') }}</p>
</body>
</html>
