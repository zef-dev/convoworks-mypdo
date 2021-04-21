-- ----------------------------------------------------------------------------------
-- Alter service_releases table by adding meta column to service_releases table
-- ----------------------------------------------------------------------------------
ALTER TABLE `service_params`
    ADD COLUMN `time_created` INT NULL DEFAULT 0,
    ADD COLUMN `time_updated` INT NULL DEFAULT 0;
