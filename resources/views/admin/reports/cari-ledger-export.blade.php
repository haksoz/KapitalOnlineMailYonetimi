<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Cari Hesap Dokumu</title>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th>Cari</th>
                <th>Donem</th>
                <th>Tip</th>
                <th>Sozlesme</th>
                <th>Urun</th>
                <th>Islem Tarihi</th>
                <th>Beklenen Alis</th>
                <th>Gerceklesen Alis</th>
                <th>Alis Fark</th>
                <th>Beklenen Satis</th>
                <th>Gerceklesen Satis</th>
                <th>Satis Fark</th>
                <th>Fatura No</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] as $row)
                <tr>
                    <td>{{ $row['cari_unvan'] }}</td>
                    <td>{{ $row['donem'] ?? '' }}</td>
                    <td>{{ $row['hareket_tipi_label'] }}</td>
                    <td>{{ $row['sozlesme_no'] ?? '' }}</td>
                    <td>{{ $row['urun_adi'] ?? '' }}</td>
                    <td>{{ $row['islem_tarihi'] ?? '' }}</td>
                    <td>{{ (float) $row['beklenen_alis_tl'] }}</td>
                    <td>{{ (float) $row['gerceklesen_alis_tl'] }}</td>
                    <td>{{ (float) $row['fark_alis_tl'] }}</td>
                    <td>{{ (float) $row['beklenen_satis_tl'] }}</td>
                    <td>{{ (float) $row['gerceklesen_satis_tl'] }}</td>
                    <td>{{ (float) $row['fark_satis_tl'] }}</td>
                    <td>
                        {{ $row['hareket_tipi'] === 'alis' ? ($row['alis_fatura_no'] ?? '') : ($row['satis_fatura_no'] ?? '') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6">Genel Dip Toplam</td>
                <td>{{ (float) ($report['grandTotals']['beklenen_alis_tl'] ?? 0) }}</td>
                <td>{{ (float) ($report['grandTotals']['gerceklesen_alis_tl'] ?? 0) }}</td>
                <td>{{ (float) ($report['grandTotals']['fark_alis_tl'] ?? 0) }}</td>
                <td>{{ (float) ($report['grandTotals']['beklenen_satis_tl'] ?? 0) }}</td>
                <td>{{ (float) ($report['grandTotals']['gerceklesen_satis_tl'] ?? 0) }}</td>
                <td>{{ (float) ($report['grandTotals']['fark_satis_tl'] ?? 0) }}</td>
                <td colspan="1"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
