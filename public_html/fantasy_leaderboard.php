<?php
require(__DIR__ . "/../partials/nav.php");
if (!is_logged_in()) {
    flash("You are not logged in", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$query = "SELECT ft.name AS fantasy_team_name,
CONCAT(g1.first_name, ' ', g1.last_name) AS guard_1_name,
CONCAT(g2.first_name, ' ', g2.last_name) AS guard_2_name,
CONCAT(f1.first_name, ' ', f1.last_name) AS forward_1_name,
CONCAT(f2.first_name, ' ', f2.last_name) AS forward_2_name,
CONCAT(c.first_name, ' ', c.last_name) AS center_name
FROM fantasy_teams ft LEFT JOIN players g1 ON ft.guard_1_id = g1.id
LEFT JOIN players g2 ON ft.guard_2_id = g2.id
LEFT JOIN players f1 ON ft.forward_1_id = f1.id
LEFT JOIN players f2 ON ft.forward_2_id = f2.id
LEFT JOIN players c ON ft.center_id = c.id;";

try {
    $db = getDB();
    $stmt = $db->prepare($query);
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $players = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching players: " . var_export($e, true));
    flash("Error fetching players", "danger");
}
?>

<div class="container-fluid">
    <h1>Fantasy Leaderboard</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Fantasy Team Name</th>
                <th>Guard 1 Name</th>
                <th>Guard 2 Name</th>
                <th>Forward 1 Name</th>
                <th>Forward 2 Name</th>
                <th>Center Name</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $player) : ?>
                <tr>
                    <td>
                        <?php se($player, "fantasy_team_name"); ?>
                    </td>
                    <td>
                        <?php se($player, "guard_1_name"); ?>
                    </td>
                    <td>
                        <?php se($player, "guard_2_name"); ?>
                    </td>
                    <td>
                        <?php se($player, "forward_1_name"); ?>
                    </td>
                    <td>
                        <?php se($player, "forward_2_name"); ?>
                    </td>
                    <td>
                        <?php se($player, "center_name"); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>