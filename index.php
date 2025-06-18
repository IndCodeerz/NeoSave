<?php
session_start();
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Beranda</title>
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

    <main class="main-content home-page">

      <div class="top-bar">

        <div class="profile-menu">

          <div class="profile">
            <span class="account-name" style="background-color: rgba(255, 255, 255, 0.1);">
              <?= htmlspecialchars($user['name']) ?>
            </span>

            <img src="Profiles/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" />
          </div>

          <div class="dropdown-menu">
            <a href="profil.php" class="dropdown-option">Kelola Profil</a>
            <a href="logout.php" class="dropdown-option logout">Logout</a>
          </div>

        </div>
        
      </div>

      <div class="content">
        <h1 class="welcome-main">SELAMAT DATANG</h1>
        <p class="welcome-sub">
          Halo,
          <?php
            $name = $user['name'];
            if (strlen($name) > 30) {
              $cut = substr($name, 0, 30);
              echo htmlspecialchars($cut . '...');
            } 
            
            else {
              echo htmlspecialchars($name);
            }
          ?>!
        </p>

        <div class="description-box">
          Apa itu NeoSave? <b>NeoSave</b> adalah sebuah website khusus untuk pelajar mengelola uang dengan lebih 
          efisien, dengan fitur-fitur seperti wishlist, catatan keuangan, dan tampilan jumlah tabungan.
        </div>

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
