<?php
// Session
session_start();

// Logout
session_destroy();
header('Location: ../auth/login.php');
exit;