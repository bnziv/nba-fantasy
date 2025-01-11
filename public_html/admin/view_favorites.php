<?php
require(__DIR__ . "/../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$team_name = se($_GET, "teamname", "", false);
$user_name = se($_GET, "username", "", false);
$limit = se($_GET, "limit", 10, false);
$type = se($_GET, "type", "favorites", false);

$query = "SELECT t.name AS 'Team Name', u.username AS 'Favorited By',
(SELECT COUNT(*) from favorite_teams WHERE team_id = t.id) AS 'Number of Favorites',
u.id as 'userid', t.id as 'teamid', ft.id as 'favoriteid'
FROM favorite_teams ft
JOIN teams t ON t.id = ft.team_id
JOIN Users u ON u.id = ft.user_id
WHERE 1=1";

$params = [];
if (!empty($team_name)) {
    $query .= " AND t.name LIKE :teamname";
    $params[":teamname"] = "%$team_name%";
}
if (!empty($user_name)) {
    $query .= " AND u.username LIKE :username";
    $params[":username"] = "%$user_name%";
}
$limit = 10;
if (isset($_GET["limit"]) && !is_nan($_GET["limit"])) {
    $limit = $_GET["limit"];
    if ($limit < 0 || $limit > 100) {
        $limit = 10;
    }
}
$query .= " ORDER BY t.name";
$query .= " LIMIT $limit";

try {
    $db = getDB();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    } else {
        $results = [];
    }
} catch (PDOException $e) {
    error_log("Error fetching favorites: " . var_export($e, true));
    flash("There was an error fetching favorites", "danger");
}

$querycount = "SELECT COUNT(*) FROM favorite_teams";
try {
    $db = getDB();
    $stmt = $db->prepare($querycount);
    $stmt->execute();
    $r = $stmt->fetch();
    if ($r) {
        $count = $r["COUNT(*)"];
    } else {
        $count = 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching favorite count: " . var_export($e, true));
    flash("There was an error fetching favorite count", "danger");
}

$title = "User Favorites (" . count($results) . ")";

$nonquery = "SELECT t.name AS 'Team Name', t.conference as 'Conference',t.id
FROM teams t
WHERE NOT EXISTS (
    SELECT 1
    FROM favorite_teams ft 
    WHERE ft.team_id = t.id
)";
$nonParams = [];
if ($type == "nonfavorites") {
    if (!empty($team_name)) {
        $nonquery .= " AND t.name LIKE :teamname";
        $nonParams[":teamname"] = "%$team_name%";
    }
}
$nonquery .= " ORDER BY t.name";
$nonquery .= " LIMIT $limit";

try {
    $db = getDB();
    $stmt = $db->prepare($nonquery);
    $stmt->execute($nonParams);
    $r = $stmt->fetchAll();
    if ($r) {
        $nonresults = $r;
    } else {
        $nonresults = [];
    }
} catch (PDOException $e) {
    error_log("Error fetching nonfavorites: " . var_export($e, true));
    flash("There was an error fetching nonfavorites", "danger");
}

$nonquerycount = "SELECT COUNT(*) FROM teams t WHERE NOT EXISTS (
    SELECT 1
    FROM favorite_teams ft 
    WHERE ft.team_id = t.id
)";
try {
    $db = getDB();
    $stmt = $db->prepare($nonquerycount);
    $stmt->execute();
    $r = $stmt->fetch();
    if ($r) {
        $noncount = $r["COUNT(*)"];
    } else {
        $noncount = 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching nonfavorite count: " . var_export($e, true));
    flash("There was an error fetching nonfavorite count", "danger");
}
$nontable = [
    "data" => $nonresults,
    "title" => "Non Favorites (" . count($nonresults) . ")",
    "empty_message" => "No non-favorites found",
    "ignored_columns" => ["id"],
    "view_url" => get_url("team_details.php"),
    "view_label" => "Details"
]

?>
<div class="container-fluid">
    <ul id="tabs" class="nav nav-pills justify-content-center">
        <li class="nav-item" style="margin-right: 20px">
            <a class="nav-link <?php echo $type == "favorites" ? "active" : "" ?>" href="#" onclick="switchTab('favorites')">User Favorites</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $type == "nonfavorites" ? "active" : "" ?>" href="#" onclick="switchTab('nonfavorites')">Non Favorites</a>
        </li>
    </ul>
    <div id="favorites" class="tab-target">
        <div class="mt-3">
            <h3>Total Favorites: <?php echo $count; ?></h3>
            <form>
                <div class="row mt-3">
                    <div class="col">
                        <?php render_input(["name" => "teamname", "type" => "text", "label" => "Team Name", "value" => $team_name]); ?>
                    </div>
                    <div class="col">
                        <?php render_input(["name" => "username", "type" => "text", "label" => "User Name", "value" => $user_name]); ?>
                    </div>
                    <div class="col">
                        <?php render_input(["name" => "limit", "type" => "number", "label" => "Limit", "value" => $limit]); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <?php render_input(["type" => "hidden", "name" => "type", "value" => "favorites"]); ?>
                        <?php render_button(["type" => "submit", "text" => "Search"]); ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="row">
            <div class="col-md-6 offset-md-3">
            <h3><?php se($title); ?></h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Team Name</th>
                        <th>Favorited By</th>
                        <th>Number of Favorites</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)) : ?>
                        <?php foreach ($results as $row) : ?>
                            <tr>
                                <td>
                                    <?php se($row["Team Name"]); ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_url("profile.php"); ?>?id=<?php se($row["userid"]); ?>"><?php se($row["Favorited By"]); ?></a>                            </td>
                                <td>
                                    <?php se($row["Number of Favorites"]); ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_url("team_details.php"); ?>?id=<?php se($row["teamid"]); ?>" class="btn btn-primary">Details</a>
                                    <a href="<?php echo get_url("update_favorite.php"); ?>?id=<?php se($row["favoriteid"]); ?>" class="btn btn-danger">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">No results found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div id="nonfavorites" class="tab-target" style="display: none;">
        <h3>Total Non Favorited Teams: <?php echo $noncount; ?></h3>
        <form>
            <div class="row mt-3">
                <div class="col" style="">
                    <?php render_input(["name" => "teamname", "type" => "text", "label" => "Team Name", "value" => $team_name]); ?>
                </div>
                <div class="col" style="">
                    <?php render_input(["name" => "limit", "type" => "number", "label" => "Limit", "value" => $limit]); ?>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <?php render_input(["type" => "hidden", "name" => "type", "value" => "nonfavorites"]); ?>
                    <?php render_button(["type" => "submit", "text" => "Search"]); ?>
                </div>
            </div>
        </form>
        <div style="width:50%; margin: auto;">
            <?php render_table($nontable); ?>
        </div>
    </div>
</div>
<script>

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type');
        
        switchTab(type || 'teams');
    });

    function switchTab(tab) {
        let target = document.getElementById(tab);
        if (target) {
            let eles = document.getElementsByClassName("tab-target");
            for (let ele of eles) {
                ele.style.display = (ele.id === tab) ? "block" : "none";
            }

            let navLinks = document.querySelectorAll("#tabs .nav-link");
            navLinks.forEach(link => link.classList.remove("active"));
            document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add("active");
        }
    }
</script>
<?php
require(__DIR__. "/../../partials/flash.php");
?>