<?php
session_start();

// remove all session variables
session_unset();

// destroy the session
session_destroy();

// redirect to login page after logout
header("Location: login.php");
exit();
