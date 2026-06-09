<?php
session_start();
session_destroy();
setcookie('user_email', '', time() - 3600, '/');
header('Location: signin.php');
exit;
?>