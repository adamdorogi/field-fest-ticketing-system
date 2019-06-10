<?php
// Include QR code generator library.
include('phpqrcode/phpqrcode.php');

// Get ID from parameter.
$id = preg_replace('/[^-0-9]/', '', $_GET['id']);

// Generate QR code from ID.
QRcode::png($id, null, QR_ECLEVEL_L, 8, 1);  
?>