<?php
/** 
* Fetches teams from the API based on their code
* 
* @param string $code The team code to fetch
**/
function fetch_team($code) {
    $params = ["code" => $code];
    $endpoint = "https://api-nba-v1.p.rapidapi.com/teams";
    $isRapidAPI = true;
    $rapidAPIHost = "api-nba-v1.p.rapidapi.com";
    $result = get($endpoint, "API_KEY", $params, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"]) && !empty($result["response"]["response"])) {
        $result = json_decode($result["response"], true);
        $result = $result["response"];

        //Filter out non-NBA franchises and get the first
        $result = array_filter($result, function ($team) {
            return isset($team["nbaFranchise"]) && $team["nbaFranchise"];
        });
        $result = $result[0];
        error_log(var_export($result, true));
        $result = [
            "api_id" => $result["id"],
            "name" => $result["name"],
            "nickname" => $result["nickname"],
            "code" => $result["code"],
            "city" => $result["city"],
            "logo_url" => $result["logo"],
            "conference" => $result["leagues"]["standard"]["conference"],
            "division" => $result["leagues"]["standard"]["division"]
        ];
    } else {
        $result = [];
    }
    return $result;
}

/**
 * Fetches all NBA teams from the API
 *
 **/
function fetch_all_teams() {
    $params = ["league" => "standard"];
    $endpoint = "https://api-nba-v1.p.rapidapi.com/teams";
    $isRapidAPI = true;
    $rapidAPIHost = "api-nba-v1.p.rapidapi.com";
    $result = get($endpoint, "API_KEY", $params, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
        $result = $result["response"];
        $result = array_filter($result, function ($team) {
            return isset($team["nbaFranchise"]) && $team["nbaFranchise"] && !$team["allStar"];
        });
        $result = array_values($result);
        $result = array_map(function ($team) {
            return [
                "api_id" => $team["id"],
                "name" => $team["name"],
                "nickname" => $team["nickname"],
                "code" => $team["code"],
                "city" => $team["city"],
                "logo_url" => $team["logo"],
                "conference" => $team["leagues"]["standard"]["conference"],
                "division" => $team["leagues"]["standard"]["division"]
            ];
        }, $result);
    } else {
        $result = [];
    }
    return $result;
}

/**
 * Fetches all players from the API based on the team
 */
function fetch_players($team_api_id) {
    $params = ["team" => $team_api_id, "season" => "2024"];
    $endpoint = "https://api-nba-v1.p.rapidapi.com/players";
    $isRapidAPI = true;
    $rapidAPIHost = "api-nba-v1.p.rapidapi.com";
    $result = get($endpoint, "API_KEY", $params, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
        if (!isset($result["response"])) {
            return [];
        }
        $result = $result["response"];
        $team_id = get_team_db_id($team_api_id);
        $result = array_map(function ($player) use ($team_id) {
            $height = isset($player["height"]["feets"]) ? $player["height"]["feets"] . "'" . ($player["height"]["inches"] ?? 0). '"' : null;
            $weight = isset($player["weight"]["pounds"]) ? $player["weight"]["pounds"] . " lbs" : null;
            if (isset($player["leagues"]["standard"])) {
                $standardKey = "standard";
            } elseif (isset($player["leagues"]["Standard"])) {
                $standardKey = "Standard";
            }
            return [
                "api_id" => $player["id"],
                "first_name" => $player["firstname"],
                "last_name" => $player["lastname"],
                "height" => $height,
                "weight" => $weight,
                "jersey_number" => $player["leagues"][$standardKey]["jersey"],
                "position" => $player["leagues"][$standardKey]["pos"],
                "team_id" => $team_id
            ];
        }, $result);
    } else {
        $result = [];
    }
    return $result;
}

/**
 * Fetch standings from the API
 */
function fetch_standings() {
    $params = ["season" => "2024", "league" => "standard"];
    $endpoint = "https://api-nba-v1.p.rapidapi.com/standings";
    $isRapidAPI = true;
    $rapidAPIHost = "api-nba-v1.p.rapidapi.com";
    $result = get($endpoint, "API_KEY", $params, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
        $result = $result["response"];
        $result = array_map(function ($team) {
            return [
                "season" => $team["season"],
                "team_api_id" => $team["team"]["id"],
                "wins" => $team["win"]["total"],
                "losses" => $team["loss"]["total"],
                "win_percentage" => $team["win"]["percentage"],
                "conference_rank" => $team["conference"]["rank"],
                "division_rank" => $team["division"]["rank"],
                "home_record" => $team["win"]["home"] . "-" . $team["loss"]["home"],
                "away_record" => $team["win"]["away"] . "-" . $team["loss"]["away"],
                "streak" => $team["streak"] . ($team["winStreak"] ? "W" : "L"),
                "last_10" => $team["win"]["lastTen"] . "-" . $team["loss"]["lastTen"],
            ];
        }, $result);
    } else {
        $result = [];
    }
    return $result;
}

/**
 * Fetches all games from the API
 */
function fetch_games() {
    $params = ["season" => "2024"];
    $endpoint = "https://api-nba-v1.p.rapidapi.com/games";
    $isRapidAPI = true;
    $rapidAPIHost = "api-nba-v1.p.rapidapi.com";
    $result = get($endpoint, "API_KEY", $params, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
        $result = $result["response"];
        $result = array_filter($result, function ($game) {
            return $game["stage"] == 2;
        });
        $result = array_values($result);
        $result = array_map(function ($game) {
            $date = new DateTime($game["date"]["start"]);
            $date = $date->format("Y-m-d H:i:s");
            return [
                "api_id" => $game["id"],
                "season" => $game["season"],
                "date" => $date,
                "home_team_api_id" => $game["teams"]["home"]["id"],
                "away_team_api_id" => $game["teams"]["visitors"]["id"],
                "home_score" => $game["scores"]["home"]["points"],
                "away_score" => $game["scores"]["visitors"]["points"],
                "arena" => $game["arena"]["name"] . ", " . $game["arena"]["city"] . ", " . $game["arena"]["state"],
                "status" => $game["status"]["long"],
            ];
        }, $result);
    } else {
        $result = [];
    }
    return $result;
}