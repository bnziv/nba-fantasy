<?php
require(__DIR__ . "/../partials/nav.php");
if (!is_logged_in()) {
    flash("You are not logged in", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$query = "SELECT DISTINCT ft.name AS fantasy_team_name,
CONCAT(g1.first_name, ' ', g1.last_name) AS guard_1_name,
CONCAT(g2.first_name, ' ', g2.last_name) AS guard_2_name,
CONCAT(f1.first_name, ' ', f1.last_name) AS forward_1_name,
CONCAT(f2.first_name, ' ', f2.last_name) AS forward_2_name,
CONCAT(c.first_name, ' ', c.last_name) AS center_name,
u.username AS user_name,
ft.created_by AS user_id,
g1p.points AS guard_1_points,
g2p.points AS guard_2_points,
f1p.points AS forward_1_points,
f2p.points AS forward_2_points,
cp.points AS center_points
FROM fantasy_teams ft
LEFT JOIN players g1 ON ft.guard_1_id = g1.id
LEFT JOIN players g2 ON ft.guard_2_id = g2.id
LEFT JOIN players f1 ON ft.forward_1_id = f1.id
LEFT JOIN players f2 ON ft.forward_2_id = f2.id
LEFT JOIN players c ON ft.center_id = c.id
LEFT JOIN player_latest_points g1p ON g1.id = g1p.player_id
LEFT JOIN player_latest_points g2p ON g2.id = g2p.player_id
LEFT JOIN player_latest_points f1p ON f1.id = f1p.player_id
LEFT JOIN player_latest_points f2p ON f2.id = f2p.player_id
LEFT JOIN player_latest_points cp ON c.id = cp.player_id
LEFT JOIN Users u ON ft.created_by = u.id LIMIT 50;";

try {
    $db = getDB();
    $stmt = $db->prepare($query);
    $stmt->execute();
    $players = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching players: " . var_export($e, true));
    flash("Error fetching players", "danger");
}

foreach ($players as $key => $player) {
    $total_points = ($player['guard_1_points'] ?? 0) + ($player['guard_2_points'] ?? 0) + ($player['forward_1_points'] ?? 0) + ($player['forward_2_points'] ?? 0) + ($player['center_points'] ?? 0);
    $players[$key]['total_points'] = $total_points;
}

usort($players, function($a, $b) {
    return $b['total_points'] - $a['total_points'];
});
?>

<div class="container-fluid">
    <h1>Fantasy Leaderboard</h1>
    <div class="row">
        <?php foreach ($players as $index => $player) : ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">#<?php echo $index + 1; ?> <?php se($player, "fantasy_team_name"); ?></h3>
                        <p class="card-text">Created by: <a href="profile.php?id=<?php se($player, "user_id"); ?>"><?php se($player, "user_name"); ?></a></p>
                        <ul class="list-unstyled">
                            <li><strong>Guard 1:</strong> <?php se($player, "guard_1_name"); ?> (<?php se($player, "guard_1_points", "0"); ?> points)</li>
                            <li><strong>Guard 2:</strong> <?php se($player, "guard_2_name"); ?> (<?php se($player, "guard_2_points", "0"); ?> points)</li>
                            <li><strong>Forward 1:</strong> <?php se($player, "forward_1_name"); ?> (<?php se($player, "forward_1_points", "0"); ?> points)</li>
                            <li><strong>Forward 2:</strong> <?php se($player, "forward_2_name"); ?> (<?php se($player, "forward_2_points", "0"); ?> points)</li>
                            <li><strong>Center:</strong> <?php se($player, "center_name"); ?> (<?php se($player, "center_points", "0"); ?> points)</li>
                        </ul>
                        <h5>Total: <?php se($player, "total_points"); ?></h5>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
    function triggerUpdate() {
        fetch('/update_data.php')
        .then(response => response.json())
        .then(data => console.log(data));
    }
    window.onload = function() {
        triggerUpdate();
    }
</script>
<?php require(__DIR__ . "/../partials/flash.php"); ?>