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

// Tambah catatan baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'], $_POST['amount'], $_POST['type'])) {
  $note = mysqli_real_escape_string($conn, $_POST['note']);
  $amount = $_POST['amount'];
  $type = $_POST['type'] === 'income' ? 'income' : 'expense';
  $date = date('Y-m-d');

  if (!ctype_digit($amount)) {
    die("Nominal harus berupa angka.");
  }

  $amount = (int)$amount;

  // Pembatasan amount
  if ($amount <= 0 || $amount > 999999999) {
    die("Nominal harus antara 1 sampai 999.999.999");
  }

  // Pembatasan amount maksimal
  if ($type === 'income') {
    $max_balance = 999999999;
    $current_balance = (int)$user['balance'];
    $remaining_space = $max_balance - $current_balance;

    // Jika saldo penuh, menolak catatan baru
    if ($remaining_space <= 0) {
      header("Location: catatan.php");
      exit;
    }

    // Potong jika melebihi batas maksimum
    if ($amount > $remaining_space) {
      $amount = $remaining_space;
    }

    // Income - Menambah balance
    mysqli_query($conn, "INSERT INTO notes (user_id, description, amount, type, date)
                         VALUES ($user_id, '$note', $amount, '$type', '$date')");
    mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE id = $user_id");

  } else {
    // Expense - Mengurangi balance
    mysqli_query($conn, "INSERT INTO notes (user_id, description, amount, type, date)
                         VALUES ($user_id, '$note', $amount, '$type', '$date')");
    mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id = $user_id");
  }

  header("Location: catatan.php");
  exit;
}

// Ambil ulang data user dan catatan
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
$notes = mysqli_query($conn, "SELECT * FROM notes WHERE user_id = $user_id ORDER BY date DESC, id DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Catatan</title>
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

      <section class="notes-section">
        <h2>Catatan Keuanganmu</h2>

        <div class="logs">

          <?php while ($row = mysqli_fetch_assoc($notes)) : ?>

            <div class="log-entry">

              <div class="log-date">[<?= date("j/n/y", strtotime($row['date'])) ?>]</div>
              <div class="log-description"><?= htmlspecialchars($row['description']) ?></div>

              <div class="log-amount <?= $row['type'] === 'income' ? 'income' : 'expense' ?>">
                Rp<?= number_format($row['amount'], 0, ',', '.') ?>
              </div>

            </div>

          <?php endwhile; ?>

        </div>

        <form class="note-input" method="POST" action="catatan.php">
          <input type="text" name="note" class="note-text" placeholder="Deskripsi catatan..." required maxlength="55" autocomplete="off">
          <input type="text" id="amount" name="amount" class="note-amount" placeholder="Masukkan nominal..." required maxlength="9" autocomplete="off">
          
          <select name="type" class="note-type" required>
            <option value="income">+</option>
            <option value="expense">-</option>
          </select>

          <button type="submit" class="submit-note">Simpan</button>
        </form>

      </section>

    </main>

  </div>

  <script>
    const profile = document.querySelector('.profile');
    const dropdown = document.querySelector('.dropdown-menu');
    const amountInput = document.getElementById('amount');

    profile.addEventListener('click', () => {
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
      if (!document.querySelector('.profile-menu').contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });

      amountInput.addEventListener('input', function () {
      // Hapus semua karakter non-angka
      this.value = this.value.replace(/[^0-9]/g, '');

      // Cegah lebih dari 9 digit
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
