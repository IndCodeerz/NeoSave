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

// Tambah, hapus, dan selesaikan wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Hapus wishlist
  if (isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $check = mysqli_query($conn, "SELECT * FROM wishlists WHERE id = $delete_id AND user_id = $user_id");

    if (mysqli_num_rows($check) > 0) {
      mysqli_query($conn, "DELETE FROM wishlists WHERE id = $delete_id");
    }
    header("Location: wishlist.php");
    exit;
  }

  // Selesaikan wishlist
  if (isset($_POST['complete_id'], $_POST['complete_note'], $_POST['complete_amount'])) {
    $wishlist_id = (int) $_POST['complete_id'];
    $note = mysqli_real_escape_string($conn, $_POST['complete_note']);
    $amount = (int) $_POST['complete_amount'];
    $date = date('Y-m-d');

    // Pastikan saldo cukup
    $cek_saldo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM users WHERE id = $user_id"));
    if ($cek_saldo['balance'] >= $amount) {
      mysqli_query($conn, "DELETE FROM wishlists WHERE id = $wishlist_id AND user_id = $user_id");

      // Kurangi saldo
      mysqli_query($conn, "INSERT INTO notes (user_id, description, amount, type, date)
                           VALUES ($user_id, '$note', $amount, 'expense', '$date')");
      mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id = $user_id");
    }

    header("Location: wishlist.php");
    exit;
  }

  // Tambah wishlist baru
  if (isset($_POST['note'], $_POST['amount'])) {
    $note = trim(mysqli_real_escape_string($conn, $_POST['note']));
    $amount = $_POST['amount'];

    if (!ctype_digit($amount) || (int)$amount <= 0) {
      die("Nominal harus angka bulat positif tanpa titik.");
    }

    $amount = (int)$amount;
    $query = "INSERT INTO wishlists (user_id, note, amount) VALUES ($user_id, '$note', $amount)";
    mysqli_query($conn, $query);
    header("Location: wishlist.php");
    exit;
  }
}

// Ambil ulang data wishlist
$wishlists = mysqli_query($conn, "SELECT * FROM wishlists WHERE user_id = $user_id");
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wishlist</title>
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

    <main class="main-content wishlist-page">

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

      <div class="wishlist-header">
        <button class="add-button" id="show-form">+</button>

        <form class="add-form" id="add-form" method="POST" action="wishlist.php" style="display: none;">
          <input type="text" name="note" class="form-note" placeholder="Catatan..." required maxlength="60" autocomplete="off">
          <input type="text" name="amount" class="form-amount" inputmode="numeric" id="amountInput" placeholder="Nominal..." required maxlength="9" autocomplete="off">
          <button type="submit" class="submit-wishlist">Kirim</button>
        </form>

        <span class="add-text" id="add-text">Tambahkan Wishlist</span>
      </div>

      <div class="wishlist-items">

        <?php while ($row = mysqli_fetch_assoc($wishlists)) :
          $note = htmlspecialchars($row['note']);
          $amount = $row['amount'];
          $is_affordable = $user['balance'] >= $amount ? 'affordable' : 'unaffordable';
        ?>

          <div class="wishlist-item-wrapper">

            <div class="wishlist-item">

              <span class="note"><?= $note ?></span>
              <span class="amount <?= $is_affordable ?>">Rp<?= number_format($amount, 0, ',', '.') ?></span>

            </div>

            <?php if ($user['balance'] >= $amount): ?>

              <form method="POST" action="wishlist.php" style="position: absolute; right: -70px;">
                <input type="hidden" name="complete_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="complete_note" value="<?= htmlspecialchars($row['note']) ?>">
                <input type="hidden" name="complete_amount" value="<?= $amount ?>">
                <button type="submit" class="check-icon" onclick="return confirm('Selesaikan wishlist ini?')">✅</button>
              </form>

            <?php endif; ?>

            <form method="POST" action="wishlist.php" style="position: absolute; right: -40px;">
              <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
              <button type="submit" class="trash-icon" onclick="return confirm('Yakin ingin menghapus?')">🗑️</button>
            </form>

          </div>

        <?php endwhile; ?>

      </div>

    </main>

  </div>

  <script>
    const input = document.getElementById("amountInput");

    input.addEventListener("input", function () {
      this.value = this.value.replace(/\D/g, '');
      if (this.value.length > 9) {
        this.value = this.value.slice(0, 9);
      }
    });
  </script>

</body>
<footer class="site-footer">
  <div class="footer-container">
    <p>&copy; 2025 <b>NeoSave</b>. All rights reserved.</p>
  </div>
</footer>
</html>
