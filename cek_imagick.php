<?php

echo '<h2>Test Imagick</h2>';

if (!extension_loaded('imagick')) {
    echo '<p style="color:red">❌ Imagick TIDAK AKTIF</p>';
    exit;
}

echo '<p style="color:green">✅ Imagick AKTIF</p>';

$imagick = new Imagick();

echo '<pre>';
print_r($imagick->getVersion());
echo '</pre>';
