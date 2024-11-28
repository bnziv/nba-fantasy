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
    try {
        $result = insert("teams", $team);
        if (!$result) {
            flash("Unhandled error", "warning");
        } else {
            flash("Created record with id " . var_export($result, true), "success");
        }
    } catch (InvalidArgumentException $e1) {
        error_log("Invalid arg" . var_export($e1, true));
        flash("Invalid data passed", "danger");
    } catch (PDOException $e2) {
        if ($e2->errorInfo[1] == 1062) {
            flash("Team exists or name/nickname/code in use", "warning");
        } else {
            error_log("Database error" . var_export($e2, true));
            flash("Database error", "danger");
        }
    } catch (Exception $e3) {
        error_log("Invalid data records" . var_export($e3, true));
        flash("Invalid data records", "danger");
    }
} else if (isset($_POST["action"]) && $_POST["action"] === "fetch_all") {
    $teams = fetch_all_teams();
    try {
        $opts = ["update_duplicate" => true];
        $result = insert("teams", $teams, $opts);
        if (!$result) {
            flash("Unhandled error", "warning");
        } else {
            flash("Inserted all teams", "success");
        }
    } catch (InvalidArgumentException $e1) {
        error_log("Invalid arg" . var_export($e1, true));
        flash("Invalid data passed", "danger");
    } catch (PDOException $e2) {
        error_log("Database error" . var_export($e2, true));
        flash("Database error", "danger");
    } catch (Exception $e3) {
        error_log("Invalid data records" . var_export($e3, true));
        flash("Invalid data records", "danger");
    }
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