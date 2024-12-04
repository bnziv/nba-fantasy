CREATE TABLE IF NOT EXISTS  `standings` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `season` INT NOT NULL,
    `team_api_id` INT NOT NULL,
    `wins` INT NOT NULL,
    `losses` INT NOT NULL,
    `win_percentage` FLOAT NOT NULL,
    `conference_rank` INT NOT NULL,
    `division_rank` INT NOT NULL,
    `home_record` VARCHAR(5) NOT NULL,
    `away_record` VARCHAR(5) NOT NULL,
    `streak` VARCHAR(5) NOT NULL,
    `last_10` VARCHAR(5) NOT NULL,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_api_id`) REFERENCES `teams`(`api_id`) ON DELETE CASCADE
)