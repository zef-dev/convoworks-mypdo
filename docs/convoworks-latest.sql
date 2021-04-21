-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema convoworks
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `service_data`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `service_data` ;

CREATE TABLE IF NOT EXISTS `service_data` (
  `service_id` VARCHAR(255) NOT NULL,
  `workflow` LONGTEXT NOT NULL DEFAULT '',
  `meta` TEXT NOT NULL DEFAULT '',
  `config` TEXT NOT NULL DEFAULT '',
  PRIMARY KEY (`service_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `service_params`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `service_params` ;

CREATE TABLE IF NOT EXISTS `service_params` (
  `service_id` VARCHAR(255) NOT NULL,
  `scope_type` VARCHAR(50) NOT NULL,
  `level_type` VARCHAR(50) NOT NULL,
  `key` VARCHAR(255) NOT NULL,
  `value` LONGTEXT NOT NULL DEFAULT '',
  `time_created` INT NULL DEFAULT 0,
  `time_updated` INT NULL DEFAULT 0,
  UNIQUE INDEX `SERVICE_PARAMS_UNIQUE` (`service_id` ASC, `level_type` ASC, `scope_type` ASC, `key` ASC),
  CONSTRAINT `FK_PARAMS_SERVICE`
    FOREIGN KEY (`service_id`)
    REFERENCES `service_data` (`service_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `service_releases`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `service_releases` ;

CREATE TABLE IF NOT EXISTS `service_releases` (
  `service_id` VARCHAR(255) NOT NULL,
  `release_id` VARCHAR(50) NOT NULL,
  `platform_id` VARCHAR(50) NOT NULL,
  `version_id` VARCHAR(50) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `stage` VARCHAR(50) NOT NULL,
  `alias` VARCHAR(50) NOT NULL,
  `meta` LONGTEXT NOT NULL,
  `time_created` INT NULL DEFAULT 0,
  `time_updated` INT NULL DEFAULT 0,
  UNIQUE INDEX `UNIQUE_SERVICE_RELEASE` (`service_id` ASC, `release_id` ASC),
  CONSTRAINT `FK_REKLEASE_SERVICE`
    FOREIGN KEY (`service_id`)
    REFERENCES `service_data` (`service_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `service_versions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `service_versions` ;

CREATE TABLE IF NOT EXISTS `service_versions` (
  `service_id` VARCHAR(255) NOT NULL,
  `version_id` VARCHAR(50) NOT NULL,
  `release_id` VARCHAR(50) NULL DEFAULT NULL,
  `version_tag` VARCHAR(255) NULL DEFAULT NULL,
  `workflow` LONGTEXT NOT NULL DEFAULT '',
  `config` TEXT NOT NULL DEFAULT '',
  `time_created` INT NULL DEFAULT 0,
  `time_updated` INT NULL DEFAULT 0,
  UNIQUE INDEX `UNIQUE_SERVICE_VERSION` (`service_id` ASC, `version_id` ASC),
  CONSTRAINT `FK_VERSION_SERVICE`
    FOREIGN KEY (`service_id`)
    REFERENCES `service_data` (`service_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
