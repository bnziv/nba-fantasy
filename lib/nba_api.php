<?php
/** 
* Fetches teams from the API based on a search
* 
* @param string $search The search term
**/
function fetch_team($search) {
    $params = ["search" => $search];
    $endpoint = "https://api-nba-v1.p.rapidapi.com/teams";
    $isRapidAPI = true;
    $rapidAPIHost = "api-nba-v1.p.rapidapi.com";
    $result = get($endpoint, "API_KEY", $params, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);

        $result = array_filter($result, function ($team) {
            return isset($team["nbaFranchise"]) && $team["nbaFranchise"];
        });
        $result = array_values($result);
    } else {
        $result = [];
    }
    return $result;
}