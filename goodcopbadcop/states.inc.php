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
    		"descriptionmyturn" => clienttranslate('${you} must select which action you will take.'),
    		"type" => "activeplayer",
        'args' => 'argGetPlayerTurnButtonList',
    		"possibleactions" => array( "clickInvestigateButton", "clickArmButton", "clickShootButton", "clickEquipButton", "clickEquipmentCard", "clickSkipButton", "clickUseEquipmentButton" ),
    		"transitions" => array( "investigateChooseCard" => 3, "armChooseCard" => 6, "askShootReaction" => 8, "equipChooseCard" => 10, "useEquipment" => 15, "executeEquip" => 11, "chooseIntegrityCards" => 40, "choosePlayer" => 41, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "executeArm" => 7, "executeEquipment" => 31, "rollInfectionDie" => 50 )
    ),

    3 => array(
    		"name" => "chooseCardToInvestigate",
    		"description" => clienttranslate('${actplayer} is investigating.'),
    		"descriptionmyturn" => clienttranslate('${you} must select which Integrity Card you will investigate.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickOpponentIntegrityCard", "clickCancelButton" ),
    		"transitions" => array( "askInvestigateReaction" => 4, "executeActionInvestigate" => 5, "cancelAction" => 2, "playerAction" => 2 )
    ),

    4 => array(
    		"name" => "askInvestigateReaction",
    		"description" => clienttranslate('Other players are deciding if they will use an Equipment.'),
    		"descriptionmyturn" => clienttranslate('${you} may use Equipment in reaction to the Investigate action.'),
    		"type" => "multipleactiveplayer",
    		"possibleactions" => array( "clickUseEquipmentButton", "clickPassOnUseEquipmentButton", "clickEquipmentCard" ),
    		"transitions" => array( "useEquipment" => 17, "allPassedOnReactions" => 5 )
    ),

    5 => array(
        "name" => "executeActionInvestigate",
        "description" => "",
        "type" => "game",
        "action" => "executeActionInvestigate",
        "updateGameProgression" => false,
        "transitions" => array( "askAim" => 27, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "rollInfectionDie" => 50, "discardEquipment" => 12 )
    ),

    6 => array(
    		"name" => "chooseCardToRevealForArm",
    		"description" => clienttranslate('${actplayer} is choosing a card to reveal.'),
    		"descriptionmyturn" => clienttranslate('${you} must select which Integrity Card you will reveal.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickMyIntegrityCard", "clickCancelButton" ),
    		"transitions" => array( "executeArm" => 7, "playerAction" => 2 )
    ),

    7 => array(
        "name" => "executeActionArm",
        "description" => "",
        "type" => "game",
        "action" => "executeActionArm",
        "updateGameProgression" => false,
        "transitions" => array( "askAim" => 27 )
    ),

    8 => array(
    		"name" => "askShootReaction",
    		"description" => clienttranslate('Other players are deciding if they will use Equipment as a reaction to the Shoot action.'),
    		"descriptionmyturn" => clienttranslate('${you} may use Equipment as a reaction to the Shoot action.'),
    		"type" => "multipleactiveplayer",
    		"possibleactions" => array( "clickUseEquipmentButton", "clickPassOnUseEquipmentButton", "clickEquipmentCard" ),
    		"transitions" => array( "useEquipment" => 18, "allPassedOnReactions" => 9, "askShootReaction" => 8 )
    ),

    9 => array(
        "name" => "executeActionShoot",
        "description" => "",
        "type" => "game",
        "action" => "executeActionShoot",
        "updateGameProgression" => false,
        "transitions" => array( "endTurnReaction" => 29, "allPassedOnReactions" => 30, "endGame" => 99, "rollInfectionDie" => 50, "askAim" => 27, "askAimMustReaim" => 26, "discardEquipment" => 12, "askBiteReaction" => 51, "askDiscardOutOfTurn" => 35 )
    ),

    10 => array(
    		"name" => "chooseCardToRevealForEquip",
    		"description" => clienttranslate('${actplayer} is choosing a card to reveal.'),
    		"descriptionmyturn" => clienttranslate('${you} must select which Integrity Card you will reveal.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickMyIntegrityCard", "clickCancelButton" ),
    		"transitions" => array( "executeEquip" => 11, "cancelAction" => 2 )
    ),

    11 => array(
        "name" => "executeActionEquip",
        "description" => "",
        "type" => "game",
        "action" => "executeActionEquip",
        "updateGameProgression" => false,
        "transitions" => array( "askAim" => 27, "discardEquipment" => 12, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "rollInfectionDie" => 50 )
    ),

    12 => array(
    		"name" => "discardEquipment",
    		"description" => clienttranslate('${actplayer} is discarding Equipment.'),
    		"descriptionmyturn" => clienttranslate('${you} must select an Equipment in your hand to discard.'),
    		"type" => "activeplayer",
        'args' => 'argGetPlayerTurnDiscardToDiscardButtonList',
    		"possibleactions" => array( "clickEquipmentCard" ),
    		"transitions" => array( "askAim" => 27, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "rollInfectionDie" => 50 )
    ),

    15 => array(
    		"name" => "chooseEquipmentToPlayOnYourTurn",
    		"description" => clienttranslate('${actplayer} is using Equipment.'),
    		"descriptionmyturn" => clienttranslate('${you} must select which Equipment in your hand you will play.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickEquipmentCard", "clickCancelButton" ),
    		"transitions" => array( "cancelEquipmentUse" => 2, "chooseIntegrityCards" => 40, "choosePlayer" => 41, "chooseActiveOrHandEquipmentCard" => 42, "executeEquipment" => 31, "playerAction" => 2 )
    ),

    16 => array(
        "name" => "chooseEquipmentToPlayReactEndOfTurn",
        "description" => clienttranslate('${actplayer} is using Equipment.'),
        "descriptionmyturn" => clienttranslate('${you} must select which Equipment in your hand you will play.'),
        "type" => "activeplayer",
        "possibleactions" => array( "clickEquipmentCard", "clickCancelButton" ),
        "transitions" => array( "cancelEquipmentUse" => 29, "chooseIntegrityCards" => 40, "choosePlayer" => 41, "chooseActiveOrHandEquipmentCard" => 42, "executeEquipment" => 31 )
    ),

    17 => array(
    		"name" => "chooseEquipmentToPlayReactInvestigate",
    		"description" => clienttranslate('${actplayer} is using Equipment.'),
    		"descriptionmyturn" => clienttranslate('${you} must select which Equipment in your hand you will play.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickEquipmentCard", "clickCancelButton" ),
    		"transitions" => array( "cancelEquipmentUse" => 4, "chooseIntegrityCards" => 40, "choosePlayer" => 41, "chooseActiveOrHandEquipmentCard" => 42, "executeEquipment" => 31 )
    ),

    18 => array(
    		"name" => "chooseEquipmentToPlayReactShoot",
    		"description" => clienttranslate('${actplayer} is using Equipment.'),
    		"descriptionmyturn" => clienttranslate('${you} must select which Equipment in your hand you will play.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickEquipmentCard", "clickCancelButton" ),
    		"transitions" => array( "cancelEquipmentUse" => 8, "chooseIntegrityCards" => 40, "choosePlayer" => 41, "chooseEquipmentTargetOutOfTurn" => 32, "chooseActiveOrHandEquipmentCard" => 42, "executeEquipment" => 31 )
    ),

    19 => array(
    		"name" => "chooseEquipmentToPlayReactBite",
    		"description" => clienttranslate('${actplayer} is using Equipment.'),
    		"descriptionmyturn" => clienttranslate('${you} must select which Equipment in your hand you will play.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickEquipmentCard", "clickCancelButton" ),
    		"transitions" => array( "cancelEquipmentUse" => 51, "chooseIntegrityCards" => 40, "choosePlayer" => 41, "chooseEquipmentTargetOutOfTurn" => 32, "chooseActiveOrHandEquipmentCard" => 42, "executeEquipment" => 31 )
    ),

    26 => array(
    		"name" => "askAimMustReaim",
    		"description" => clienttranslate('${actplayer} is choosing a new target.'),
    		"descriptionmyturn" => clienttranslate('${you} must change your aim.'),
    		"type" => "activeplayer",
        'args' => 'argGetGunTargetsMustReaim',
    		"possibleactions" => array( "clickPlayer" ),
    		"transitions" => array( "aimAtPlayer" => 28, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "rollInfectionDie" => 50, "askAimMustReaim" => 26, "askAim" => 27 )
    ),

    27 => array(
    		"name" => "askAim",
    		"description" => clienttranslate('${actplayer} is deciding if they will change their aim.'),
    		"descriptionmyturn" => clienttranslate('${you} may change your aim.'),
    		"type" => "activeplayer",
        'args' => 'argGetGunTargets',
    		"possibleactions" => array( "clickPlayer", "clickEndTurnButton" ),
    		"transitions" => array( "aimAtPlayer" => 28, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "rollInfectionDie" => 50, "askAimMustReaim" => 26, "askAim" => 27, "endGame" => 99, "allPassedOnReactions" => 30 )
    ),

    28 => array(
    		"name" => "aimAtPlayer",
    		"description" => clienttranslate('${actplayer} is aiming.'),
    		"descriptionmyturn" => clienttranslate('${you} must aim at a player.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickPlayer", "clickCancelButton" ),
    		"transitions" => array( "endTurn" => 29, "cancelAim" => 27, "rollInfectionDie" => 50 )
    ),

    29 => array(
        "name" => "askEndTurnReaction",
        "description" => clienttranslate('Other players are deciding if they will use an Equipment at the end of this player turn.'),
        "descriptionmyturn" => clienttranslate('${you} may use Equipment at the end of this player turn.'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "clickPassOnUseEquipmentButton", "clickUseEquipmentButton", "clickEquipmentCard" ),
        "transitions" => array( "allPassedOnReactions" => 30, "endTurnReaction" => 29, "useEquipment" => 16 )
    ),

    30 => array(
        "name" => "endPlayerTurn",
        "description" => "",
        "type" => "game",
        "action" => "endTurnCleanup",
        "updateGameProgression" => true,
        "transitions" => array( "startNewPlayerTurn" => 2 )
    ),

    31 => array(
        "name" => "executeEquipmentPlay",
        "description" => "",
        "type" => "game",
        "action" => "executeEquipmentPlay",
        "updateGameProgression" => true,
        "transitions" => array( "playerTurn" => 2, "askInvestigateReaction" => 4, "executeActionInvestigate" => 5, "askShootReaction" => 8, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "askAimOutOfTurn" => 33, "askDiscardOutOfTurn" => 35, "endGame" => 99, "allPassedOnReactions" => 30, "rollInfectionDie" => 50, "executeActionBite" => 52, "askBiteReaction" => 51, "askDiscardOutOfTurn" => 35 )
    ),

    32 => array(
        "name" => "chooseEquipmentTargetOutOfTurn",
        "description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
        "type" => "game",
        "action" => "chooseEquipmentTargetOutOfTurn",
        "updateGameProgression" => false,
        "transitions" => array( "choosePlayer" => 41, "choosePlayerNoCancel" => 44 )
    ),

    33 => array(
    		"name" => "askAimOutOfTurn",
    		"description" => clienttranslate('${actplayer} is aiming the gun they just got.'),
    		"descriptionmyturn" => clienttranslate('${you} must aim your new gun.'),
    		"type" => "activeplayer",
        'args' => 'argGetGunTargets',
    		"possibleactions" => array( "clickPlayer"),
    		"transitions" => array( "afterAimedOutOfTurn" => 34 )
    ),

    34 => array(
        "name" => "afterAimedOutOfTurn",
        "description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
        "type" => "game",
        "action" => "afterAimedOutOfTurn",
        "updateGameProgression" => false,
        "transitions" => array( "playerTurn" => 2, "askInvestigateReaction" => 4, "executeActionInvestigate" => 5, "askShootReaction" => 8, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "askAimOutOfTurn" => 33, "rollInfectionDie" => 50 )
    ),

    35 => array(
    		"name" => "discardOutOfTurn",
    		"description" => clienttranslate('${actplayer} is discarding an Equipment.'),
    		"descriptionmyturn" => clienttranslate('${you} must discard an Equipment in your hand.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickEquipmentCard"),
    		"transitions" => array( "afterDiscardedOutOfTurn" => 36 )
    ),

    36 => array(
        "name" => "afterDiscardedOutOfTurn",
        "description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
        "type" => "game",
        "action" => "afterDiscardedOutOfTurn",
        "updateGameProgression" => false,
        "transitions" => array( "playerTurn" => 2, "askInvestigateReaction" => 4, "executeActionInvestigate" => 5, "askShootReaction" => 8, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "rollInfectionDie" => 50 )
    ),

    40 => array(
    		"name" => "chooseIntegrityCards",
    		"description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
    		"descriptionmyturn" => clienttranslate('${you} must select an Integrity Card to target with the Equipment.'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "clickConfirmButton", "clickCancelButton", "clickOpponentIntegrityCard", "clickMyIntegrityCard", "clickDoneSelectingButton" ),
    		"transitions" => array( "executeEquipment" => 31, "chooseIntegrityCards" => 40, "playerTurn" => 2, "chooseEquipmentToPlayReactEndOfTurn" => 16, "chooseEquipmentToPlayReactInvestigate" => 17, "chooseEquipmentToPlayReactShoot" => 18, "chooseEquipmentToPlayOnYourTurn" => 15, "chooseEquipmentToPlayReactBite" => 19 )
    ),

    41 => array(
    		"name" => "choosePlayer",
    		"description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
    		"descriptionmyturn" => clienttranslate('${you} must choose a player to target with the Equipment.'),
    		"type" => "activeplayer",
        'args' => 'argGetPlayerButtonTargets',
    		"possibleactions" => array( "clickPlayer", "clickCancelButton" ),
    		"transitions" => array( "executeEquipment" => 31, "choosePlayer" => 41, "chooseActiveOrHandEquipmentCard" => 42, "playerTurn" => 2, "chooseEquipmentToPlayReactEndOfTurn" => 16, "chooseEquipmentToPlayReactInvestigate" => 17, "chooseEquipmentToPlayReactShoot" => 18, "chooseEquipmentToPlayOnYourTurn" => 15, "chooseEquipmentToPlayReactBite" => 19 )
    ),

    42 => array(
    		"name" => "chooseActiveOrHandEquipmentCard",
    		"description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
    		"descriptionmyturn" => clienttranslate('${you} must select an Equipment to give to another player.'),
    		"type" => "activeplayer",
        'args' => 'argGetPlayerBoardEquipment',
    		"possibleactions" => array( "clickEquipmentCardToTarget", "clickEquipmentCard", "clickCancelButton" ),
    		"transitions" => array( "executeEquipment" => 31, "chooseAnotherPlayer" => 43, "playerAction" => 2, "chooseEquipmentToPlayReactEndOfTurn" => 16, "chooseEquipmentToPlayReactInvestigate" => 17, "chooseEquipmentToPlayReactShoot" => 18 )
    ),

    43 => array(
    		"name" => "chooseAnotherPlayer",
    		"description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
    		"descriptionmyturn" => clienttranslate('${you} must select another player to target with the Equipment.'),
    		"type" => "activeplayer",
        'args' => 'argGetPlayerButtonTargets',
    		"possibleactions" => array( "clickPlayer", "clickCancelButton" ),
    		"transitions" => array( "executeEquipment" => 31, "chooseActiveOrHandEquipmentCard" => 42, "chooseEquipmentToPlayReactEndOfTurn" => 16 )
    ),

    44 => array(
    		"name" => "choosePlayerNoCancel",
    		"description" => clienttranslate('${actplayer} is choosing an Equipment target.'),
    		"descriptionmyturn" => clienttranslate('${you} must select a player to target with the Equipment.'),
    		"type" => "activeplayer",
        'args' => 'argGetPlayerButtonTargets',
    		"possibleactions" => array( "clickPlayer" ),
    		"transitions" => array( "executeEquipment" => 31, "choosePlayer" => 41, "chooseActiveOrHandEquipmentCard" => 42, "playerTurn" => 2, "chooseEquipmentToPlayReactEndOfTurn" => 16, "chooseEquipmentToPlayReactInvestigate" => 17, "chooseEquipmentToPlayReactShoot" => 18, "chooseEquipmentToPlayOnYourTurn" => 15, "chooseEquipmentToPlayReactBite" => 19 )
    ),

    50 => array(
        "name" => "rollInfectionDie",
        "description" => clienttranslate('${actplayer} is rolling the Infection Die.'),
        "type" => "game",
        "action" => "rollInfectionDie",
        "updateGameProgression" => true,
        "transitions" => array( "endTurnReaction" => 29, "allPassedOnReactions" => 30 )
    ),

    51 => array(
    		"name" => "askBiteReaction",
    		"description" => clienttranslate('Other players are deciding if they will use an Equipment in reaction to the Bite.'),
    		"descriptionmyturn" => clienttranslate('${you} may use Equipment in reaction to the Bite.'),
    		"type" => "multipleactiveplayer",
    		"possibleactions" => array( "clickUseEquipmentButton", "clickPassOnUseEquipmentButton", "clickEquipmentCard" ),
    		"transitions" => array( "useEquipment" => 19, "allPassedOnReactions" => 52 )
    ),

    52 => array(
        "name" => "executeActionBite",
        "description" => "",
        "type" => "game",
        "action" => "executeActionBite",
        "updateGameProgression" => true,
        "transitions" => array( "askAim" => 27, "discardEquipment" => 12, "endTurnReaction" => 29, "allPassedOnReactions" => 30, "rollInfectionDie" => 50, "askAimMustReaim" => 26, "endGame" => 99 )
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
