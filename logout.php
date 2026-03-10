<?php
session_start();

// Destroy session
session_destroy();

// Redirect to login
header('Location: login%20(1).php');
exit();
?>
