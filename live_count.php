<?php
// live_count.php
require_once 'koneksi.php';

// Ambil total suara masuk
$totalSuara = (int)$pdo->query("SELECT COUNT(*) FROM transaksi_suara")->fetchColumn();

// Ambil perolehan masing-masing kandidat
$stmt = $pdo->query("
    SELECT k.nomor_urut, k.nama_kandidat, COUNT(ts.id_suara) AS perolehan
    FROM kandidat k
    LEFT JOIN transaksi_suara ts ON k.id_kandidat = ts.id_kandidat
    GROUP BY k.id_kandidat, k.nomor_urut, k.nama_kandidat
    ORDER BY k.nomor_urut ASC
");
$hasil = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <title>VOTERA - Rekapitulasi Suara Real-Time</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; padding: 2rem 1rem; }
        .container { max-width: 750px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 2.5rem; }
        .header h1 { color: #38bdf8; font-size: 1.8rem; }
        .total-badge { background: #1e293b; border: 1px solid #334155; padding: 0.5rem 1rem; border-radius: 20px; display: inline-block; margin-top: 0.5rem; font-size: 0.9rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .bar-container { background: #0f172a; border-radius: 8px; height: 16px; overflow: hidden; margin-top: 1rem; }
        .bar-fill { background: #0284c7; height: 100%; transition: width 0.5s ease; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>REKAPITULASI SUARA ELEKTRONIK</h1>
            <div class="total-badge">Total Suara Masuk: <strong><?php echo $totalSuara; ?></strong></div>
        </div>

        <?php foreach ($hasil as $h): 
            $persen = $totalSuara > 0 ? round(($h['perolehan'] / $totalSuara) * 100, 1) : 0;
        ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span style="color:#94a3b8; font-size:0.8rem; font-weight:bold;">PASLON 0<?php echo $h['nomor_urut']; ?></span>
                        <h3 style="color:#fff; margin-top:2px;"><?php echo htmlspecialchars($h['nama_kandidat']); ?></h3>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:1.4rem; font-weight:bold; color:#38bdf8;"><?php echo $h['perolehan']; ?></span>
                        <span style="color:#94a3b8; font-size:0.85rem;">(<?php echo $persen; ?>%)</span>
                    </div>
                </div>
                <div class="bar-container">
                    <div class="bar-fill" style="width: <?php echo $persen; ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="text-align:center; margin-top:2rem;">
            <a href="admin_panel.php" style="color:#64748b; font-size:0.85rem; text-decoration:none;">&larr; Kembali ke Panel Admin</a>
        </div>
    </div>
</body>
</html>