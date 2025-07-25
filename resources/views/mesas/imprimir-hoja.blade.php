<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>QR CODES</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .page-break {
                page-break-after: always;
            }
        }

        body {
            font-family: sans-serif;
            background: white;
            padding: 20px;
        }

        .qr-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
        }

        .qr-card {
            width: 47%;
            height: 220px;
            border: 1px dashed #999;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            page-break-inside: avoid;
        }

        .qr-card img {
            width: 140px;
            height: 140px;
            object-fit: contain;
        }

        .mesa-label {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }

        .titulo {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="qr-grid">
        @foreach ($mesas as $mesa)
            <div class="qr-card">
                <img src="{{ asset('images/qrmesas/' . $mesa->codigo_qr) }}" alt="QR Mesa {{ $mesa->nombre }}">
                <div class="mesa-label">Mesa N.º {{ $mesa->nombre }}</div>
            </div>
        @endforeach
    </div>

    <script>
        window.onload = () => window.print();
    </script>
</body>
</html>
