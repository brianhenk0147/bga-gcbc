<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * goodcopbadcop implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * goodcopbadcop game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),

    // Note: ID=2 => your first state

    2 => array(
    		"name" => "playerTurn",
    		"description" => clienttranslate('${actplayer} is choosing their turn action.'),
    		"descriptionmyturn" => clienttranslate('${you} must choose which action you will take.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickInvestigateButton", "clickArmButton" ),
    		"transitions" => array( "investigateChooseCard" => 3, "armChooseCard" => 6 )
    ),

    3 => array(
    		"name" => "chooseCardToInvestigate",
    		"description" => clienttranslate('${actplayer} is investigating.'),
    		"descriptionmyturn" => clienttranslate('${you} must choose which Integrity Card you will investigate.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickOpponentIntegrityCard", "clickCancelAction" ),
    		"transitions" => array( "askInvestigateReaction" => 4, "cancelAction" => 2 )
    ),

    4 => array(
    		"name" => "askInvestigateReaction",
    		"description" => clienttranslate('Other players are deciding if they will use an Equipment card.'),
    		"descriptionmyturn" => clienttranslate('${you} may use Equipment in reaction to the investigation.'),
    		"type" => "multipleactiveplayer",
    		"possibleactions" => array( "useEquipment", "clickPassOnUseEquipmentButton" ),
    		"transitions" => array( "useEquipment" => 2, "allPassedOnReactions" => 5 )
    ),

    5 => array(
        "name" => "executeActionInvestigate",
        "description" => "",
        "type" => "game",
        "action" => "executeActionInvestigate",
        "updateGameProgression" => false,
        "transitions" => array( "askAim" => 27, "endTurnReaction" => 29 )
    ),

    6 => array(
    		"name" => "chooseCardToRevealForArm",
    		"description" => clienttranslate('${actplayer} is choosing a card to reveal.'),
    		"descriptionmyturn" => clienttranslate('${you} must choose which Integrity Card you will reveal.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickMyIntegrityCard", "clickCancelAction" ),
    		"transitions" => array( "executeArm" => 7, "cancelAction" => 2 )
    ),

    7 => array(
        "name" => "executeActionArm",
        "description" => "",
        "type" => "game",
        "action" => "executeActionArm",
        "updateGameProgression" => false,
        "transitions" => array( "askAim" => 27 )
    ),

    27 => array(
    		"name" => "askAim",
    		"description" => clienttranslate('${actplayer} is deciding if they will change their aim.'),
    		"descriptionmyturn" => clienttranslate('${you} may change your aim.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickAimPlayerButton", "clickEndTurnButton" ),
    		"transitions" => array( "aimAtPlayer" => 28, "endTurnReaction" => 29 )
    ),

    28 => array(
    		"name" => "aimAtPlayer",
    		"description" => clienttranslate('${actplayer} is aiming.'),
    		"descriptionmyturn" => clienttranslate('${you} must aim at a player.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickAimPlayerButton", "clickCancelButton" ),
    		"transitions" => array( "endTurn" => 29, "cancelAim" => 27 )
    ),

    29 => array(
        "name" => "askEndTurnReaction",
        "description" => clienttranslate('Other players are deciding if they will use an Equipment card at the end of this turn.'),
        "descriptionmyturn" => clienttranslate('${you} may use Equipment at the end of this turn.'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "useEquipment", "clickPassOnUseEquipmentButton" ),
        "transitions" => array( "useEquipment" => 2, "allPassedOnReactions" => 30 )
    ),

    30 => array(
        "name" => "endPlayerTurn",
        "description" => "",
        "type" => "game",
        "action" => "endTurnCleanup",
        "updateGameProgression" => false,
        "transitions" => array( "startNewPlayerTurn" => 2 )
    ),

/*
    Examples:

    2 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
    ),

    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ),

*/

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
