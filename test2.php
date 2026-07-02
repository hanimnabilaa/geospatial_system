<?php
require 'db.php';

$stmt = $pdo->query("SHOW TABLES FROM maintenance_system");
print_r($stmt->fetchAll());
?>