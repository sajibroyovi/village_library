<?php
include 'includes/config.php';
$family_id = $_GET['fid'] ?? 1;
$stmt = $conn->prepare("SELECT id, name, relation_to_owner, parent_member_id FROM family_members WHERE family_id = ? AND deleted_at IS NULL");
$stmt->execute([$family_id]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
?>
