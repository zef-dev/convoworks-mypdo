-- ----------------------------------------------------------------------------------
-- Alter service_releases table by adding meta column to service_releases table
-- ----------------------------------------------------------------------------------
ALTER TABLE `service_releases`
ADD COLUMN `meta` LONGTEXT AFTER `alias`;
