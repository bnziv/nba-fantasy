<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$team_name = se($_GET, "teamname", "", false);
$user_name = se($_GET, "username", "", false);
$limit = se($_GET, "limit", 10, false);

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

$title = "User Favorites (" . count($results) . ")";

?>
<div class="container-fluid">
    <div>
        <form>
            <div class="row">
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
                                <?php se($row["Favorited By"]); ?>
                            </td>
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

<?php
require(__DIR__. "/../../../partials/flash.php");
?>