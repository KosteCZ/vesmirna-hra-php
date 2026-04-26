<?php

function handleStatsAction(string $action, int $userId, PDO $db): bool
{
    if ($action === 'leaderboard') {
        $stmt = $db->query("SELECT p.mine_level, p.iron_amount, p.researched_colors, u.player_name, u.last_login FROM planets p JOIN users u ON p.user_id = u.id ORDER BY p.mine_level DESC LIMIT 10");
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($leaderboard);
        return true;
    }

    if ($action === 'global_stats') {
        $sql = "SELECT
            SUM(res_yellow) as yellow, SUM(res_red) as red, SUM(res_blue) as blue,
            SUM(res_green) as green, SUM(res_orange) as orange, SUM(res_purple) as purple
            FROM planets";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        return true;
    }

    return false;
}
