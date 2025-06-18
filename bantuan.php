<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'connectdb.php';

// Cegah akses tanpa login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($query);

// Cegah session palsu atau user yang sudah dihapus
if (!$query || mysqli_num_rows($query) === 0) {
  session_destroy();
  header("Location: daftar.php");
  exit;
}

// Hapus laporan (admin)
if (isset($_GET['hapus'])) {
  $id = intval($_GET['hapus']);
  mysqli_query($conn, "DELETE FROM reports WHERE id = $id");
  header("Location: bantuan.php");
  exit;
}

// Cek status (user/admin)
$is_admin = strtolower($user['status']) === 'admin';
$showSuccess = isset($_GET['success']) && $_GET['success'] == 1;

// Jeda waktu laporan
$limit_seconds = 3600;
$limit_reached = false;
$last_time = 0;

// Pengecekan batas waktu (user)
if (!$is_admin) {
  $last_report_query = mysqli_query($conn, "
    SELECT created_at FROM reports 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 1
  ");

  if ($last_report_query && mysqli_num_rows($last_report_query) > 0) {
    $last = mysqli_fetch_assoc($last_report_query);

    try {
      $dt = new DateTime($last['created_at']);
      $last_time = $dt->getTimestamp();
      $now = time();
      $selisih = $now - $last_time;

      if ($limit_seconds > 0 && $selisih < $limit_seconds) {
        $limit_reached = true;
      }
    }
    catch (Exception $e) {}
  }
}

// Baca semua laporan (admin)
else {
  $all_reports = mysqli_query($conn, "
    SELECT r.*, u.name 
    FROM reports r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
  ");
}

// Kirim laporan (user)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_admin && !$limit_reached && isset($_POST['title'], $_POST['message'])) {
  $title   = mysqli_real_escape_string($conn, $_POST['title']);
  $message = mysqli_real_escape_string($conn, $_POST['message']);

  // Simpan laporan
  $insert = mysqli_query($conn, "
    INSERT INTO reports (user_id, title, message, created_at)
    VALUES ($user_id, '$title', '$message', NOW())
  ");

  if ($insert) {
    header("Location: bantuan.php?success=1");
    exit;
  } 
  else {
    echo "<script>alert('Gagal mengirim laporan.');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Laporkan Masalah</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="icon" href="Media/NeoSave.ico" type="image/x-icon" />
  <script src="script.js" defer></script>
</head>
<body>
  <div class="container">

    <aside class="sidebar">

      <div class="brand-school">SMKN 2 BANDUNG</div>
      <a href="index.php" class="brand-logo">
        <img src="Media/NeoSave.ico" alt="Logo" class="brand-icon">
        <p>NeoSave</p>
      </a>

      <nav>
        <ul>
          <li><a href="index.php">Beranda</a></li>
          <li><a href="wishlist.php">Wishlist</a></li>
          <li><a href="catatan.php">Catatan</a></li>
          <li><a href="bantuan.php">Bantuan</a></li>
        </ul>
      </nav>

      <div class="balance-summary">
        <p>Total Tabungan</p>
        <h3>Rp<?= number_format($user['balance'], 0, ',', '.') ?></h3>
      </div>

    </aside>

    <main class="main-content">

      <div class="top-bar">

        <div class="profile-menu">

          <div class="profile">
            <span class="account-name"><?= htmlspecialchars($user['name']) ?></span>
            <img src="Profiles/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" />
          </div>

          <div class="dropdown-menu">
            <a href="profil.php" class="dropdown-option">Kelola Profil</a>
            <a href="logout.php" class="dropdown-option logout">Logout</a>
          </div>

        </div>

      </div>

      <div class="support-form">

        <?php if ($is_admin): ?>
          <h2>Daftar Laporan Pengguna</h2>

          <?php if (mysqli_num_rows($all_reports) === 0): ?>
            <p>Tidak ada laporan yang tersedia.</p>

          <?php else: ?>

            <table style="width:1000px; border-collapse:collapse; margin-top:10px;">

              <thead>

                <tr style="background:#2c3e50; color:white;">
                  <th style="padding:8px; border:1px solid #ccc; width:15%;">Pengguna</th>
                  <th style="padding:8px; border:1px solid #ccc; width:20%;">Judul</th>
                  <th style="padding:8px; border:1px solid #ccc; width:50%;">Pesan</th>
                  <th style="padding:8px; border:1px solid #ccc; width:15%;">Tanggal</th>
                </tr>
              
              </thead>

              <tbody>

                <?php while ($report = mysqli_fetch_assoc($all_reports)): ?>

                  <tr style="background:none;">
                    <td style="background:#ffffff; padding:8px; border:1px solid #ccc; word-break:break-word;">
                      <?= htmlspecialchars($report['name']) ?></td>
                    <td style="background:#ffffff; padding:8px; border:1px solid #ccc; word-break:break-word;">
                      <?= htmlspecialchars($report['title']) ?></td>
                    <td style="background:#ffffff; padding:8px; border:1px solid #ccc; word-break:break-word;">
                      <?= htmlspecialchars($report['message']) ?></td>
                    <td style="background:#ffffff; padding:8px; border:1px solid #ccc;">
                      <?= date('d M Y H:i', strtotime($report['created_at'])) ?></td>
                    <td style="padding-left: 10px; background: none; border: none;">

                      <form method="get" onsubmit="return confirm('Konfirmasi dan hapus laporan?');">
                        <input type="hidden" name="hapus" value="<?= $report['id'] ?>">
                        <button type="submit" class="check-icon" style="background-color:none;">✅</button>

                      </form>

                    </td>
                  </tr>

                <?php endwhile; ?>

              </tbody>

            </table>
          <?php endif; ?>

        <?php elseif ($showSuccess): ?>
          <div class="alert-success" 
               style="background: #dff0d8; padding: 10px; border-radius: 6px; color: #3c763d; margin-bottom: 15px;">
            Terima kasih! Laporanmu sudah terkirim dengan sukses.
          </div>

        <?php elseif ($limit_reached): ?>
          <div class="alert-warning" 
               style="background: #f8d7da; padding: 10px; border-radius: 6px; color: #721c24; margin-bottom: 15px;">
            Kamu hanya bisa mengirim 1 laporan setiap 1 jam. Silakan coba lagi nanti.
          </div>

        <?php else: ?>
          <h2>Butuh bantuan? Tuliskan kritik & saranmu disini:</h2>

          <form method="POST" action="bantuan.php" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="text" name="title" class="support-title" placeholder="Pembahasan..." required maxlength="100">
            <textarea name="message" class="support-desc" placeholder="Keterangan..." required maxlength="500"></textarea>
            <button type="submit" class="support-submit">Kirim</button>
          </form>

        <?php endif; ?>

      </div>

    </main>

  </div>
</body>
<footer class="site-footer">
  <div class="footer-container">
    <p>&copy; 2025 <b>NeoSave</b>. All rights reserved.</p>
  </div>
</footer>
</html>
