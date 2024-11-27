<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$query = "SELECT id, name, nickname, code, city, conference, division, api_id FROM `teams` ORDER BY created DESC LIMIT 25";
$db = getDB();
$stmt = $db->prepare($query);
$results = [];
try {
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching stocks " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

$table = ["data" => $results, "title" => "Teams", "ignored_columns" => ["id"], "edit_url" => get_url("admin/edit_team.php"),
    "header_override" => ["Name", "Nickname", "Code", "City", "Conference", "Division", "API ID"]];
?>
<div class="container-fluid">
    <h3>List Teams</h3>
    <?php render_table($table); ?>
</div>