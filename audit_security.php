<?php
// audit_security.php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}
require_once 'koneksi.php';

// Ambil riwayat log deteksi intrusi (terbaru paling atas)
$stmt = $pdo->query("SELECT log_id, event_type, ip_address, payload_input, status_response, created_at 
                     FROM security_logs 
                     ORDER BY log_id DESC 
                     LIMIT 50");
$logs = $stmt->fetchAll();

// Hitung total insiden keamanan berdasarkan jenis
$summaryStmt = $pdo->query("SELECT event_type, COUNT(*) as total FROM security_logs GROUP BY event_type");
$summary = $summaryStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>VOTERA - Audit Keamanan & Deteksi Intrusi (IDS)</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; padding: 2rem 1rem; }
        .container { max-width: 1050px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #334155; padding-bottom: 1rem; }
        .nav a { color: #94a3b8; text-decoration: none; margin-left: 1rem; font-size: 0.9rem; }
        .nav a:hover { color: #38bdf8; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: #1e293b; border: 1px solid #334155; padding: 1.25rem; border-radius: 10px; }
        .stat-title { color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        .stat-val { font-size: 1.7rem; font-weight: bold; color: #f87171; margin-top: 0.3rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #334155; padding: 0.75rem 1rem; text-align: left; font-size: 0.85rem; }
        th { background: #0f172a; color: #38bdf8; font-weight: 600; }
        .badge { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; display: inline-block; }
        .badge-danger { background: #7f1d1d; color: #fecaca; }
        .badge-warning { background: #78350f; color: #fde68a; }
        .badge-info { background: #1e3a8a; color: #bfdbfe; }
        .code-payload { font-family: monospace; background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2>AUDIT TRAIL & LOG KEAMANAN (IDS)</h2>
                <p style="color:#94a3b8; font-size:0.85rem; margin-top:3px;">Pemantauan Upaya Anomali & Rekam Jejak Sistem Forensik</p>
            </div>
            <div class="nav">
                <a href="admin_panel.php">&larr; Panel DPT</a>
                <a href="live_count.php">Live Count</a>
                <a href="logout.php" style="color:#ef4444;">Keluar</a>
            </div>
        </div>

        <!-- Rekapitulasi Ancaman -->
        <div class="summary-grid">
            <div class="stat-card">
                <div class="stat-title">Total Rekam Log</div>
                <div class="stat-val" style="color:#38bdf8;"><?php echo count($logs); ?></div>
            </div>
            <?php foreach ($summary as $s): ?>
                <div class="stat-card">
                    <div class="stat-title"><?php echo htmlspecialchars($s['event_type']); ?></div>
                    <div class="stat-val"><?php echo (int)$s['total']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabel Log Forensik -->
        <div class="card">
            <h3 style="margin-bottom:1rem; font-size:1.1rem; color:#cbd5e1;">50 Aktivitas / Insiden Terakhir</h3>
            <table>
                <thead>
                    <tr>
                        <th>Waktu (Timestamp)</th>
                        <th>Alamat IP</th>
                        <th>Kategori Event</th>
                        <th>Muatan (Payload Input)</th>
                        <th>Tindakan Sistem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#94a3b8;">Belum ada insiden atau aktivitas anomali yang tercatat. Sistem beroperasi normal.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): 
                            $badgeClass = 'badge-info';
                            if (in_array($l['event_type'], ['SQLI_ATTEMPT', 'DOUBLE_VOTE_BLOCKED', 'CSRF_BLOCKED'])) {
                                $badgeClass = 'badge-danger';
                            } elseif (in_array($l['event_type'], ['ADMIN_LOCKED', 'REUSED_TOKEN_LOGIN'])) {
                                $badgeClass = 'badge-warning';
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($l['created_at']); ?></td>
                                <td><code><?php echo htmlspecialchars($l['ip_address']); ?></code></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($l['event_type']); ?></span></td>
                                <td><span class="code-payload"><?php echo htmlspecialchars($l['payload_input']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($l['status_response']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>