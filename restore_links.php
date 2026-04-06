<?php
$conn = new PDO('mysql:host=localhost;dbname=shidhlajury_db', 'root', '');

// Find families that have a 'Self (Owner)' and a 'Parent'
$stmt = $conn->query("SELECT family_id, MAX(CASE WHEN relation_to_owner = 'Self (Owner)' THEN id END) as owner_id, MAX(CASE WHEN relation_to_owner = 'Parent' THEN id END) as parent_id FROM family_members GROUP BY family_id");

$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['owner_id'] && $row['parent_id']) {
        // Check if owner's parent_member_id is currently NULL
        $ownerStmt = $conn->prepare("SELECT parent_member_id FROM family_members WHERE id = ?");
        $ownerStmt->execute([$row['owner_id']]);
        $ownerData = $ownerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (empty($ownerData['parent_member_id'])) {
            // Restore the link! The Owner is the child of the Parent.
            $update = $conn->prepare("UPDATE family_members SET parent_member_id = ? WHERE id = ?");
            $update->execute([$row['parent_id'], $row['owner_id']]);
            $count++;
            echo "Restored link for Family " . $row['family_id'] . " (Owner " . $row['owner_id'] . " -> Parent " . $row['parent_id'] . ")\n";
        }
    }
}

echo "Done. Restored $count links.";
?>
