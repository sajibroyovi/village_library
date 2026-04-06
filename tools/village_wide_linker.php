<?php
/**
 * village_wide_linker.php
 * This script identifies parent-child links BETWEEN separate households by matching
 * family's "owner_father_name" with the "house_owner_name" of other households.
 */

$host = 'localhost';
$db   = 'shidhlajury_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

echo "--- Ultimate Village-Wide Hierarchy Linker ---\n";

// 1. Fetch all families
$families = $pdo->query("SELECT id, house_owner_name, owner_father_name, owner_mother_name, parent_family_id FROM families")->fetchAll();

$linkCount = 0;

foreach ($families as $family) {
    // Only target families that don't have a parent_family_id yet
    if (empty($family['parent_family_id']) && !empty($family['owner_father_name'])) {
        $fatherName = trim($family['owner_father_name']);
        
        // Search for ANY member in the whole village where this father exists
        $stmt = $pdo->prepare("SELECT family_id, id FROM family_members WHERE name = ? AND family_id != ? LIMIT 1");
        $stmt->execute([$fatherName, $family['id']]);
        $parentMember = $stmt->fetch();
        
        if ($parentMember) {
            // Perform the link
            $update = $pdo->prepare("UPDATE families SET parent_family_id = ?, origin_member_id = ? WHERE id = ?");
            if ($update->execute([$parentMember['family_id'], $parentMember['id'], $family['id']])) {
                echo "🔗 Linked House '{$family['house_owner_name']}' to parent '{$fatherName}' in Household ID: {$parentMember['family_id']}\n";
                $linkCount++;
            }
        }
    }
}

echo "\n--- Village Linker Complete. Total houses joined: $linkCount ---\n";
?>
