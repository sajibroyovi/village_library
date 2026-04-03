<?php
$lines = file('dashboard.php');
$depth = 0;
foreach ($lines as $i => $line) {
    if (strpos($line, '//') !== false && strpos($line, '(') > strpos($line, '//')) continue; // Skip simple comments with parens
    $opens = substr_count($line, '(');
    $closes = substr_count($line, ')');
    $oldDepth = $depth;
    $depth += ($opens - $closes);
    if ($depth > 50) { // arbitrary threshold for sanity
         echo "🚨 Abnormal depth at line " . ($i + 1) . ": $depth\n";
         break;
    }
    // Track where it goes up and doesn't come down
    if ($depth > $oldDepth) {
        $lastOpenLine = $i + 1;
    }
}
echo "Final depth: $depth\n";
echo "Suspect last open line around: $lastOpenLine\n";
?>
