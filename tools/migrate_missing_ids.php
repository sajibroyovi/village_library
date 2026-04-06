<?php
require_once 'db_connect.php';

$approvedWithoutTarget = $conn->query("SELECT id, action_type, payload FROM pending_actions WHERE status='approved' AND (target_id IS NULL OR target_id = 0) AND action_type IN ('add_family', 'add_member')")->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
foreach($approvedWithoutTarget as $row) {
    $payload = json_decode($row['payload'], true);
    if (!$payload) continue;

    if ($row['action_type'] === 'add_family' && !empty($payload['owner_name'])) {
        $stmt = $conn->prepare("SELECT id FROM families WHERE house_owner_name=? AND area=? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$payload['owner_name'], $payload['area']]);
        $realId = $stmt->fetchColumn();
        
        if ($realId) {
            $update = $conn->prepare("UPDATE pending_actions SET target_id=? WHERE id=?");
            $update->execute([$realId, $row['id']]);
            $updated++;
            echo "Linked abstract family pending_{$row['id']} to real family ID: $realId\n";
        }
    }
    
    if ($row['action_type'] === 'add_member' && !empty($payload['name'])) {
        $stmt = $conn->prepare("SELECT id FROM family_members WHERE name=? AND relation_to_owner=? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$payload['name'], $payload['relation_to_owner']]);
        $realId = $stmt->fetchColumn();
        
        if ($realId) {
            $update = $conn->prepare("UPDATE pending_actions SET target_id=? WHERE id=?");
            $update->execute([$realId, $row['id']]);
            $updated++;
            echo "Linked abstract member pending_{$row['id']} to real member ID: $realId\n";
        }
    }
}
echo "Total missing target_ids linked: $updated / " . count($approvedWithoutTarget) . "\n";
?>
