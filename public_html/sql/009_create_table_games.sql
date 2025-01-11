CREATE TABLE IF NOT EXISTS `games` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `api_id` INT NOT NULL UNIQUE,
    `season` INT NOT NULL,
    `date` TIMESTAMP NOT NULL,
    `home_team_api_id` INT NOT NULL,
    `away_team_api_id` INT NOT NULL,
    `home_score` INT,
    `away_score` INT,
    `arena` VARCHAR(100) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`home_team_api_id`) REFERENCES `teams`(`api_id`) ON DELETE CASCADE,
    FOREIGN KEY (`away_team_api_id`) REFERENCES `teams`(`api_id`) ON DELETE CASCADE
)