<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Formato de Radicación de Facturas</title>
    <style>
        @page {
            margin: 20px 30px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: #000;
            background-color: #fff;
            line-height: 1.3;
            font-size: 10pt;
        }

        h1 {
            font-size: 16pt;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20pt;
            text-transform: uppercase;
        }

        .header-section {
            margin-bottom: 20pt;
            border-bottom: 2px solid #000;
            padding-bottom: 10pt;
        }

        .grid-table {
            width: 100%;
            border: none;
            margin-bottom: 10pt;
        }

        .grid-table td {
            border: none;
            padding: 4pt 0;
            vertical-align: top;
        }

        .header-label {
            font-weight: bold;
            font-size: 9pt;
            display: block;
        }

        .header-value {
            font-size: 10pt;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 2px;
            display: block;
        }

        .summary-box {
            background-color: #e8f4f8;
            border: 2px solid #0066cc;
            padding: 12pt;
            margin-bottom: 15pt;
            border-radius: 4px;
        }

        .summary-table {
            width: 100%;
            border: none;
        }

        .summary-table td {
            border: none;
            padding: 0;
        }

        .summary-label {
            font-weight: bold;
            color: #0066cc;
        }

        .summary-value {
            font-size: 13pt;
            font-weight: bold;
            margin-left: 5pt;
        }

        .val-green {
            color: #006600;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 20pt;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 6pt;
            text-align: left;
            vertical-align: middle;
        }

        table.data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .uuid {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
        }

        .footer {
            margin-top: 30pt;
            padding-top: 10pt;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>

<body>
    @php
        $items = is_iterable($data ?? []) ? collect($data) : (isset($data) ? collect([$data]) : collect([]));
        $total = $items->sum('total_value');
        $count = $items->count();
        $firstItem = $items->first();
    @endphp

    <div class="header-section">
        <h1>Formato de Radicación de Facturas</h1>

        <table class="grid-table">
            <tr>
                <td width="50%">
                    <span class="header-label">Razón Social del Prestador:</span>
                    <span class="header-value">{{ $firstItem->third->name ?? 'N/A' }}</span>
                </td>
                <td width="50%">
                    <span class="header-label">NIT del Prestador:</span>
                    <span class="header-value">{{ $firstItem->third->nit ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="header-label">Fecha de Generación:</span>
                    <span class="header-value">{{ now()->translatedFormat('d \d\e F \d\e Y') }}</span>
                </td>
                <td></td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td width="40%">
                    <span class="summary-label">Cantidad de Facturas:</span>
                    <span class="summary-value">{{ $count }}</span>
                </td>
                <td width="60%">
                    <span class="summary-label">Valor Total Radicado:</span>
                    <span class="summary-value val-green">{{ formatNumber($total) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <p style="font-size: 10pt; margin-bottom: 10pt">
        A continuación se relaciona el detalle de las facturas radicadas</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%">Radicado (UUID)</th>
                <th style="width: 15%">No. Factura</th>
                <th style="width: 20%">Valor Factura</th>
                <th style="width: 15%">Contrato</th>
                <th style="width: 20%">Fecha Radicación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="uuid">{{ $item->id }}</td>
                    <td class="text-center">{{ $item->invoice_number ?? 'N/A' }}</td>
                    <td class="text-right">{{ formatNumber($item->total_value ?? 0) }}</td>
                    <td class="text-center">{{ $item->filingInvoice->contract->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->created_at ? $item->created_at->format('Y-m-d') : 'N/A' }}</td>
                </tr>
            @endforeach
            <!-- Total Row -->
            <tr style="background-color: #f9f9f9; font-weight: bold">
                <td colspan="2" class="text-right">TOTAL RADICADO:</td>
                <td class="text-right">{{ formatNumber($total) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>



    <div class="footer">
        <p style="margin: 0;">
            Documento generado electrónicamente - No requiere firma</p>
    </div>
</body>

</html>