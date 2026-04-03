<?php
$content = file_get_contents('dashboard.php');
$len = strlen($content);
$depth = 0;
$stack = [];
$inS = false; $q = ''; $esc = false;
$line = 1;

for ($i = 0; $i < $len; $i++) {
    $c = $content[$i];
    if ($c === "\n") $line++;
    
    if ($esc) { $esc = false; continue; }
    if ($c === '\\') { $esc = true; continue; }
    
    if (!$inS) {
        if ($c === "'" || $c === '"' || $c === '`') {
            $inS = true; $q = $c;
        } else if ($c === '/') {
            // Check for comments
            if ($i + 1 < $len && $content[$i+1] === '/') {
                // Line comment
                while ($i < $len && $content[$i] !== "\n") $i++;
                $line++;
            } else if ($i + 1 < $len && $content[$i+1] === '*') {
                // Block comment
                $i += 2;
                while ($i + 1 < $len && !($content[$i] === '*' && $content[$i+1] === '/')) {
                    if ($content[$i] === "\n") $line++;
                    $i++;
                }
                $i++;
            } else {
                // Not a comment
            }
        } else if ($c === '(') {
            $depth++;
            $stack[] = $line;
        } else if ($c === ')') {
            $depth--;
            array_pop($stack);
        }
    } else if ($c === $q) {
        $inS = false;
    }
}

echo "Remaining open parens stack (Line numbers):\n";
print_r($stack);
echo "Final depth: $depth\n";
?>
