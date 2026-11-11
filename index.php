<?php
// index.php
session_start();
require_once 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim   = trim($_POST['nim'] ?? '');
    $token = trim($_POST['token'] ?? '');

    // Sensor proteksi input karakter SQLi
    if (preg_match("/('|\"|--|UNION|OR\s+1=1)/i", $nim . $token)) {
        catatSecurityLog($pdo, 'SQLI_ATTEMPT', "NIM:$nim", 'BLOCKED');
        $error = "Pola karakter terlarang terdeteksi!";
    } elseif (!empty($nim) && !empty($token)) {
        // 1. Cek apakah NIM terdaftar sebagai pemilih
        $stmtUser = $pdo->prepare("SELECT user_id, nim, role FROM users WHERE nim = :nim AND role = 'user'");
        $stmtUser->execute(['nim' => $nim]);
        $user = $stmtUser->fetch();

        if ($user) {
            $tokenHash = hash('sha256', $token);
            // 2. Cocokkan token dengan user_id terkait
            $stmtToken = $pdo->prepare("SELECT token_id, status_pakai FROM token_voting WHERE user_id = :uid AND token_hash = :th");
            $stmtToken->execute(['uid' => $user['user_id'], 'th' => $tokenHash]);
            $tokenData = $stmtToken->fetch();

            if ($tokenData) {
                if ($tokenData['status_pakai'] == 0) {
                    // Validasi lolos: Token sah & belum dipakai
                    session_regenerate_id(true);
                    $_SESSION['user_id']  = $user['user_id'];
                    $_SESSION['nim']      = $user['nim'];
                    $_SESSION['role']     = 'user';
                    $_SESSION['token_id'] = $tokenData['token_id'];

                    header("Location: bilik_suara.php");
                    exit;
                } else {
                    catatSecurityLog($pdo, 'REUSED_TOKEN_LOGIN', "NIM:$nim", 'DENIED');
                    $error = "Hak suara untuk NIM dan Token ini sudah pernah digunakan!";
                }
            } else {
                catatSecurityLog($pdo, 'INVALID_TOKEN_FOR_NIM', "NIM:$nim", 'INVALID');
                $error = "Token voting tidak cocok dengan NIM yang dimasukkan!";
            }
        } else {
            catatSecurityLog($pdo, 'NIM_NOT_FOUND', "NIM:$nim", 'NOT_FOUND');
            $error = "NIM tidak terdaftar dalam Daftar Pemilih Tetap (DPT)!";
        }
    } else {
        $error = "NIM dan Token voting wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOTERA - Portal Pemilihan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .box { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 2.5rem; width: 100%; max-width: 440px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo h1 { font-size: 2rem; color: #38bdf8; letter-spacing: 1px; }
        .logo p { font-size: 0.85rem; color: #94a3b8; margin-top: 4px; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: #cbd5e1; font-weight: 500; }
        input[type="text"] { width: 100%; padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid #475569; background: #0f172a; color: #fff; font-size: 0.95rem; outline: none; }
        input[type="text"]:focus { border-color: #38bdf8; }
        .token-field { text-transform: uppercase; font-family: monospace; letter-spacing: 2px; }
        .btn-submit { width: 100%; padding: 0.85rem; background: #0284c7; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        .btn-submit:hover { background: #0369a1; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.2rem; }
        .admin-link { text-align: center; margin-top: 1.5rem; border-top: 1px solid #334155; padding-top: 1rem; }
        .admin-link a { color: #64748b; font-size: 0.8rem; text-decoration: none; }
        .admin-link a:hover { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo">
            <h1>VOTERA</h1>
            <p>Sistem Pemungutan Suara Elektronik Terenkripsi</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nomor Induk Mahasiswa (NIM)</label>
                <input type="text" name="nim" placeholder="Contoh: 230101009" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Token Voting Rahasia</label>
                <input type="text" name="token" class="token-field" placeholder="16 DIGIT TOKEN" required autocomplete="off">
            </div>

            <button type="submit" class="btn-submit">Verifikasi & Masuk Bilik Suara</button>
        </form>

        <div class="admin-link">
            <a href="admin_login.php">Portal Panitia & Administrator &rarr;</a>
        </div>
    </div>
</body>
</html>