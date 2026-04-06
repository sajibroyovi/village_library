<?php
$pdo = new PDO('mysql:host=localhost;dbname=shidhlajury_db', 'root', '');
$res = $pdo->query('SELECT house_owner_name, owner_father_name FROM families');
foreach($res as $r) {
    echo $r['house_owner_name'] . ' (Son of ' . ($r['owner_father_name'] ?? 'NULL') . ")\n";
}
?>
