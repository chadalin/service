<?php
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "GD loaded: " . (extension_loaded('gd') ? 'YES' : 'NO') . "<br>";

if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    echo "<pre>";
    print_r($gdInfo);
    echo "</pre>";
} else {
    echo "GD not loaded. Check php.ini";
}