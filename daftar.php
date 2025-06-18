<?php
session_start();
include 'connectdb.php';

$errors = []; // Wadah error

// Wadah input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
  $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
  $password_plain = $_POST['password'];

  // Validasi nama
  if (empty($name)) {
    $errors[] = 'Nama tidak boleh kosong';
  }
  else if (strlen($name) > 50) {
    $errors[] = 'Nama tidak boleh lebih dari 50 karakter';
  }

  // Validasi email
  if (empty($email)) {
    $errors[] = 'Email tidak boleh kosong';
  }
  else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email tidak valid';
  }
  elseif (strlen($email) > 50) {
    $errors[] = 'Email tidak boleh lebih dari 50 karakter';
  }

  // Validasi password
  if (empty($password_plain)) {
    $errors[] = 'Password tidak boleh kosong';
  }
  else if (strlen($password_plain) < 8) {
    $errors[] = 'Password harus minimal 8 karakter';
  }
  elseif (strlen($password_plain) > 20) {
    $errors[] = 'Password tidak boleh lebih dari 20 karakter';
  }

  // Cek apakah email sudah terdaftar
  $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
  if (mysqli_num_rows($check) > 0) {
    $errors[] = 'Email sudah digunakan';
  }

  // Simpan jika tidak ada error
  if (empty($errors)) {
    $password = password_hash($password_plain, PASSWORD_DEFAULT);
    $insert = mysqli_query($conn, "INSERT INTO users (name, email, password, status) VALUES ('$name', '$email', '$password', 'student')");

    if ($insert) {
      $_SESSION['user_id'] = mysqli_insert_id($conn);
      $_SESSION['user_email'] = $email;
      $_SESSION['user_status'] = 'student';
      header("Location: index.php");
      exit;
    }
    else {
      $errors[] = 'Gagal menyimpan data';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar - NeoSave</title>
  <link rel="stylesheet" href="style2.css" />
  <link rel="icon" href="Media/NeoSave.ico" type="image/x-icon" />
</head>
<body>
  <div class="auth-container">

    <div class="auth-box">
      <h2>Daftar Akun NeoSave</h2>

      <?php foreach ($errors as $e): ?>
        <div class="error-message"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST" action="">
        <input type="text" name="name" placeholder="Nama..." value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
        <input type="email" name="email" placeholder="Email..." value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
        <input type="password" name="password" placeholder="Password..." >
        <button type="submit">Daftar</button>
        <p class="toggle-text">Sudah punya akun? <a href="login.php">Login</a></p>
      </form>

    </div>

  </div>
</body>
<footer class="site-footer">
  <div class="footer-container">
    <p>&copy; 2025 <b>NeoSave</b>. All rights reserved.</p>
  </div>
</footer>
</html>