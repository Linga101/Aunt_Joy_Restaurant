<?php
$passwords = [
    'Lingalithu Kadewele' => 'Lingalithu@2025',
    'William Soko' => 'William@2025',
    'Veronica Khoswe' => 'Veronica@2025',
    'Morren Chunga' => 'Morren@2025',
    'Deborah Banda' => 'Deborah@2025',
    'Samuel Phiri' => 'Samuel@2025',
    'Grace Mwale' => 'Grace@2025',
    'Patrick Kachali' => 'Patrick@2025'
];

echo "=== PASSWORD HASHES ===\n\n";
foreach ($passwords as $name => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    echo $name . "\n";
    echo "  Password: " . $password . "\n";
    echo "  Hash: " . $hash . "\n";
    echo "\n";
}
?>
