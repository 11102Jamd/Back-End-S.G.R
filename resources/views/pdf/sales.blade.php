<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Estilos generales */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #ffffff
        }

        /* Contenedor principal */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        /* Encabezado con logo */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3E608C;
            position: relative;
        }

        .logo-container {
            width: 150px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 0px solid #e0e0e0;
            padding: 5px;
            background-color: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
        }

        .logo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            color: #999;
            font-size: 12px;
            text-align: center;
            padding: 5px;
        }

        .header-info {
            text-align: center;
            flex-grow: 1;
            padding-left: 20px;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #176FA6;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .period {
            font-size: 14px;
            margin-bottom: 5px;
            color: #666;
        }

        .generated {
            font-size: 12px;
            color: #888;
        }

        /* Tabla de órdenes */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #176FA6;
            color: white;
            font-weight: 600;
        }

        .sale-header {
            background-color: #e9f2ff;
            font-weight: bold;
        }

        .sale-header td {
            border-bottom: 2px solid #176FA6;
            font-size: 16px;
            padding: 12px 10px;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f1f7ff;
        }

        .sale-total {
            text-align: right;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .sale-total td {
            border-top: 2px solid #ddd;
        }

        /* Total general */
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            margin-top: 30px;
            padding: 15px;
            background-color: #176FA6;
            color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Salto de página para impresión */
        .page-break {
            page-break-after: always;
        }
    </style>
    <title>Reporte de Ventas</title>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <div class="logo-placeholder">
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('logoSGR.jpeg'))) }}"
                        alt="logo" width="200" height="80">
                </div>
            </div>
            <div class="header-info">
                <div class="company-name">Pan de Yuca Que Rico</div>
                <div class="report-title">Reporte de Ventas</div>
                <div class="period">
                    Período: {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}
                </div>
                <div class="generated">
                    Generado el: {{ $generateAt ?? 'Fecha no disponible' }}
                </div>
            </div>
        </div>

        @foreach ($sales as $sale)
            <table>
                <tr class="sale-header">
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
                        <td>{{ number_format($salesProduct->quantity_requested, 0) }} unidades</td>
                        <td class="text-right">${{ number_format($salesProduct->product->unit_price ?? 0, 0) }}</td>
                        <td class="text-right">${{ number_format($salesProduct->subtotal_price, 0) }}</td>
                    </tr>
                @endforeach

                <tr class="sale-total">
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
    </div>
</body>

</html>
