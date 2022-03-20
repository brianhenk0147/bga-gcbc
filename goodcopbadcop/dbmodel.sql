
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- goodcopbadcop implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `last_player_investigated` varchar(30) NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `last_card_position_investigated` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `last_card_position_revealed` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `is_eliminated` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `is_wounded` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `aiming_at` varchar(30) NOT NULL DEFAULT '';


CREATE TABLE IF NOT EXISTS `integrityCards` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(30) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `guns` (
  `gun_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gun_held_by` varchar(30) NOT NULL DEFAULT '',
  `gun_aimed_at` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`gun_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `playerCardVisibility` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` varchar(30) NOT NULL,
  `is_seen` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`card_id`,`player_id`)
) ENGINE=InnoDB ;

CREATE TABLE IF NOT EXISTS `playerPositioning` (
  `player_asking` varchar(30) NOT NULL,
  `player_id` varchar(30) NOT NULL,
  `player_position` varchar(5) NOT NULL,
  PRIMARY KEY (`player_asking`,`player_id`)
) ENGINE=InnoDB ;
