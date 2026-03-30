<!DOCTYPE html>
<html>
<head>
    <title>Bakong QR Payment</title>
</head>
<body style="text-align:center; font-family:Arial; margin-top:50px;">

    <h2>Scan to Pay</h2>

    <p>Reference: {{ $reference }}</p>
    <p>Amount: ${{ $amount }}</p>

    @if($qrImage)
        <img src="{{ (str_contains($qrImage, 'data:image') || str_contains($qrImage, 'http')) ? $qrImage : 'data:image/png;base64,' . $qrImage }}" width="300">
    @else
        <p>QR not generated</p>
    @endif

</body>
</html>