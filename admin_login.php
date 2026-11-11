<?php
// admin_login.php
session_start();
require_once 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT user_id, nim, password_hash, role, failed_attempts, locked_until FROM users WHERE nim = :u AND role = 'admin'");
        $stmt->execute(['u' => $username]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Cek apakah akun terkunci karena salah login 3x
            if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                $sisaMenit = ceil((strtotime($admin['locked_until']) - time()) / 60);
                $error = "Akun terkunci karena 3x gagal login! Coba lagi dalam $sisaMenit menit.";
            } elseif (password_verify($password, $admin['password_hash'])) {
                // Login berhasil -> Reset failed attempts
                $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = :id")
                    ->execute(['id' => $admin['user_id']]);

                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['user_id'];
                $_SESSION['admin_user'] = $admin['nim'];
                $_SESSION['role']       = 'admin';

                header("Location: admin_panel.php");
                exit;
            } else {
                // Password salah -> Tambah counter percobaan gagal
                $attempts = $admin['failed_attempts'] + 1;
                if ($attempts >= 3) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $pdo->prepare("UPDATE users SET failed_attempts = :att, locked_until = :lu WHERE user_id = :id")
                        ->execute(['att' => $attempts, 'lu' => $lockUntil, 'id' => $admin['user_id']]);
                    catatSecurityLog($pdo, 'ADMIN_LOCKED', "Admin:$username", 'ACCOUNT_LOCKED');
                    $error = "Terlalu banyak percobaan gagal. Akun dikunci 15 menit!";
                } else {
                    $pdo->prepare("UPDATE users SET failed_attempts = :att WHERE user_id = :id")
                        ->execute(['att' => $attempts, 'id' => $admin['user_id']]);
                    $sisa = 3 - $attempts;
                    $error = "Password salah! Sisa percobaan: $sisa kali.";
                }
            }
        } else {
            $error = "Kredensial panitia tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>VOTERA - Portal Panitia</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #020617; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .box { background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 2.5rem; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.6); }
        h2 { color: #f59e0b; font-size: 1.4rem; margin-bottom: 0.3rem; }
        p { color: #64748b; font-size: 0.85rem; margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.4rem; color: #cbd5e1; }
        input { width: 100%; padding: 0.8rem; border-radius: 6px; border: 1px solid #334155; background: #020617; color: #fff; margin-bottom: 1.2rem; outline: none; }
        input:focus { border-color: #f59e0b; }
        button { width: 100%; padding: 0.85rem; background: #d97706; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        button:hover { background: #b45309; }
        .alert { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-bottom: 1.2rem; }
    </style>
</head>
<body>
    <div class="box">
        <h2>PORTAL PANITIA</h2>
        <p>Akses terbatas panitia pemilihan umum</p>

        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Username Panitia</label>
            <input type="text" name="username" required autocomplete="off">

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Autentikasi Masuk</button>
        </form>

        <div style="text-align:center; margin-top:1.5rem;">
            <a href="index.php" style="color:#64748b; font-size:0.8rem; text-decoration:none;">&larr; Halaman Bilik Pemilih</a>
        </div>
    </div>
</body>
</html>