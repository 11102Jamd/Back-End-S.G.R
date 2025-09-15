<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .order-header {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .order-total {
            background-color: #f8f9fa;
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
        .text-right {
            text-align: right;
        }
    </style>
    <title>Reporte de Ventas</title>
</head>
<body>
    <div class="header">
        <div class="title">Reporte de Ventas</div>
        <div class="period">
            Período: {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}
        </div>
        <div class="generated">
            Generado el: {{ $generateAt ?? 'Fecha no disponible' }}
        </div>
    </div>

    @foreach ($sales as $sale)
        <table>
            <tr class="order-header">
                <td colspan="4">
                    Venta N° {{ $sale->id }}
                    Usuario: {{ $sale->user->name ?? 'Sin Usuario' }} -
                    Fecha: {{ Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y') }}
                </td>
            </tr>
            <tr>
                <th>Producto</th>
                <th>Cantidad Solicitada</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>

            @foreach ($sale->saleProducts as $salesProduct)
                <tr>
                    <td>{{ $salesProduct->product->product_name ?? 'N/A' }}</td>
                    <td>{{ $salesProduct->quantity_requested }} unidades</td>
                    <td class="text-right">${{ number_format($salesProduct->product->unit_price ?? 0, 0) }}</td>
                    <td class="text-right">${{ number_format($salesProduct->subtotal_price, 0) }}</td>
                </tr>
            @endforeach

            <tr class="order-total">
                <td colspan="3">Total Venta:</td>
                <td class="text-right">${{ number_format($sale->sale_total, 0) }}</td>
            </tr>
        </table>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="grand-total">
        Total de las Ventas: ${{ number_format($totalSales, 0) }}
    </div>
</body>
</html>
