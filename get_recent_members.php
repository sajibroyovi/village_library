<?php
$conn = new PDO('mysql:host=localhost;dbname=shidhlajury_db', 'root', '');
$stmt = $conn->query("SELECT id, family_id, name, relation_to_owner, parent_member_id FROM family_members WHERE deleted_at IS NULL ORDER BY family_id DESC LIMIT 15");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
