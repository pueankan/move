<?php
// เปิด Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Test</title></head><body>";
echo "<h1>PHP Working!</h1>";
echo "<p>Current Path: " . __DIR__ . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "</body></html>";