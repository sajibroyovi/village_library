<?php
require 'db_connect.php';

$res = $conn->query("SELECT relation_to_owner, COUNT(*) as c FROM family_members GROUP BY relation_to_owner ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach($res as $r) {
    echo $r['relation_to_owner'] . " : " . $r['c'] . "\n";
}
?>
