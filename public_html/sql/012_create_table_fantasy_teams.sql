CREATE TABLE IF NOT EXISTS `fantasy_teams` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `guard_1_id` INT REFERENCES `players`(`id`) ON DELETE CASCADE,
    `guard_2_id` INT REFERENCES `players`(`id`) ON DELETE CASCADE,
    `forward_1_id` INT REFERENCES `players`(`id`) ON DELETE CASCADE,
    `forward_2_id` INT REFERENCES `players`(`id`) ON DELETE CASCADE,
    `center_id` INT REFERENCES `players`(`id`) ON DELETE CASCADE,
    `created_by` INT REFERENCES `users`(`id`) ON DELETE CASCADE,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE(`name`)
)