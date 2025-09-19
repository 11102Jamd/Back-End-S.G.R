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

        .production-header {
            background-color: #e9f2ff;
            font-weight: bold;
        }

        .order-header td {
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

        .price-product {
            text-align: right;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .price-product td {
            border-top: 2px solid #ddd;
        }

        .production-total {
            text-align: right;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .production-total td {
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
    <title>Reporte de Producciones</title>
</head>

<body>
    <div class="container">
        {{-- Encabezado del Pdf --}}
        <div class="header">
            <div class="logo-container">
                <div class="logo-placeholder">
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('logoSGR.jpeg'))) }}"
                        alt="logo" width="200" height="80">
                </div>
            </div>
            <div class="header-info">
                <div class="company-name">Pan de Yuca Que Rico</div>
                <div class="report-title">Reporte de Producciones</div>
            </div>
            <div class="period">
                Período: {{ $startDate ?? 'N/A' }} al {{ $endDate ?? 'N/A' }}
            </div>
            <div class="generated">
                Generado el: {{ $generateAt ?? 'Fecha no disponible' }}
            </div>
        </div>

        {{-- Iterar sobre todas las ordenes --}}
        @foreach ($productions as $production)
            @php
                $firstProductProduction = $production->productProductions->first();
            @endphp

            <table>
                <tr class="production-header">
                    <td colspan="4">
                        Producción N° {{ $production->id }} -
                        Receta: {{ $production->recipe->recipe_name ?? 'Sin Receta' }} -
                        Producto: {{ $firstProductProduction->product->product_name ?? 'Sin producto' }} -
                        Fecha: {{ Carbon\Carbon::parse($production->production_date)->format('d/m/Y') }}
                        Ganancia: {{ $firstProductProduction->profit_margin_porcentage ?? 'Sin Ganancia' }} %
                    </td>
                </tr>
                <tr>
                    <th>Insumo</th>
                    <th>Cantidad Gastada</th>
                    <th>Subtotal</th>
                    <th>Lote N°</th>
                </tr>
                {{-- Detalle de produccion --}}
                @foreach ($production->productionConsumptions as $consumption)
                    <tr>
                        <td>{{ $consumption->batch->input->name ?? 'N/A' }}</td>
                        <td>{{ number_format($consumption->quantity_used, 0) }}
                            {{ $consumption->batch->unit_converted }}</td>
                        <td class="text-right">${{ number_format($consumption->total_cost, 0) }}</td>
                        <td>{{ $consumption->batch->batch_number ?? 'N/A' }}</td>
                    </tr>
                @endforeach

                <tr class="price-product">
                    <td colspan="3">Precio por producto</td>
                    <td class="text-right">${{ number_format($production->price_for_product ?? 0, 0) }}</td>
                </tr>
                <tr class="production-total">
                    <td colspan="3">Total Producción:</td>
                    <td class="text-right">${{ number_format($production->total_cost, 0) }}</td>
                </tr>
            </table>

            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach

        {{-- Total de producciones realizadas --}}
        <div class="grand-total">
            Total de las Producciones: ${{ number_format($totalProductions, 0) }}
        </div>
    </div>
</body>

</html>
