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