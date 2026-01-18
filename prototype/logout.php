<?php
require __DIR__ . '/app/includes/header.php';

Auth::logout();
header('Location: index.php');
exit;
