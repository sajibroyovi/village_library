<?php
$content = file_get_contents('dashboard.php');
// Remove comments
$content = preg_replace('/\/\/.*$/m', '', $content);
$content = preg_replace('/\/\*.*?\*\//s', '', $content);
// Remove strings (single, double, backtick)
// Use a simple state machine approach to be safer
$clean = '';
$inString = false;
$quoteChar = '';
$escaped = false;
for ($i = 0; $i < strlen($content); $i++) {
    $c = $content[$i];
    if ($escaped) {
        $escaped = false;
        continue;
    }
    if ($c === '\\') {
        $escaped = true;
        continue;
    }
    if (!$inString) {
        if ($c === "'" || $c === '"' || $c === '`') {
            $inString = true;
            $quoteChar = $c;
        } else {
            $clean .= $c;
        }
    } else {
        if ($c === $quoteChar) {
            $inString = false;
        }
    }
}

$oB = substr_count($clean, '{'); $cB = substr_count($clean, '}');
$oP = substr_count($clean, '('); $cP = substr_count($clean, ')');

echo "Balanced Braces: { $oB, } $cB\n";
echo "Balanced Parens: ( $oP, ) $cP\n";
?>
