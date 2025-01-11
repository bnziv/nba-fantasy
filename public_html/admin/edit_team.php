<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../partials/nav.php");
    //bv249 12/6
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
?>

<?php
$id = se($_GET, "id", -1, false);
if (isset($_POST["name"])) {
    foreach ($_POST as $k => $v) {
        if (!in_array($k, ["name", "nickname", "code", "city", "conference", "division", "logo_url"])) {
            unset($_POST[$k]);
        }
        $entry = $_POST;
        error_log("Cleaned up POST: " . var_export($entry, true));
    }
    $db = getDB();
    $query = "UPDATE `teams` SET ";

    $params = [];
    foreach ($entry as $k => $v) {
        if ($params) {
            $query .= ",";
        }
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
    $db = getDB();
    $query = "SELECT name, nickname, code, city, conference, division, logo_url FROM `teams` WHERE id = :id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
        if ($r) {
            $team = $r;
        } else {
            flash("Invalid team", "danger");
            die(header("Location:" . get_url("teams.php")));
        }
    } catch (PDOException $e) {
        error_log("Error fetching record: " . var_export($e, true));
        flash("Error fetching record", "danger");
    }
} else {
    flash("Invalid team", "danger");
    die(header("Location:" . get_url("teams.php")));
}

$conferences = get_conferences();
$conferences = array_map(function ($v) {
    return [$v["conference"] => $v["conference"]];
}, $conferences);
$divisions = get_divisions();
$divisions = array_map(function ($v) {
    return [$v["division"] => $v["division"]];
}, $divisions);
if ($team) {
    $form = [
        ["type" => "text", "name" => "name", "placeholder" => "Name", "label" => "Name", "rules" => ["required" => "required"]],
        ["type" => "text", "name" => "nickname", "placeholder" => "Nickname", "label" => "Nickname", "rules" => ["required" => "required"]],
        ["type" => "text", "name" => "code", "placeholder" => "Code", "label" => "Code", "rules" => ["required" => "required", "maxlength" => 3]],
        ["type" => "text", "name" => "city", "placeholder" => "City", "label" => "City", "rules" => ["required" => "required"]],
        ["type" => "select", "name" => "conference", "label" => "Conference", "options" => $conferences, "rules" => ["required" => "required"]],
        ["type" => "select", "name" => "division", "label" => "Division", "options" => $divisions, "rules" => ["required" => "required"]],
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
    <form method="POST" onsubmit="return validate(this)">
        <?php foreach ($form as $k => $v) {

            render_input($v);
        } ?>
        <?php render_button(["text" => "Search", "type" => "submit", "text" => "Update"]); ?>
    </form>
</div>
<script>
    function validate(form) {
        document.getElementById("flash").innerHTML = "";
        let isValid = true;
        let namePattern = /^[a-zA-Z ]{3,30}$/;
        let eastDivisions = ["Central", "Atlantic", "Southeast"];
        let westDivisions = ["Southwest", "Pacific", "Northwest"];
        if (!namePattern.test(form.name.value.trim())) {
            flash("Name must be between 3 and 30 alphabetic characters", "danger");
            isValid = false;
        }
        if (!namePattern.test(form.nickname.value.trim())) {
            flash("Nickname must be between 3 and 30 alphabetic characters", "danger");
            isValid = false;
        }
        if (form.code.value.trim().length != 3) {
            flash("Code must be 3 characters", "danger");
            isValid = false;
        }
        if (form.conference.value.trim() == "East" && westDivisions.includes(form.division.value.trim())) {
            flash("East conference teams cannot be in western divisions", "danger");
            isValid = false;
        }
        if (form.conference.value.trim() == "West" && eastDivisions.includes(form.division.value.trim())) {
            flash("West conference teams cannot be in eastern divisions", "danger");
            isValid = false;
        }
        return isValid;
    }
</script>

<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../partials/flash.php");
?>