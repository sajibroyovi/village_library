<?php
$lines = file('dashboard.php');
$depth = 0;
foreach ($lines as $i => $line) {
    $opens = substr_count($line, '(');
    $closes = substr_count($line, ')');
    $depth += ($opens - $closes);
    if ($depth < 0) {
        echo "🚨 Negative depth at line " . ($i + 1) . ": $depth\n";
        echo "Line: " . trim($line) . "\n";
        $depth = 0; // reset to continue
    }
}
echo "Final depth: $depth\n";
?>
