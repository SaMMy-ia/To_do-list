<?php
require_once __DIR__ . '/../database/env.php';

function encryptData($data) {
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    return openssl_encrypt($data, "AES-256-CBC", $key, 0, $iv);
}

function decryptData($encryptedData) {
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    return openssl_decrypt($encryptedData, "AES-256-CBC", $key, 0, $iv);
}
?>
