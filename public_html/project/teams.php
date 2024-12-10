<?php
require(__DIR__ . "/../../partials/nav.php");

$name = se($_GET, "name", "", false);
$code = se($_GET, "code", "", false);
$conference = se($_GET, "conference", "", false);
$division = se($_GET, "division", "", false);

$query = "SELECT id, name as Name, code as Code, conference as Conference, division as Division 
FROM `teams` WHERE 1=1";
$params = [];
if (!empty($name)) {
    $query .= " AND name LIKE :name";
    $params[":name"] = "%$name%";
}
if (!empty($code)) {
    $query .= " AND code LIKE :code";
    $params[":code"] = "%$code%";
}
if (!empty($conference)) {
    $query .= " AND conference LIKE :conference";
    $params[":conference"] = "%$conference%";
}
if (!empty($division)) {
    $query .= " AND division LIKE :division";
    $params[":division"] = "%$division%";
}
$query .= " ORDER BY name";

$limit = 10;
if (isset($_GET["limit"]) && !is_nan($_GET["limit"])) {
    $limit = $_GET["limit"];
    if ($limit < 0 || $limit > 100) {
        $limit = 10;
    }
}
$query .= " LIMIT $limit";
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

$favorite_teams = get_favorites("team", get_user_id());

$table = ["data" => $results, "title" => "Teams", "ignored_columns" => ["id"], "view_url" => get_url("team_details.php"), "view_label" => "Details",
"empty_message" => "No teams to show", "extra_classes" => "table-hover", 
"favorites" => $favorite_teams, "favorite_url" => get_url("update_favorite.php"), "favorite_type" => "team"];
if (has_role("Admin")) {
    $table["edit_url"] = get_url("admin/edit_team.php"); 
    $table["delete_url"] = get_url("admin/delete_team.php");
}
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

?>
<div class="container-fluid">
    <div>
        <form>
            <div class="row">
                <div class="col">
                    <?php render_input(["name" => "name", "type" => "text", "label" => "Name", "value" => $name]); ?>
                </div>
                <div class="col">
                    <?php render_input(["name" => "code", "type" => "text", "label" => "Code", "value" => $code, "rules" => ["maxlength" => 3]]); ?>
                </div>
                <div class="col">
                    <?php render_input(["name" => "conference", "type" => "select", "label" => "Conference", "value" => $conference, "options" => $conferences]); ?>
                </div>
                <div class="col">
                    <?php render_input(["name" => "division", "type" => "select", "label" => "Division", "value" => $division, "options" => $divisions]); ?>
                </div>
                <div class="col">
                    <?php render_input(["name" => "limit", "type" => "number", "label" => "Limit", "value" => $limit]); ?>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <?php render_button(["type" => "submit", "text" => "Search"]); ?>
                </div>
            </div>
        </form>
    </div>
    <div class="row">
        <div style="width:75%; margin: auto;">
            <?php render_table($table); ?>
        </div>
    </div>
</div>

<?php
require(__DIR__. "/../../partials/flash.php");
?>