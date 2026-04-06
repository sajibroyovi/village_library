<?php
$conn = new PDO('mysql:host=localhost;dbname=shidhlajury_db', 'root', '');
$stmt = $conn->query("SELECT id, name, family_id, relation_to_owner, parent_member_id FROM family_members WHERE name LIKE '%Khogen%' OR name LIKE '%Apurbo%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
