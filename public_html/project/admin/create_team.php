<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
?>

<?php

//TODO handle stock fetch
if (isset($_POST["action"]) && $_POST["action"] !== "fetch_all") {
    $action = $_POST["action"];
    $name =  strtoupper(se($_POST, "name", "", false));
    $team = [];
    if ($name) {
        if ($action === "fetch") {
            $result = fetch_team($name);
            
            error_log("Data from API" . var_export($result, true));
            if ($result) {
                $team = $result;
            } else {
                flash("Team not found", "warning");
            }
        } else if ($action === "create") {
            foreach ($_POST as $k => $v) {
                if (!in_array($k, ["api_id","name", "nickname", "code", "city", "conference", "division", "logo_url"])) {
                    unset($_POST[$k]);
                }
                $team = $_POST;
                error_log("Cleaned up POST: " . var_export($team, true));
            }
        }
    } else {
        flash("You must provide a team code", "warning");
    }
    //insert data
    $db = getDB();
    $query = "INSERT INTO `teams` ";
    $columns = [];
    $params = [];
    //per record
    foreach ($team as $k => $v) {
        array_push($columns, "`$k`");
        $params[":$k"] = $v;
    }
    $query .= "(" . join(",", $columns) . ")";
    $query .= "VALUES (" . join(",", array_keys($params)) . ")";
    error_log("Query: " . $query);
    error_log("Params: " . var_export($params, true));
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Inserted record " . $db->lastInsertId(), "success");
    } catch (PDOException $e) {
        error_log("Something broke with the query" . var_export($e, true));
        if ($e->errorInfo[1] === 1062) {
            flash("Record already exists", "warning");
        }
        flash("An error occurred", "danger");
    }
} else if (isset($_POST["action"]) && $_POST["action"] === "fetch_all") {
    $result = fetch_all_teams();
    $db = getDB();
    $insert_count = 0;
    foreach ($result as $team) {
        $query = "INSERT INTO `teams` ";
        $columns = [];
        $params = [];
        foreach ($team as $k => $v) {
            array_push($columns, "`$k`");
            $params[":$k"] = $v;
        }
        $query .= "(" . join(",", $columns) . ")";
        $query .= "VALUES (" . join(",", array_keys($params)) . ")";
        error_log("Query: " . $query);
        error_log("Params: " . var_export($params, true));
        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $insert_count++;
        } catch (Exception $e) {
            error_log(var_export($e, true));
        }
    }
    flash("Inserted $insert_count records", "success");
}

//TODO handle manual create stock
?>
<div class="container-fluid">
    <h3>Create or Fetch Team</h3>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create')">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch')">Create</a>
        </li>
    </ul>
    <div id="fetch" class="tab-target">
        <form method="POST">
            <?php render_input(["type" => "search", "name" => "name", "placeholder" => "Team Code", "rules" => ["required" => "required", "maxlength" => 3, "minlength" => 3]]); ?>
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "fetch"]); ?>
            <?php render_button(["text" => "Fetch", "type" => "submit",]); ?>
        </form>
        <form method="POST">
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "fetch_all"]); ?>
            <?php render_button(["text" => "Fetch All", "type" => "submit",]); ?>
        </form>
    </div>
    <div id="create" style="display: none;" class="tab-target">
        <form method="POST">

            <?php render_input(["type" => "text", "name" => "name", "placeholder" => "Name", "label" => "Name", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "nickname", "placeholder" => "Nickname", "label" => "Nickname", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "code", "placeholder" => "Code", "label" => "Code", "rules" => ["required" => "required", "maxlength" => 3]]); ?>
            <?php render_input(["type" => "text", "name" => "city", "placeholder" => "City", "label" => "City", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "conference", "placeholder" => "Conference", "label" => "Conference", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "division", "placeholder" => "Division", "label" => "Division", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "logo_url", "placeholder" => "Logo URL", "label" => "Logo URL", "rules" => ["required" => "required"]]); ?>

            <?php render_input(["type" => "hidden", "name" => "action", "value" => "create"]); ?>
            <?php render_button(["type" => "submit", "text" => "Create"]); ?>
        </form>
    </div>
</div>
<script>
    function switchTab(tab) {
        let target = document.getElementById(tab);
        if (target) {
            let eles = document.getElementsByClassName("tab-target");
            for (let ele of eles) {
                ele.style.display = (ele.id === tab) ? "none" : "block";
            }
        }
    }
</script>

<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>