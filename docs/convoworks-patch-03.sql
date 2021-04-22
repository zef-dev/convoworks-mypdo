-- ----------------------------------------------------------------------------------
-- Create convoworks_cache table
-- ----------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `convoworks_cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` LONGTEXT NOT NULL DEFAULT '',
  `time_created` INT NULL DEFAULT 0,
  `expires` INT NULL DEFAULT 0,
  PRIMARY KEY (`key`)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4;
