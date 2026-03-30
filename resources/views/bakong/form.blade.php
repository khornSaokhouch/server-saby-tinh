<!DOCTYPE html>
<html>
<head>
    <title>Bakong QR</title>
</head>
<body style="text-align:center; font-family:Arial; margin-top:40px;">

<h2>Generate QR</h2>

<form method="POST" action="/bakong/generate">
    @csrf
    <input type="number" name="amount" placeholder="Enter price"
           step="0.01" required style="padding:10px;">
    <br><br>
    <button type="submit">Generate QR</button>
</form>

<hr>

@if(isset($message))
    <div style="margin: 20px auto; max-width: 400px; padding: 15px; border-radius: 8px; 
                background-color: {{ isset($success) && $success ? '#d4edda' : '#f8d7da' }}; 
                color: {{ isset($success) && $success ? '#155724' : '#721c24' }}; 
                border: 1px solid {{ isset($success) && $success ? '#c3e6cb' : '#f5c6cb' }};">
        {{ $message }}
    </div>
@endif

@if(isset($qrImage) && $qrImage)
            <div class="qr-container">
                <img src="{{ (str_contains($qrImage, 'data:image') || str_contains($qrImage, 'http')) ? $qrImage : 'data:image/png;base64,' . $qrImage }}" alt="Bakong QR">
                <p>Scan to pay: <strong>KHR {{ number_format($amount ?? 0, 2) }}</strong></p>
            </div>
@else
    <p style="color:orange;">No QR image returned. Showing QR string:</p>
    <textarea rows="4" cols="60">{{ $qrString ?? '' }}</textarea>
@endif

</body>
</html>