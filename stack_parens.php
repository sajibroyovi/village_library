<?php
$content = file_get_contents('dashboard.php');
$lines = explode("\n", $content);
$depth = 0;
$stack = [];

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $clean = preg_replace('/\/\/.*$/', '', $line);
    $clean = preg_replace('/\/\*.*?\*\//s', '', $clean);
    
    // Remove strings manually to be safer
    $s = ''; $inS = false; $q = ''; $esc = false;
    for ($j = 0; $j < strlen($clean); $j++) {
        $c = $clean[$j];
        if ($esc) { $esc = false; continue; }
        if ($c === '\\') { $esc = true; continue; }
        if (!$inS) {
            if ($c === "'" || $c === '"' || $c === '`') { $inS = true; $q = $c; }
            else { $s .= $c; }
        } else if ($c === $q) { $inS = false; }
    }
    
    $opens = substr_count($s, '(');
    $closes = substr_count($s, ')');
    
    for ($k = 0; $k < $opens; $k++) $stack[] = $i + 1;
    for ($k = 0; $k < $closes; $k++) array_pop($stack);
}

echo "Remaining open parens stack (Line numbers):\n";
print_r($stack);
?>
