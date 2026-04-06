<?php
/**
 * undo_branching.php
 * This script removes all cross-household links to restore data isolation.
 */

$host = 'localhost';
$db   = 'shidhlajury_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

echo "--- Household De-Branching Migration ---\n";

// Clear the parent family links
$stmt = $pdo->prepare("UPDATE families SET parent_family_id = NULL, origin_member_id = NULL");
$stmt->execute();

echo "🔗 Removed all cross-household links. All families are now isolated.\n";
?>
