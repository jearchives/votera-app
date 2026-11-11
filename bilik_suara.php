<?php
// bilik_suara.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit;
}
require_once 'koneksi.php';

$_SESSION['csrf_token'] = bin2hex(random_bytes(16));

$stmt = $pdo->query("SELECT id_kandidat, nomor_urut, nama_kandidat, visi_misi FROM kandidat ORDER BY nomor_urut ASC");
$kandidatList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>VOTERA - Bilik Pencoblosan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; padding: 2rem 1rem; }
        .container { max-width: 860px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #334155; padding-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; }
        .card { background: #1e293b; border: 2px solid #334155; border-radius: 12px; padding: 1.8rem; cursor: pointer; transition: 0.2s; position: relative; }
        .card:hover { border-color: #38bdf8; }
        .card.active { border-color: #38bdf8; background: #1e3a5f; }
        .card input { position: absolute; top: 1.8rem; right: 1.8rem; width: 1.3rem; height: 1.3rem; cursor: pointer; }
        .badge { background: #0284c7; color: #fff; font-size: 0.85rem; font-weight: bold; padding: 4px 10px; border-radius: 4px; display: inline-block; margin-bottom: 1rem; }
        .btn-vote { width: 100%; margin-top: 2rem; padding: 1rem; background: #16a34a; color: #fff; font-size: 1.1rem; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
        .btn-vote:hover { background: #15803d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2>Bilik Pencoblosan Resmi</h2>
                <p style="color:#94a3b8; font-size:0.85rem;">Pemilih Sah: NIM <?php echo htmlspecialchars($_SESSION['nim']); ?></p>
            </div>
            <a href="logout.php" style="color:#ef4444; text-decoration:none; font-size:0.85rem;">Batal / Keluar</a>
        </div>

        <form action="proses_vote.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="grid">
                <?php foreach ($kandidatList as $k): ?>
                <label class="card" onclick="pilih(this)">
                    <input type="radio" name="id_kandidat" value="<?php echo (int)$k['id_kandidat']; ?>" required>
                    <span class="badge">PASLON 0<?php echo (int)$k['nomor_urut']; ?></span>
                    <h3 style="margin-bottom:0.75rem; color:#fff; font-size:1.25rem;"><?php echo htmlspecialchars($k['nama_kandidat']); ?></h3>
                    <p style="color:#cbd5e1; font-size:0.85rem; line-height:1.6; white-space:pre-line;"><?php echo htmlspecialchars($k['visi_misi']); ?></p>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn-vote" onclick="return confirm('Pilihan tidak dapat diubah setelah dikirim. Kirim suara sekarang?')">
                Kirim Hak Suara Saya
            </button>
        </form>
    </div>

    <script>
        function pilih(el) {
            document.querySelectorAll('.card').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
        }
    </script>
</body>
</html>