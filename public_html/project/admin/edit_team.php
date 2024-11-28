<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
?>

<?php
$id = se($_GET, "id", -1, false);
//TODO handle stock fetch
if (isset($_POST["name"])) {
    foreach ($_POST as $k => $v) {
        if (!in_array($k, ["name", "nickname", "code", "city", "conference", "division", "logo_url"])) {
            unset($_POST[$k]);
        }
        $entry = $_POST;
        error_log("Cleaned up POST: " . var_export($entry, true));
    }
    //insert data
    $db = getDB();
    $query = "UPDATE `teams` SET ";

    $params = [];
    //per record
    foreach ($entry as $k => $v) {

        if ($params) {
            $query .= ",";
        }
        //be sure $k is trusted as this is a source of sql injection
        $query .= "$k=:$k";
        $params[":$k"] = $v;
    }

    $query .= " WHERE id = :id";
    $params[":id"] = $id;
    error_log("Query: " . $query);
    error_log("Params: " . var_export($params, true));
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Updated record ", "success");
    } catch (PDOException $e) {
        error_log("Something broke with the query" . var_export($e, true));
        flash("An error occurred", "danger");
    }
}

$team = [];
if ($id > -1) {
    //fetch
    $db = getDB();
    $query = "SELECT name, nickname, code, city, conference, division, logo_url FROM `teams` WHERE id = :id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
        if ($r) {
            $team = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching record: " . var_export($e, true));
        flash("Error fetching record", "danger");
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location:" . get_url("admin/list_teams.php")));
}
if ($team) {
    $form = [
        ["type" => "text", "name" => "name", "placeholder" => "Name", "label" => "Name", "rules" => ["required" => "required"]],
        ["type" => "text", "name" => "nickname", "placeholder" => "Nickname", "label" => "Nickname", "rules" => ["required" => "required"]],
        ["type" => "text", "name" => "code", "placeholder" => "Code", "label" => "Code", "rules" => ["required" => "required", "maxlength" => 3]],
        ["type" => "text", "name" => "city", "placeholder" => "City", "label" => "City", "rules" => ["required" => "required"]],
        ["type" => "text", "name" => "conference", "placeholder" => "Conference", "label" => "Conference", "rules" => ["required" => "required"]],
        ["type" => "text", "name" => "division", "placeholder" => "Division", "label" => "Division", "rules" => ["required" => "required"]],
        ["type" => "text", "name" => "logo_url", "placeholder" => "Logo URL", "label" => "Logo URL", "rules" => ["required" => "required"]],
    ];
    $keys = array_keys($team);

    foreach ($form as $k => $v) {
        if (in_array($v["name"], $keys)) {
            $form[$k]["value"] = $team[$v["name"]];
        }
    }
}

?>
<div class="container-fluid">
    <h3>Edit Team</h3>
    <form method="POST">
        <?php foreach ($form as $k => $v) {

            render_input($v);
        } ?>
        <?php render_button(["text" => "Search", "type" => "submit", "text" => "Update"]); ?>
    </form>

</div>

<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>