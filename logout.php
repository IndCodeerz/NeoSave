<?php
session_start();

// Hapus data session
session_unset();
session_destroy();

// Redirect ke landing page
header("Location: landing.html");
exit;
?>