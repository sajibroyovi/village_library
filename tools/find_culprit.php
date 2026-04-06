<?php
$content = file_get_contents('dashboard.php');
$lines = explode("\n", $content);
$depth = 0;
$candidates = [];
foreach ($lines as $i => $line) {
    // Basic ignore of comments
    $clean = preg_replace('/\/\/.*$/', '', $line);
    $opens = substr_count($clean, '(');
    $closes = substr_count($clean, ')');
    $depthBefore = $depth;
    $depth += ($opens - $closes);
    
    if ($depth > $depthBefore) {
        $candidates[$depth] = $i + 1;
    }
}
// The culprit is likely the last one that stayed at its depth level
echo "Final depth: $depth\n";
echo "Candidates for missing closing paren:\n";
foreach ($candidates as $d => $l) {
    echo "Depth $d started at line $l\n";
}
?>
