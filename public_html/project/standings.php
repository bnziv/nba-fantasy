<?php
require(__DIR__ . "/../../partials/nav.php");

$conference = se($_GET, "conference", "", false);
$division = se($_GET, "division", "", false);
$order = se($_GET, "order", "desc", false);
$type = se($_GET, "type", "wins", false);

$query = "SELECT t.id, t.name as Name, CONCAT(wins, \"-\", losses) AS Record, CONCAT(t.conference, \" - Rank: \", conference_rank) AS Conference,
CONCAT(t.division, \" - Rank: \", division_rank) AS Division, streak AS Streak, last_10 AS \"Last 10\" FROM standings
JOIN teams t ON team_api_id = t.api_id WHERE 1=1";
$params = [];
if (!empty($conference)) {
    $query .= " AND conference LIKE :conference";
    $params[":conference"] = "%$conference%";
}
if (!empty($division)) {
    $query .= " AND division LIKE :division";
    $params[":division"] = "%$division%";
}
switch ($type) {
    case "wins":
        $query .= " ORDER BY wins $order";
        break;
    case "crank":
        $query .= " ORDER BY conference_rank $order";
        break;
    case "drank":
        $query .= " ORDER BY division_rank $order";
        break;
}
$db = getDB();
$stmt = $db->prepare($query);
$results = [];
try {
    $stmt->execute($params);
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching teams " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
$table = ["data" => $results, "title" => "Standings", "ignored_columns" => ["id"], "view_url" => get_url("team_details.php"), "view_label" => "Details",
"empty_message" => "No teams to show", "extra_classes" => "table-hover"];

$conferences = get_conferences();
$conferences = array_map(function ($v) {
    return [$v["conference"] => $v["conference"]];
}, $conferences);
$divisions = get_divisions();
$divisions = array_map(function ($v) {
    return [$v["division"] => $v["division"]];
}, $divisions);
array_push($conferences, ["" => "None"]);
array_push($divisions, ["" => "None"]);

$types = [
    ["wins" => "Wins"],
    ["crank" => "Conference Rank"],
    ["drank" => "Division Rank"]
]

?>
<div class="container-fluid">
    <div>
        <form>
            <div class="row">
                <div class="col">
                    <?php render_input(["name" => "conference", "type" => "select", "label" => "Conference", "value" => $conference, "options" => $conferences]); ?>
                </div>
                <div class="col">
                    <?php render_input(["name" => "division", "type" => "select", "label" => "Division", "value" => $division, "options" => $divisions]); ?>
                </div>
                <div class="col">
                    <?php render_input(["name" => "type", "type" => "select", "label" => "Order by", "value" => $type, "options" => $types]); ?>
                </div>
                <div class="col">
                    <?php render_input(["name" => "order", "type" => "select", "label" => "Order", "value" => $order, "options" => [["asc" => "Ascending"], ["desc" => "Descending"]]]); ?>
                </div>
            <div class="row">
                <div class="col">
                    <?php render_button(["type" => "submit", "text" => "Search"]); ?>
                </div>
            </div>
        </form>
    </div>
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <?php render_table($table); ?>
        </div>
    </div>
</div>

<?php
require(__DIR__. "/../../partials/flash.php");
?>