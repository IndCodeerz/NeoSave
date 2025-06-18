<?php
session_start();
include 'connectdb.php';

$errors = []; // Wadah error

// Wadah input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
  $password = $_POST['password'];

  // Validasi nama
  if (empty($name)) {
    $errors[] = 'Nama tidak boleh kosong';
  }

  // Validasi password
  if (empty($password)) {
    $errors[] = 'Password tidak boleh kosong';
  }

  // Pencocokan data
  if (empty($errors)) {
    $query = mysqli_query($conn, "SELECT * FROM users WHERE name = '$name'");
    $user = mysqli_fetch_assoc($query);

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['user_status'] = $user['status'];
      header("Location: index.php");
      exit;
    } 
    else {
      $errors[] = 'Nama, atau password salah';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - NeoSave</title>
  <link rel="stylesheet" href="style2.css" />
  <link rel="icon" href="Media/NeoSave.ico" type="image/x-icon" />
</head>
<body>
  <div class="auth-container">

    <div class="auth-box">
      <h2>Login ke NeoSave</h2>
      
      <?php foreach ($errors as $e): ?>
        <div class="error-message"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST" action="">
        <input type="text" name="name" placeholder="Nama..." value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">
        <input type="password" name="password" placeholder="Password..." value="<?= isset($password) ? htmlspecialchars($password) : '' ?>">
        <button type="submit">Masuk</button>
        <p class="toggle-text">Belum punya akun? <a href="daftar.php">Daftar</a></p>
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
