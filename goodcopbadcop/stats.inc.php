<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * goodcopbadcop implementation : © Pull the Pin Games - support@pullthepingames.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * goodcopbadcop game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.

    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")

    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean

    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.

    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress

    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players

*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Turns taken"),
                    "type" => "int" ),
        "winning_team" => array("id"=> 13,
                    "name" => totranslate("Winning team"),
                    "type" => "int" ),
        "honest_at_start" => array("id"=> 14,
                    "name" => totranslate("Honest players at start"),
                    "type" => "int" ),
        "honest_at_end" => array("id"=> 15,
                    "name" => totranslate("Honest players at end"),
                    "type" => "int" ),

        "crooked_at_start" => array("id"=> 16,
                    "name" => totranslate("Crooked players at start"),
                    "type" => "int" ),

        "crooked_at_end" => array("id"=> 17,
                    "name" => totranslate("Crooked players at end"),
                    "type" => "int" ),
        "zombies_at_start" => array("id"=> 18,
                    "name" => totranslate("Zombie players at start"),
                    "type" => "int" ),
        "zombies_at_end" => array("id"=> 19,
                    "name" => totranslate("Zombie players at end"),
                    "type" => "int" ),
        "bombers_at_start" => array("id"=> 20,
                    "name" => totranslate("Bombers players at start"),
                    "type" => "int" ),
        "bombers_at_end" => array("id"=> 21,
                    "name" => totranslate("Bomber players at end"),
                    "type" => "int" ),
        "traitors_at_start" => array("id"=> 22,
                    "name" => totranslate("Traitor players at start"),
                    "type" => "int" ),
        "traitors_at_end" => array("id"=> 23,
                    "name" => totranslate("Traitor players at end"),
                    "type" => "int" ),

/*
        Examples:


        "table_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("table test stat 1"),
                                "type" => "int" ),

        "table_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("table test stat 2"),
                                "type" => "float" )
*/
    ),

    // Statistics existing for each player
    "player" => array(

        "starting_role" => array("id"=> 11,
                    "name" => totranslate("Starting role"),
                    "type" => "int" ),
        "ending_role" => array("id"=> 12,
                    "name" => totranslate("Ending role"),
                    "type" => "int" ),
        "investigations_completed" => array("id"=> 20,
                    "name" => totranslate("Investigations completed"),
                    "type" => "int" ),
        "equipment_acquired" => array("id"=> 21,
                    "name" => totranslate("Equipment acquired"),
                    "type" => "int" ),
        "equipment_used" => array("id"=> 22,
                    "name" => totranslate("Equipment used"),
                    "type" => "int" ),
        "guns_acquired" => array("id"=> 23,
                    "name" => totranslate("Guns acquired"),
                    "type" => "int" ),
        "guns_aimed_at_me" => array("id"=> 24,
                    "name" => totranslate("Guns aimed at me"),
                    "type" => "int" ),
        "opponents_shot" => array("id"=> 25,
                    "name" => totranslate("Opponents shot"),
                    "type" => "int" ),
        "teammates_shot" => array("id"=> 26,
                    "name" => totranslate("Teammates shot"),
                    "type" => "int" ),
        "bullets_taken" => array("id"=> 27,
                    "name" => totranslate("Times shot"),
                    "type" => "int" ),
        "players_bitten" => array("id"=> 28,
                    "name" => totranslate("Players bitten"),
                    "type" => "int" ),
        "bites_taken" => array("id"=> 29,
                    "name" => totranslate("Bites taken"),
                    "type" => "int" ),

/*
        Examples:


        "player_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("player test stat 1"),
                                "type" => "int" ),

        "player_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("player test stat 2"),
                                "type" => "float" )

*/
    ),
    "value_labels" => array(
		    11 => array(
			       0 => totranslate("Honest Agent"),
			       1 => totranslate("Honest Cop"),
			       2 => totranslate("Crooked Kingpin"),
 			       3 => totranslate("Crooked Cop"),
             4 => totranslate("Zombie Infector"),
             5 => totranslate("Zombie Minion"),
             6 => totranslate("Kingpin Agent"),
             7 => totranslate("Infector Agent"),
             8 => totranslate("Infector Kingpin"),
             9 => totranslate("Bomber"),
             10 => totranslate("Traitor")
		    ),
        12 => array(
			       0 => totranslate("Honest Agent"),
			       1 => totranslate("Honest Cop"),
			       2 => totranslate("Crooked Kingpin"),
 			       3 => totranslate("Crooked Cop"),
             4 => totranslate("Zombie Infector"),
             5 => totranslate("Zombie Minion"),
             6 => totranslate("Kingpin Agent"),
             7 => totranslate("Infector Agent"),
             8 => totranslate("Infector Kingpin"),
             9 => totranslate("Bomber"),
             10 => totranslate("Traitor")
		    ),
        13 => array(
			       0 => totranslate("Honest"),
			       1 => totranslate("Crooked"),
			       2 => totranslate("Zombie"),
             3 => totranslate("Bomber"),
             4 => totranslate("Traitor"),
             5 => totranslate("Solo"),
             6 => totranslate("Bomber and Traitor")
		    ),
    )

);
