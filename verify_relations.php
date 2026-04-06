<?php
include 'includes/config.php';
$stmt = $conn->query("SELECT id, name, relation_to_owner, spouse_member_id FROM family_members WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
?>
