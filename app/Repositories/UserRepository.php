<?php

function createUser(PDO $db, string $email, string $hashedPassword, string $playerName): int {
    $stmt = $db->prepare("INSERT INTO users (email, password, player_name) VALUES (?, ?, ?)");
    $stmt->execute([$email, $hashedPassword, $playerName]);

    return (int) $db->lastInsertId();
}

function findUserByEmail(PDO $db, string $email) {
    $stmt = $db->prepare("SELECT id, password, player_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function updateUserLastLogin(PDO $db, int $userId, string $lastLoginAt): void {
    $stmt = $db->prepare("UPDATE users SET last_login = ? WHERE id = ?");
    $stmt->execute([$lastLoginAt, $userId]);
}
