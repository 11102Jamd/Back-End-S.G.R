<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .period {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .generated {
            font-size: 12px;
            color: #555;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .order-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .order-total {
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
    <title>Reporte de Compras</title>
</head>

<body>
    <div class="header">
        <div class="title">Reporte de Compras</div>
        <div class="period">
            Período: {{ isset($startDate) ? $startDate : 'N/A' }} al
            {{ isset($endDate) ? $endDate : 'N/A' }}
        </div>
        <div class="generated">
            Generado el: {{ $generateAt ?? 'Fecha no disponible' }}
        </div>
    </div>

    @foreach ($orders as $order)
        <table>
            <tr class="order-header">
                <td colspan="5">
                    Orden # {{ $order->id }} - Proveedor: {{ $order->supplier_name ?? 'Sin proveedor' }} -
                    Fecha: {{ Carbon\Carbon::parse($order->order_date)->format('d/m/Y') }}
                </td>
            </tr>
            <tr>
                <th>Insumo</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
                <th>Lote N°</th>
            </tr>

            @foreach ($order->batches as $batch)
                <tr>
                    <td>{{ $batch->input->name }}</td>
                    <td>{{ $batch->quantity_total }} {{ $batch->unit }}</td>
                    <td>${{ number_format($batch->unit_price, 0) }}</td>
                    <td>${{ number_format($batch->subtotal_price, 0) }}</td>
                    <td>{{ $batch->batch_number ?? 'N/A' }}</td>
                </tr>
            @endforeach

            <tr class="order-total">
                <td colspan="4">Total Compra: </td>
                <td>${{ number_format($order->order_total, 0) }}</td>
            </tr>
        </table>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="grand-total">
        Total de las Compras Realizadas: ${{ number_format($totalOrders, 0) }}
    </div>
</body>

</html>
