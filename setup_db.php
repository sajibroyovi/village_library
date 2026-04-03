<?php
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password

try {
    // Connect without database to create it first
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS shidhlajury_db";
    $conn->exec($sql);
    echo "Database created successfully\n";
    
    // Connect to the specific database
    $conn->exec("USE shidhlajury_db");

    // Create users table
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sqlUsers);
    echo "Users table created successfully\n";

    // Create families table (house owner)
    $sqlFamilies = "CREATE TABLE IF NOT EXISTS families (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        house_owner_name VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        user_id INT(11) DEFAULT NULL, /* For standard users to manage their own family */
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $conn->exec($sqlFamilies);
    echo "Families table created successfully\n";

    // Create family_members table (hierarchy)
    $sqlMembers = "CREATE TABLE IF NOT EXISTS family_members (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        family_id INT(11) NOT NULL,
        name VARCHAR(100) NOT NULL,
        relation_to_owner VARCHAR(50) NOT NULL,
        mobile_number VARCHAR(20) DEFAULT NULL,
        job_status VARCHAR(100) DEFAULT NULL,
        marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed') DEFAULT 'Single',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
    )";
    $conn->exec($sqlMembers);
    echo "Family members table created successfully\n";

    // Insert default super admin account (password: admin123)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $insertStmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES ('admin', :hash, 'super_admin')");
        $insertStmt->execute(['hash' => $hash]);
        echo "Default Super Admin created. (Username: admin, Password: admin123)\n";
    } else {
        echo "Super admin user already exists.\n";
    }

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
