<?php
$content = file_get_contents('dashboard.php');
$len = strlen($content);
$depth = 0;
$lastDepth = 0;
for ($i = 0; $i < $len; $i++) {
    $char = $content[$i];
    if ($char === '(') $depth++;
    if ($char === ')') $depth--;
    
    if ($depth < 0) {
        $lineNum = substr_count(substr($content, 0, $i), "\n") + 1;
        echo "🚨 ERROR: Negative depth ($depth) at line $lineNum char $i\n";
        $depth = 0;
    }
}
echo "Final Depth: $depth\n";
?>
