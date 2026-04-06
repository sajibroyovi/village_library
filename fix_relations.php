<?php
$conn = new PDO('mysql:host=localhost;dbname=shidhlajury_db', 'root', '');
// Fix any parents that were mistakenly set as children of their own children
$conn->exec("UPDATE family_members SET parent_member_id = NULL WHERE relation_to_owner = 'Parent' AND parent_member_id IS NOT NULL");
echo 'Cleaned up parents. ';
// Fix Sibling mappings if needed
echo 'Done.';
?>
