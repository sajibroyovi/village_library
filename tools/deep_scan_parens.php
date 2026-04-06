<?php
$lines = file('dashboard.php');
$depth = 0;
foreach ($lines as $i => $line) {
    // Remove comments to avoid false positives
    $cleanLine = preg_replace('/\/\/.*$/', '', $line);
    $opens = substr_count($cleanLine, '(');
    $closes = substr_count($cleanLine, ')');
    $depth += ($opens - $closes);
    echo "Line " . ($i + 1) . " (Depth $depth): " . trim($line) . "\n";
    if ($depth < 0) {
        echo "🚨 ERROR: Negative depth at " . ($i + 1) . "\n";
        exit;
    }
}
echo "Final Depth: $depth\n";
?>
