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

// Edit profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Hapus foto profil
  if (isset($_POST['delete_picture'])) {

    if ($user['profile_picture'] !== 'default.png') {
      $old_path = "Profiles/" . $user['profile_picture'];
      
      if (file_exists($old_path)) {
        unlink($old_path);
      }
    }
    mysqli_query($conn, "UPDATE users SET profile_picture = 'default.png' WHERE id = $user_id");
    header("Location: profil.php");
    exit;
  }

  // Ubah data lainnya
  if (isset($_POST['name'], $_POST['email'])) {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $sql = "UPDATE users SET name = '$name', email = '$email'";

    // Password
    if (!empty($_POST['password'])) {
      $password = mysqli_real_escape_string($conn, $_POST['password']);

      if (strlen($password) < 8) {
        header("Location: profil.php?error=Password harus minimal 8 karakter.");
        exit;
      }

      $hash = password_hash($password, PASSWORD_DEFAULT);
      $sql .= ", password = '$hash'";
    }

    // Upload gambar
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
      $tmp = $_FILES['profile_picture']['tmp_name'];
      $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp'];

      if (!in_array($ext, $allowed)) {
        header("Location: profil.php?error=Jenis file tidak didukung.");
        exit;
      }

      // Hapus foto lama
      if ($user['profile_picture'] !== 'default.png') {
        $old_path = "Profiles/" . $user['profile_picture'];
        if (file_exists($old_path)) {
          unlink($old_path);
        }
      }

      // Simpan foto baru
      $filename = "user_" . $user_id . "." . $ext;
      move_uploaded_file($tmp, "Profiles/$filename");
      $sql .= ", profile_picture = '$filename'";
    }

    $sql .= " WHERE id = $user_id";
    mysqli_query($conn, $sql);
    header("Location: profil.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Profil</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="icon" href="Media/NeoSave.ico" type="image/x-icon" />
  <style>
    button:disabled {
      opacity: 0.6;
      cursor: default;
    }
    
    .profile-image-buttons .save-btn,
    .profile-image-buttons .discard-btn {
      font-size: 18px;
      padding: 8px 16px;
    }
  </style>
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

      <?php if (isset($_GET['error'])): ?>
        <div style="background: #fdd; color: #c00; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          ⚠️ <?= htmlspecialchars($_GET['error']) ?>
        </div>
      <?php endif; ?>

      <form class="profile-editor" method="POST" action="profil.php" enctype="multipart/form-data">

        <div class="profile-top">
          
          <div class="profile-image-wrapper">
            <img id="profile-preview" src="Profiles/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" />
          </div>

          <div class="profile-image-buttons">
            
            <label class="save-btn" style="cursor: pointer;">
              <p>Ganti Foto</p>
              <input type="file" name="profile_picture" id="upload-profile" accept="image/*" hidden />
            </label>

            <button type="button" class="discard-btn" onclick="confirmDelete()">Hapus Foto</button>
          </div>

        </div>

        <div class="profile-info">
          <label for="name">Nama:</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required maxlength="50" autocomplete="off"/>

          <label for="email">Email:</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required maxlength="50" autocomplete="off"/>

          <label>Status:</label>
          <div class="status"><?= htmlspecialchars($user['status']) ?></div>

          <label for="password">Ganti Password:</label>
          <input type="password" id="password" name="password" placeholder="Kosongkan jika tidak ingin diubah" maxlength="20" />
        </div>

        <div class="profile-actions">
          <button type="submit" class="save-btn" id="saveBtn" disabled>Simpan</button>
          <button type="reset" class="discard-btn" id="cancelBtn" disabled>Batal</button>
        </div>

      </form>

    </main>

  </div>

  <script>
    const nameInput = document.getElementById("name");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const fileInput = document.getElementById("upload-profile");
    const profilePreview = document.getElementById("profile-preview");
    const saveBtn = document.getElementById("saveBtn");
    const cancelBtn = document.getElementById("cancelBtn");

    const initialName = nameInput.value;
    const initialEmail = emailInput.value;
    const initialProfileSrc = profilePreview.src;

      function confirmDelete() {
        if (confirm("Yakin ingin menghapus foto profil?")) {
          const form = document.createElement("form");
          form.method = "POST";
          form.action = "profil.php";

          const hidden = document.createElement("input");
          hidden.type = "hidden";
          hidden.name = "delete_picture";
          hidden.value = "1";

          form.appendChild(hidden);
          document.body.appendChild(form);
          form.submit();
        }
      }

    function activateButtons() {
      const nameChanged = nameInput.value !== initialName;
      const emailChanged = emailInput.value !== initialEmail;
      const passwordChanged = passwordInput.value.length > 0;
      const fileChanged = fileInput.files.length > 0;

      const enable = nameChanged || emailChanged || passwordChanged || fileChanged;
      saveBtn.disabled = !enable;
      cancelBtn.disabled = !enable;
    }

    nameInput.addEventListener("input", activateButtons);
    emailInput.addEventListener("input", activateButtons);
    passwordInput.addEventListener("input", activateButtons);

    fileInput.addEventListener("change", () => {
      if (fileInput.files[0]) {
        profilePreview.src = URL.createObjectURL(fileInput.files[0]);
      }
      activateButtons();
    });

    document.querySelector('form').addEventListener('reset', () => {
      nameInput.value = initialName;
      emailInput.value = initialEmail;
      passwordInput.value = "";
      saveBtn.disabled = true;
      cancelBtn.disabled = true;
    });

    document.querySelector("form").addEventListener("reset", () => {
      nameInput.value = initialName;
      emailInput.value = initialEmail;
      passwordInput.value = "";

      profilePreview.src = initialProfileSrc;
      fileInput.value = "";

      saveBtn.disabled = true;
      cancelBtn.disabled = true;
    });
  </script>
</body>
<footer class="site-footer">
  <div class="footer-container">
    <p>&copy; 2025 <b>NeoSave</b>. All rights reserved.</p>
  </div>
</footer>
</html>
