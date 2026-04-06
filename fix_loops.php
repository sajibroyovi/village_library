<?php
$conn = new PDO('mysql:host=localhost;dbname=shidhlajury_db', 'root', '');

// Find ALL rows where relation_to_owner is 'Parent'
$stmt = $conn->query("SELECT id, family_id, parent_member_id FROM family_members WHERE relation_to_owner = 'Parent'");

$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['parent_member_id'])) {
        // Find the "owner" or child that the parent points to
        $childId = $row['parent_member_id'];
        
        // Remove parent_member_id from the Parent (so they aren't the child of their child)
        $updateParent = $conn->prepare("UPDATE family_members SET parent_member_id = NULL WHERE id = ?");
        $updateParent->execute([$row['id']]);
        
        // Ensure the Child correctly points back to the Parent
        $updateChild = $conn->prepare("UPDATE family_members SET parent_member_id = ? WHERE id = ?");
        $updateChild->execute([$row['id'], $childId]);
        
        $count++;
        echo "Fixed loop for Family " . $row['family_id'] . " (Parent " . $row['id'] . " <-> Child " . $childId . ")\n";
    }
}

echo "Done. Fixed $count recursive parent-child loops.\n";
?>
