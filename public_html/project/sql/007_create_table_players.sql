CREATE TABLE IF NOT EXISTS `players` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `api_id` INT DEFAULT NULL UNIQUE,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `height` VARCHAR(10),
    `weight` VARCHAR(10),
    `jersey_number` INT,
    `position` VARCHAR(20),
    `team_api_id` INT NOT NULL,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_api_id`) REFERENCES `teams`(`api_id`) ON DELETE CASCADE
)