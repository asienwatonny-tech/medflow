<?php
$host = "localhost";
$dbname = "medflow";
$username = "root";
$password = ""; // Default XAMPP setup usually has an empty password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit("Database connection failed. Check your database settings.");
}
$db = $pdo; // lets the rest of the MedFlow code use either name
?>
