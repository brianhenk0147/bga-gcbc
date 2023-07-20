/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * goodcopbadcop implementation : © Pull the Pin Games - support@pullthepingames.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * goodcopbadcop.js
 *
 * goodcopbadcop user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.goodcopbadcop", ebg.core.gamegui, {
        constructor: function(){


            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

            this.integrityCardWidth = 50;
            this.integrityCardHeight = 70;

            this.equipmentCardWidth = 50;
            this.equipmentCardHeight = 70;
            this.largeEquipmentCardWidth = 240;
            this.largeEquipmentCardHeight = 336;

            this.gunCardWidth = 70;
            this.gunCardHeight = 50;

            this.woundedTokenWidth = 50;
            this.woundedTokenHeight = 50;

            this.dieWidth = 63;
            this.dieHeight = 63;

            this.integritySymbolWidth = 25;
            this.integritySymbolHeight = 27;

            this.EXTRA_DESCRIPTION_TEXT = ''; // this is only set when playing equipment to give specific direction to the player

        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

        setup: function( gamedatas )
        {
            // add active equipment holders to player boards
            for( var i in this.gamedatas.playerLetters )
            {
               var player = this.gamedatas.playerLetters[i];
               var playerId = player['player_id'];
               var playerLetter = player['player_letter'];
               var htmlActiveEquipmentPlacing = "<div id=player_board_active_equipment_"+playerLetter+" class=player_board_active_equipment><div>";
               var htmlBoardDestination = "player_board_"+playerId;

               var htmlHandEquipmentPlacing = "<div id=player_board_hand_equipment_"+playerLetter+" class=player_board_hand_equipment><div>";
               dojo.place( htmlHandEquipmentPlacing, htmlBoardDestination );

               dojo.place( htmlActiveEquipmentPlacing, htmlBoardDestination );
            }

            this.initializeHandEquipment(this.gamedatas.playerLetters);
            this.initializeActiveEquipment(this.gamedatas.playerLetters);
            this.initializeEquipmentList(this.gamedatas.equipmentList);
            this.initializeZombieDice();
            this.initializePreferenceToggles(this.gamedatas.skipEquipmentReactions);

            // Setting up player boards
            var numberOfPlayers = 0;
            for( var player_id in gamedatas.players )
            {
                var playerInstance = gamedatas.players[player_id];
                var score = playerInstance['score'];

                if(score > 0)
                {
                    //this.displayWinnerBorders(player_id);
                    this.giveWinnerMedal(player_id);
                }

                numberOfPlayers++;
            }

            if(numberOfPlayers < 7)
            { // this game has 6 or less players
                var middleRowId = 'board_row_3'; // the HTML ID of the lower middle row
                dojo.destroy(middleRowId); // destroy this row because we only need it for games of 7 or 8 players
            }

            if(gamedatas.zombieExpansion != 2)
            { // we are NOT using the zombies expansion
                var diceHolder = 'die_result_holder'; // the place where infection and zombie dice are rolled
                dojo.destroy(diceHolder); // we don't need this taking up space if we're not using this expansion

                var armDeck = 'arm_deck'; // the place where arms are kept
                dojo.destroy(armDeck); // we don't need this taking up space if we're not using this expansion
            }

            // TODO: Set up your game interface here, according to "gamedatas"

            this.placeCurrentTurnToken(gamedatas.currentPlayerTurn, gamedatas.isClockwise, gamedatas.currentPlayerName, gamedatas.nextPlayerName);

            // put all revealed cards out
            for( var i in this.gamedatas.revealedCards )
            {
                var card = this.gamedatas.revealedCards[i];

                var playerLetter = card['player_position']; // a, b, c, etc.
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

                var cardPosition = card['card_location_arg']; // 1, 2, 3
                var cardType = card['card_type']; // kingpin, agent, honest, crooked
                var playersSeen = card['player_list']; // the list of players who have seen this card

                var isWounded = card['has_wound'];
                var hasBombSymbol = card['has_bomb_symbol'];
                var hasKnifeSymbol = card['has_knife_symbol'];
                var hasSeen3Bombs = card['hasSeen3Bombs'];
                var hasSeen3Knives = card['hasSeen3Knives'];

                var affectedByPlantedEvidence = card['affectedByPlantedEvidence'];
                var affectedByDisguise = card['affectedByDisguise'];
                var affectedBySurveillanceCamera = card['affectedBySurveillanceCamera'];

                this.placeIntegrityCard(playerLetter, cardPosition, 'REVEALED', cardType, rotation, 0, playersSeen, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives); // put a revealed card face-up
            }

            //hiddenCardsIHaveSeen
            for( var i in this.gamedatas.hiddenCardsIHaveSeen )
            {
                var card = this.gamedatas.hiddenCardsIHaveSeen[i];


                var playerLetter = card['player_position']; // a, b, c, etc.
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

                var cardPosition = card['card_location_arg']; // 1, 2, 3
                var cardType = card['card_type']; // kingpin, agent, honest, crooked
                var playersSeen = card['player_list']; // the list of players who have seen this card

                var isWounded = card['has_wound'];
                var hasBombSymbol = card['has_bomb_symbol'];
                var hasKnifeSymbol = card['has_knife_symbol'];
                var hasSeen3Bombs = card['hasSeen3Bombs'];
                var hasSeen3Knives = card['hasSeen3Knives'];

                var affectedByPlantedEvidence = card['affectedByPlantedEvidence'];
                var affectedByDisguise = card['affectedByDisguise'];
                var affectedBySurveillanceCamera = card['affectedBySurveillanceCamera'];

                this.placeIntegrityCard(playerLetter, cardPosition, 'HIDDEN_SEEN', cardType, rotation, 1, playersSeen, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives); // put a hidden card out so i can see what it is but it is clear it is not visible to everyone
            }

            //hiddenCardsIHaveNotSeen
            for( var i in this.gamedatas.hiddenCardsIHaveNotSeen )
            {
                var card = this.gamedatas.hiddenCardsIHaveNotSeen[i];

                var playerLetter = card['player_position']; // a, b, c, etc.
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

                var cardPosition = card['card_location_arg']; // 1, 2, 3
                var cardType = _("Unknown");
                var playersSeen = card['player_list']; // the list of players who have seen this card

                var isWounded = card['has_wound'];

                var affectedByPlantedEvidence = card['affectedByPlantedEvidence'];
                var affectedByDisguise = card['affectedByDisguise'];
                var affectedBySurveillanceCamera = card['affectedBySurveillanceCamera'];

                this.placeIntegrityCard(playerLetter, cardPosition, 'HIDDEN_NOT_SEEN', cardType, rotation, 1, playersSeen, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, 2, 2, false, false); // put a face-down integrity card out
            }

            for( var gun_id in gamedatas.guns )
            {
                var gun = gamedatas.guns[gun_id];
                var gunId = gun['gun_id'];
                var heldByPlayerId = gun['playerIdHeldBy'];
                var heldByLetterOrder = gun['letterPositionHeldBy'];
                var aimedAtPlayerId = gun['playerIdAimedAt'];
                var aimedAtLetterOrder = gun['letterPositionAimedAt'];
                var heldByName = gun['heldByName'];
                var heldByColor = gun['heldByColor'];
                var aimedAtName = gun['aimedAtName'];
                var aimedAtColor = gun['aimedAtColor'];
                var gunType = gun['gun_type']; // gun or arm

                if(heldByName == null)
                  heldByName = ''; // we don't want to display "null"

                if(aimedAtName == null)
                  aimedAtName = ''; // we don't want to display "null"

                var heldByNameHtml = '<span style="color:#' + heldByColor + '"><b>' + heldByName + '</b></span>';
                var aimedAtNameHtml = '<span style="color:#' + aimedAtColor + '"><b>' + aimedAtName + '</b></span>';

                this.placeGun(gunId, gunType, heldByLetterOrder, aimedAtLetterOrder, heldByNameHtml, aimedAtNameHtml);

            }

            for( var gun_id in gamedatas.gunRotations )
            {
                var gun = gamedatas.gunRotations[gun_id];
                var gunId = gun['gun_id'];
                var gunType = gun['gun_type'];
                var rotation = gun['rotation'];
                var isPointingLeft = gun['is_pointing_left']; // 1 if pointing LEFT or 0 if pointing RIGHT

                this.rotateGun(gunId, gunType, rotation, isPointingLeft);
            }

            if(gamedatas.zombieExpansion == 2)
            { // we are using the zombies expansion

                for( var i in gamedatas.infectionTokens )
                {
                    var token = gamedatas.infectionTokens[i];
                    var infectedPlayerLetterOrder = token['infectedPlayerLetterOrder'];
                    var infectedCardPosition = token['cardPosition']; // 1, 2, 3

                    this.placeInfectionToken(infectedPlayerLetterOrder, infectedCardPosition); // put the token on the integrity card
                }

                //this.placeCenterInfectionToken(); // put a token in the center mat
            }

            for( var i in gamedatas.woundedTokens )
            {
                var wound = gamedatas.woundedTokens[i];
                var woundedPlayerLetterOrder = wound['woundedPlayerLetterOrder'];
                var leaderCardPosition = wound['leaderCardPosition']; // 1, 2, 3
                var cardType = wound['cardType']; // agent or kingpin

                this.placeWoundedToken(woundedPlayerLetterOrder, leaderCardPosition, cardType); // put the token on the integrity card
            }

            this.placeCenterWoundedToken(); // put a token in the center mat

            // my equipment cards
            for( var i in gamedatas.myEquipmentCards )
            {
                var myEquipmentCards = gamedatas.myEquipmentCards[i];
                var collectorNumber = myEquipmentCards['card_type_arg'];
                var equipmentCardId = myEquipmentCards['card_id'];
                var equipName = myEquipmentCards['equip_name'];
                var equipEffect = myEquipmentCards['equip_effect'];

                var cardHtmlId = this.placeMyEquipmentCard(equipmentCardId, collectorNumber, null, equipName, equipEffect);
            }

            // opponent equipment cards
            for( var i in gamedatas.opponentEquipmentCards )
            {
                var playerEquipmentCards = gamedatas.opponentEquipmentCards[i];
                var player_id = playerEquipmentCards['player_id'];
                var playerLetterOrder = playerEquipmentCards['playerLetterOrder']; // a, b, c
                var equipmentCardId = playerEquipmentCards['equipmentCardIds']; // the number of cards this player has
                var collectorNumber = playerEquipmentCards['collectorNumber'];
                var equipName = playerEquipmentCards['equipName'];
                var equipEffect = playerEquipmentCards['equipEffect'];
                var state = gamedatas.currentState;

                if(state == "gameEnd")
                {
                    this.revealEquipmentInHand( playerLetterOrder, equipmentCardId, collectorNumber, equipName, equipEffect );
                }
                else
                {
                    this.placeOpponentEquipmentCard(playerLetterOrder, equipmentCardId); // put this card out
                }

            }

            // active SHARED equipment cards
            for( var i in gamedatas.sharedActiveEquimentCards )
            {
                var activeEquipmentCard = gamedatas.sharedActiveEquimentCards[i];
                var collectorNumber = activeEquipmentCard['card_type_arg']; // collector number
                var equipmentId = activeEquipmentCard['card_id']; // equipment ID
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90
                var equipName = activeEquipmentCard['equip_name'];
                var equipEffect = activeEquipmentCard['equip_effect'];

                this.placeActiveCentralEquipmentCard(equipmentId, collectorNumber, rotation, equipName, equipEffect); // place an equipment card in the center of the table
            }

            // active PLAYER equipment cards
            var thisPlayersActiveEquipmentCardIndex = 0;
            for( var i in gamedatas.playerActiveEquipmentCards )
            {
                var activeEquipmentCard = gamedatas.playerActiveEquipmentCards[i];
                var collectorNumber = activeEquipmentCard['collectorNumber']; // collector number
                var equipmentId = activeEquipmentCard['equipmentCardIds']; // equipment ID
                var playerLetter = activeEquipmentCard['playerLetterOrder'];
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90
                var equipName = activeEquipmentCard['equipmentName'];
                var equipEffect = activeEquipmentCard['equipmentEffect'];
                var ownerId = activeEquipmentCard['equipmentOwner']; // the player
                if(ownerId = this.player_id )
                { // this active equipment is in front of THIS player
                    thisPlayersActiveEquipmentCardIndex++;
                }

                this.placeActivePlayerEquipmentCard(equipmentId, collectorNumber, playerLetter, rotation, equipName, equipEffect, thisPlayersActiveEquipmentCardIndex); // place an equipment card in the center of the table
            }

            // eliminate players
            for( var i in gamedatas.eliminatedPlayers )
            {
                var eliminatedPlayer = gamedatas.eliminatedPlayers[i];
                var eliminatedPlayerId = eliminatedPlayer['playerId']; // eliminated player ID
                var letterOfPlayerWhoWasEliminated = eliminatedPlayer['playerLetter']; // eliminated player letter for this player

                this.eliminatePlayer(eliminatedPlayerId, letterOfPlayerWhoWasEliminated); // gray out eliminated players
            }

            // zombie players
            for( var i in gamedatas.zombiePlayers )
            {
                var zombiePlayer = gamedatas.zombiePlayers[i];
                var zombiePlayerId = zombiePlayer['playerId']; // zombie player ID
                var letterOfPlayerWhoWasZombie = zombiePlayer['playerLetter']; // zombie player letter for this player

                this.zombifyPlayer(zombiePlayerId, letterOfPlayerWhoWasZombie); // add green to player area
            }

            this.resetDie('infectionDie', 'infectionDieResult');
            this.resetDie('zombieDie1', 'zombieDie1Result');
            this.resetDie('zombieDie2', 'zombieDie2Result');
            this.resetDie('zombieDie3', 'zombieDie3Result');



            // First Param: css class to target
            // Second Param: type of events
            // Third Param: the method that will be called when the event defined by the second parameter happen
            //this.addEventToClass( "integrity_card", "onclick", "onClickIntegrityCard" );
            //this.addEventToClass( "hand_equipment_card", "onclick", "onClickEquipmentCard" );


            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            // these aren't used but they are here to make sure we have translated versions of these strings to use in the TPL and elsewhere
            this.honestTranslated = _("HONEST");
            this.crookedTranslated = _("CROOKED");
            this.agentTranslated = _("AGENT");
            this.kingpinTranslated = _("KINGPIN");
            this.infectorTranslated = _("INFECTOR");
            this.skipEquipmentTranslated = _("Skip Equipment Reactions");
            this.equipmentReferenceTranslated = _("Equipment Reference");
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {

            switch( stateName )
            {
                case 'chooseEquipmentCardInAnyHand':
                dojo.query( '.cardHighlighted' ).removeClass( 'cardHighlighted' ); // remove all card highlights to reduce confusion
                break;
                case 'executeEquipmentPlay':
                case 'askEndTurnReaction':
                case 'askShootReaction':
                this.EXTRA_DESCRIPTION_TEXT = ''; // in case special instructions were given, clear them out
                break;
                case 'playerTurn':
                this.resetDie('infectionDie', 'infectionDieResult');
                this.resetDie('zombieDie1', 'zombieDie1Result');
                this.resetDie('zombieDie2', 'zombieDie2Result');
                this.resetDie('zombieDie3', 'zombieDie3Result');
                break;
                case 'chooseActiveOrHandEquipmentCard':

                if( this.isCurrentPlayerActive() )
                {
                    var possibleHandEquipmentCardTargets = args.args.handEquipmentCardTargets;

                    const handEquipmentTargets = Object.keys(possibleHandEquipmentCardTargets);
                    for (const handEquipmentTargetKey of handEquipmentTargets)
                    { // go through each card in a player hand

                        var playerIdOfEquipmentCardOwner = possibleHandEquipmentCardTargets[handEquipmentTargetKey]['playerIdOfEquipmentCardOwner'];
                        var playerLetter = this.gamedatas.playerLetters[playerIdOfEquipmentCardOwner].player_letter;
                        var equipmentId = possibleHandEquipmentCardTargets[handEquipmentTargetKey]['equipmentId'];
                        var collectorNumber = possibleHandEquipmentCardTargets[handEquipmentTargetKey]['collectorNumber'];
                        var htmlId = 'player_board_hand_equipment_'+playerLetter+'_item_'+equipmentId;


                        this.highlightEquipmentTargetOption(htmlId);
                        //dojo.addClass( htmlId, 'equipmentTargetHighlighted'); // highlight this possible target
                    }



                    var possibleActiveEquipmentCardTargets = args.args.activeEquipmentCardTargets;

                    const activeEquipmentTargets = Object.keys(possibleActiveEquipmentCardTargets);
                    for (const activeEquipmentTargetKey of activeEquipmentTargets)
                    { // go through each card active in front of a player

                        var playerIdOfEquipmentCardOwner = possibleActiveEquipmentCardTargets[activeEquipmentTargetKey]['playerIdOfEquipmentCardOwner'];
                        var playerLetter = this.gamedatas.playerLetters[playerIdOfEquipmentCardOwner].player_letter;
                        var equipmentId = possibleActiveEquipmentCardTargets[activeEquipmentTargetKey]['equipmentId'];
                        var collectorNumber = possibleActiveEquipmentCardTargets[activeEquipmentTargetKey]['collectorNumber'];
                        var htmlId = 'player_board_active_equipment_'+playerLetter+'_item_'+collectorNumber;


                        this.highlightEquipmentTargetOption(htmlId);
                        //dojo.addClass( htmlId, 'equipmentTargetHighlighted'); // highlight this possible target
                    }
                }

                break;
                case 'chooseIntegrityCardFromPlayer':
                case 'chooseIntegrityCards':
                if( this.isCurrentPlayerActive() )
                {
                    var possibleIntegrityCardTargets = args.args.possibleIntegrityCardTargets;

                    const integrityTargets = Object.keys(possibleIntegrityCardTargets);
                    for (const integrityTargetKey of integrityTargets)
                    { // go through each integrity card target

                        var playerIdOfIntegrityCardOwner = possibleIntegrityCardTargets[integrityTargetKey]['playerIdOfIntegrityCardOwner'];
                        var playerLetter = this.gamedatas.playerLetters[playerIdOfIntegrityCardOwner].player_letter;
                        var cardPosition = possibleIntegrityCardTargets[integrityTargetKey]['cardPosition'];
                        var htmlId = 'player_'+playerLetter+'_integrity_card_'+cardPosition;

                        this.highlightEquipmentTargetOption(htmlId);
                        //dojo.addClass( htmlId, 'equipmentTargetHighlighted'); // highlight this possible target
                    }
                }

                break;

                case 'chooseCardToRevealForArm':
                case 'chooseCardToRevealForEquip':
                if( this.isCurrentPlayerActive() )
                {
                    var possibleArmEquipTargets = args.args.possibleArmEquipTargets;

                    const armEquipTargets = Object.keys(possibleArmEquipTargets);
                    for (const armEquipTargetKey of armEquipTargets)
                    { // go through each integrity card target

                        var playerIdOfIntegrityOwner = possibleArmEquipTargets[armEquipTargetKey]['playerIdOfIntegrityCardOwner'];
                        var playerLetter = this.gamedatas.playerLetters[playerIdOfIntegrityOwner].player_letter;
                        var cardPosition = possibleArmEquipTargets[armEquipTargetKey]['cardPosition'];
                        var htmlId = 'player_'+playerLetter+'_integrity_card_'+cardPosition;

                        this.highlightEquipmentTargetOption(htmlId);
                        //dojo.addClass( htmlId, 'equipmentTargetHighlighted'); // highlight this possible target
                    }
                }




            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {


            switch( stateName )
            {
              case 'chooseIntegrityCards':

                  break;

            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log("onUpdateActionButtons state " + stateName);


            if( this.isCurrentPlayerActive() )
            {
                // update any special instructions that are needed
                if(this.EXTRA_DESCRIPTION_TEXT && this.EXTRA_DESCRIPTION_TEXT != '')
                {
                    this.setPlayerInstructions(this.EXTRA_DESCRIPTION_TEXT);
                }

                switch( stateName )
                {

                  case 'askStartGameReaction':
                      this.addActionButton( 'button_PauseToUseEquipment', _('Use Equipment'), 'onClick_PauseToUseEquipment' );

                      if (false)
                      { // we want to disble this button
                         dojo.addClass( 'button_PauseToUseEquipment', 'disabled'); //disable the button
                      }

                      this.addActionButton( 'button_PassOnUseEquipment', _('Pass'), 'onClick_PassOnEquipmentUse', null, false, 'red' );


                      // get a list of all players, their name, and their letter from my perpsective, if possible
                      //var myInfo = args.myNewGameInfo;
                      //                      console.log(myInfo);
                      //var name = myInfo['name'];

                      //this.addActionButton( 'button_Name', _(name), 'onClick_PauseToUseEquipment' );

                  break;

                  case 'playerTurn':
                        var buttonList = args.buttonList;

                        const buttonKeys = Object.keys(buttonList);
                        for (const buttonKey of buttonKeys)
                        { // go through each player

                            var buttonLabel = buttonList[buttonKey]['buttonLabel'];
                            var isDisabled = buttonList[buttonKey]['isDisabled'];
                            var hoverOverText = buttonList[buttonKey]['hoverOverText']; // hover over text or '' if we don't want a hover over
                            var actionName = buttonList[buttonKey]['actionName']; // shoot, useEquipment
                            var equipmentId = buttonList[buttonKey]['equipmentId'];  // only used for equipment to specify which equipment in case of more than one in hand
                            var makeRed = buttonList[buttonKey]['makeRed'];

                            var buttonId = 'button_' + actionName;
                            if(equipmentId && equipmentId != '')
                            {
                                buttonId += '_' + equipmentId; // add on the equipment ID if this is an equipment we are using
                            }

                            var clickMethod = 'onClick_' + actionName;
                            if(makeRed == true)
                            { // make this button red
                                this.addActionButton( buttonId, _(buttonLabel), clickMethod, null, false, 'red' );
                            }
                            else
                            { // keep this button the default blue
                                this.addActionButton( buttonId, _(buttonLabel), clickMethod );
                            }

                            if (isDisabled == true)
                            { // we want to disble this button
                            	 dojo.addClass( buttonId, 'disabled'); //disable the button
                            }

                            if(hoverOverText && hoverOverText != '')
                            { // there is a hover over text we want to add
                                this.addTooltip( buttonId, _(hoverOverText), '' ); // add a tooltip to explain why it is disabled or how to use it
                            }

                        }


                    break;

                    case 'chooseCardToRevealToReturnEquipmentToHand':
                        this.addActionButton( 'button_pass', _('Pass'), 'onClick_PassOnOption' );
                    break;

                    case 'chooseCardToInfect1':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;
                    case 'chooseCardToInfect2':
                        this.addActionButton( 'button_done', _('Done Selecting'), 'onClick_DoneSelectingButton' );
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;

                    case 'chooseTokenToDiscardForZombieEquip':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;

                    case 'chooseActiveOrHandEquipmentCard':
                    case 'chooseEquipmentCardInAnyHand':
                    case 'chooseCardToInvestigate':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;
                    case 'chooseIntegrityCardFromPlayer':
                        this.addActionButton( 'button_cancel', _('Pass'), 'onClickCancelButton', null, false, 'red' );
                    break;
                    case 'chooseCardToRevealForArm':
                    case 'chooseCardToRevealForEquip':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;

                    case 'chooseLeader':
                      var validLeaders = args.possibleLeaderTargets;

                      const leaders = Object.keys(validLeaders);
                      for (const leaderKey of leaders)
                      { // go through each player
                          var buttonLabel = validLeaders[leaderKey]['button_label'];
                          var buttonValue = validLeaders[leaderKey]['button_value'];
                          this.addActionButton( 'button_' + buttonValue, buttonLabel, 'onClickLeaderButton' ); // the player name does not need to be translated
                      }

                      this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;

                    case 'chooseAnotherPlayer':
                    case 'choosePlayer':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    case 'choosePlayerNoCancel':
                    case 'askAimOutOfTurn':
                    case 'askAim':
                    case 'askAimMustReaim':

                        // get a list of all players, their name, and their letter from my perpsective, if possible
                        var validPlayers = args.validPlayers;

                        const players = Object.keys(validPlayers);
                        for (const playerKey of players)
                        { // go through each player
                            var owner = playerKey;
                            var name = validPlayers[playerKey]['player_name'];
                            var letterPosition = validPlayers[playerKey]['player_letter'];
                            this.addActionButton( 'button_aimAt_' + letterPosition + '_' + owner, name, 'onClickPlayerButton' ); // the player name does not need to be translated
                        }

                    break;

                    case 'askInvestigateReaction':
                    case 'askShootReaction':
                    case 'askEndTurnReaction':
                    case 'askBiteReaction':

                        this.addActionButton( 'button_PauseToUseEquipment', _('Use Equipment'), 'onClick_PauseToUseEquipment' );
                        this.addActionButton( 'button_PassOnUseEquipment', _('Pass'), 'onClick_PassOnEquipmentUse', null, false, 'red' );

                        //this.addTooltip( 'button_useEquipment', _('Pause the timer and consider using equipment.'), '' ); // add a tooltip to explain

                    break;

                    case 'chooseIntegrityCards':
                        this.addActionButton( 'button_done', _('Done Selecting'), 'onClick_DoneSelectingButton' );
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );


                    break;

                    case 'chooseEquipmentToPlayStartGame':
                    case 'chooseEquipmentToPlayReactInvestigate':
                    case 'chooseEquipmentToPlayReactShoot':
                    case 'chooseEquipmentToPlayReactBite':
                    case 'chooseEquipmentToPlayReactEndOfTurn':
                    case 'chooseEquipmentToPlayOnYourTurn':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;

                    case 'discardEquipment':
                      var buttonListDiscard = args.buttonList;

                      const buttonKeysDiscard = Object.keys(buttonListDiscard);
                      for (const buttonKey of buttonKeysDiscard)
                      { // go through each player
                          //console.log("buttonKey:" + buttonKey);

                          var buttonLabel = buttonListDiscard[buttonKey]['buttonLabel'];
                          var isDisabled = buttonListDiscard[buttonKey]['isDisabled'];
                          var hoverOverText = buttonListDiscard[buttonKey]['hoverOverText']; // hover over text or '' if we don't want a hover over
                          var actionName = buttonListDiscard[buttonKey]['actionName']; // shoot, useEquipment
                          var equipmentId = buttonListDiscard[buttonKey]['equipmentId'];  // only used for equipment to specify which equipment in case of more than one in hand
                          var makeRed = buttonListDiscard[buttonKey]['makeRed'];

                          var buttonId = 'button_' + actionName;
                          if(equipmentId && equipmentId != '')
                          {
                              buttonId += '_' + equipmentId; // add on the equipment ID if this is an equipment we are using
                          }

                          var clickMethod = 'onClick_' + actionName;
                          if(makeRed == true)
                          { // make this button red
                              this.addActionButton( buttonId, _(buttonLabel), clickMethod, null, false, 'red' );
                          }
                          else
                          { // keep this button the default blue
                              this.addActionButton( buttonId, _(buttonLabel), clickMethod );
                          }

                          if (isDisabled == true)
                          { // we want to disble this button
                             dojo.addClass( buttonId, 'disabled'); //disable the button
                          }

                          if(hoverOverText && hoverOverText != '')
                          { // there is a hover over text we want to add
                              this.addTooltip( buttonId, _(hoverOverText), '' ); // add a tooltip to explain why it is disabled or how to use it
                          }

                      }

                    break;

                }
            }
            else
            { // they are NOT the active player
                this.EXTRA_DESCRIPTION_TEXT = ''; // don't show special instructions to non-active players
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */

        /** Override this function to inject html into log items. This is a built-in BGA method.  */
        /* @Override */
        format_string_recursive : function(log, args) {
            try {
                if (log && args && !args.processed) {
                    args.processed = true;


                    // list of special keys we want to replace with images
                    var keys = ['equipment_name','player_name_2','equip_name','target_player_name','target_name'];

                    //console.log("Looking through keys:" + keys);
                    for ( var i in keys) {
                        var key = keys[i];
                        args[key] = this.getTokenDiv(key, args);

                    }
                }
            } catch (e) {
                console.error(log,args,"Exception thrown", e.stack);
            }
            return this.inherited(arguments);
        },

        getTokenDiv : function(key, args)
        {
            var token_id = args[key];
            if(!token_id)
              return '';
            var logid = "log" + (this.globalid++) + "_" + token_id.substring(0,3);

            var playerColor = this.gamedatas.player_colors[token_id];
            if(playerColor)
            { // player name
                return "<span id="+logid+" style=\"color:#" + playerColor + ";font-weight:bolder;\">" + token_id + "</span>";
            }
            else
            { // equipment card name
                var delay = 0; // any delay before it appears
                this.addTooltipHtml( logid, this.getEquipmentEffectByName(token_id), delay );
                this.addTooltipHtml( "logNaN_" + token_id.substring(0,3), this.getEquipmentEffectByName(token_id), delay ); // workaround for bug where globalid++ returns NaN

                return "<span id="+logid+" class=\"message_log_equipment\">" + this.clienttranslate_string(token_id) + "</span>";
            }

            return "'" + this.clienttranslate_string(token_id) + "'";
       },

       highlightComponent: function(htmlIdOfComponent)
       {
          if (this.prefs[100].value == 1)
          { // the user does want components to be highlighted
              if(document.getElementById(htmlIdOfComponent))
              { // this component exists
                  dojo.addClass( htmlIdOfComponent, 'cardHighlighted');
              }
          }
       },

       highlightEquipmentTargetOption: function(htmlIdOfComponent)
       {
          if (this.prefs[101].value == 1)
          { // the user does want components to be highlighted
              if(document.getElementById(htmlIdOfComponent))
              { // this component exists
                  dojo.addClass( htmlIdOfComponent, 'equipmentTargetHighlighted');
              }
          }
       },

       selectComponent: function(htmlIdOfComponent)
       {
          if(document.getElementById(htmlIdOfComponent))
          { // this component exists
              dojo.addClass( htmlIdOfComponent, 'cardSelected');
          }
       },

       rollDie: function(dieNodeId, resultNodeId, resultInt, animation, prefix)
       {
          //replace all of this kind with dojo.byId() at BGA Studio
          //var dice=document.getElementById("infectionDie");
          //var diceresult=document.getElementById("diceresult");

          //dice.className ="rolled";
          //diceresult.className ="num"+result;
dojo.style( dieNodeId, 'display', 'block' ); // show the die
           dojo.addClass( dieNodeId, animation ); // start the animation of rolling the die
           dojo.addClass( resultNodeId, prefix+resultInt );
       },

       resetDie: function(dieNodeId, resultNodeId)
       {
         if(document.getElementById(dieNodeId))
         {
            dojo.style( dieNodeId, 'display', 'none' ); // hide the die

            dojo.removeClass(dieNodeId, 'rolled');
            dojo.removeClass(dieNodeId, 'zom1Rolled');
            dojo.removeClass(dieNodeId, 'zom2Rolled');
            dojo.removeClass(dieNodeId, 'zom3Rolled');
         }
         //dojo.style( 'infectionDie', 'left', '-200px' );
           //var dice=document.getElementById("infectionDie");
           //var diceresult=document.getElementById("diceresult");

           //dice.className ="";
           //diceresult.className ="";


           if(document.getElementById(resultNodeId))
           {
              dojo.removeClass(resultNodeId, 'num1');
              dojo.removeClass(resultNodeId, 'num2');
              dojo.removeClass(resultNodeId, 'num3');
              dojo.removeClass(resultNodeId, 'num4');
              dojo.removeClass(resultNodeId, 'num5');
              dojo.removeClass(resultNodeId, 'num6');

              dojo.removeClass(resultNodeId, 'zom1Num1');
              dojo.removeClass(resultNodeId, 'zom1Num2');
              dojo.removeClass(resultNodeId, 'zom1Num3');
              dojo.removeClass(resultNodeId, 'zom1Num4');
              dojo.removeClass(resultNodeId, 'zom1Num5');
              dojo.removeClass(resultNodeId, 'zom1Num6');

              dojo.removeClass(resultNodeId, 'zom2Num1');
              dojo.removeClass(resultNodeId, 'zom2Num2');
              dojo.removeClass(resultNodeId, 'zom2Num3');
              dojo.removeClass(resultNodeId, 'zom2Num4');
              dojo.removeClass(resultNodeId, 'zom2Num5');
              dojo.removeClass(resultNodeId, 'zom2Num6');

              dojo.removeClass(resultNodeId, 'zom3Num1');
              dojo.removeClass(resultNodeId, 'zom3Num2');
              dojo.removeClass(resultNodeId, 'zom3Num3');
              dojo.removeClass(resultNodeId, 'zom3Num4');
              dojo.removeClass(resultNodeId, 'zom3Num5');
              dojo.removeClass(resultNodeId, 'zom3Num6');
            }

            if(document.getElementById('integrity_token_bite'))
            {
                dojo.destroy('integrity_token_bite'); // destroy any tokens that were used during the bite animation
            }
       },

       getTransformProperty: function(node)
       {
          if(!node.style.transform)
          {

          }

          return node.style.transform;
       },

       setPlayerInstructions: function(text) {

            var main = $('pagemaintitletext');
            main.innerHTML = text; // make sure text is translated before it is sent to this function
       },

       getEquipmentEffectByName : function(key)
       {
            return this.gamedatas.equipment_effects[key].effect; // get name for the key, from static table for example
       },

       initializeZombieDice : function()
       {
            this.tableDice = new ebg.stock(); // create a new set of cards for the dice
            this.tableDice.create( this, $('dice'), this.dieWidth, this.dieHeight ); // specify where it goes and how wide/tall it should be
            this.tableDice.image_items_per_row = 6; // specify that there are 13 images per row in the CSS sprite image

            // define all the dice that could be in the dice area
            this.tableDice.addItemType( 0, 1, g_gamethemeurl+'img/zombie_dice.jpg', 0 ); // infection die | infection token face
            this.tableDice.addItemType( 1, 2, g_gamethemeurl+'img/zombie_dice.jpg', 1 ); // infection die | infection token face
            this.tableDice.addItemType( 2, 3, g_gamethemeurl+'img/zombie_dice.jpg', 2 ); // infection die | blank face
            this.tableDice.addItemType( 3, 4, g_gamethemeurl+'img/zombie_dice.jpg', 3 ); // infection die | blank face
            this.tableDice.addItemType( 4, 5, g_gamethemeurl+'img/zombie_dice.jpg', 4 ); // infection die | blank face
            this.tableDice.addItemType( 5, 6, g_gamethemeurl+'img/zombie_dice.jpg', 5 ); // infection die | blank face

            this.tableDice.addItemType( 6, 7, g_gamethemeurl+'img/zombie_dice.jpg', 6 ); // zombie die | zombie face
            this.tableDice.addItemType( 7, 8, g_gamethemeurl+'img/zombie_dice.jpg', 7 ); // zombie die | re-aim arms face
            this.tableDice.addItemType( 8, 9, g_gamethemeurl+'img/zombie_dice.jpg', 8 ); // zombie die | re-aim arms face
            this.tableDice.addItemType( 9, 10, g_gamethemeurl+'img/zombie_dice.jpg', 9 ); // zombie die | re-aim gun face
            this.tableDice.addItemType( 10, 11, g_gamethemeurl+'img/zombie_dice.jpg', 10 ); // zombie die | infection token face
            this.tableDice.addItemType( 11, 12, g_gamethemeurl+'img/zombie_dice.jpg', 11 ); // zombie die | infection token face


       },

       initializeEquipmentList : function(allEquipment)
       {
         this.equipmentList = new ebg.stock(); // create a new set of cards for the player's equipment
         this.equipmentList.create( this, $('equipment_list'), this.equipmentCardWidth, this.equipmentCardHeight );
         this.equipmentList.image_items_per_row = 6;
         this.equipmentList.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
         this.equipmentList.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
         this.equipmentList.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
         this.equipmentList.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
         this.equipmentList.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
         this.equipmentList.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
         this.equipmentList.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
         this.equipmentList.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
         this.equipmentList.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
         this.equipmentList.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
         this.equipmentList.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
         this.equipmentList.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
         this.equipmentList.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
         this.equipmentList.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
         this.equipmentList.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
         this.equipmentList.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
         this.equipmentList.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
         this.equipmentList.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
         this.equipmentList.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
         this.equipmentList.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

         this.equipmentList.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
         this.equipmentList.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
         this.equipmentList.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
         this.equipmentList.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
         this.equipmentList.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
         this.equipmentList.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
         this.equipmentList.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
         this.equipmentList.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
         this.equipmentList.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

         this.equipmentList.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
         this.equipmentList.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
         this.equipmentList.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
         this.equipmentList.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
         this.equipmentList.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster

         this.resetEquipmentList(allEquipment);

         /*
         this.equipmentList.addToStockWithId( 2, 2 );
         this.equipmentList.addToStockWithId( 8, 8 );
         this.equipmentList.addToStockWithId( 12, 12 );
         this.equipmentList.addToStockWithId( 15, 15 );
         this.equipmentList.addToStockWithId( 16, 16 );
         this.equipmentList.addToStockWithId( 44, 44 );
         this.equipmentList.addToStockWithId( 11, 11 );
         this.equipmentList.addToStockWithId( 4, 4 );
         this.equipmentList.addToStockWithId( 35, 35 );
         this.equipmentList.addToStockWithId( 14, 14 );
         this.equipmentList.addToStockWithId( 30, 301 ); // defibrillator
         this.equipmentList.addToStockWithId( 30, 302 ); // defibrillator
         this.equipmentList.addToStockWithId( 1, 1 );
         this.equipmentList.addToStockWithId( 45, 45 );
         this.equipmentList.addToStockWithId( 9, 9 );
         this.equipmentList.addToStockWithId( 13, 13 );
         this.equipmentList.addToStockWithId( 7, 7 );
         this.equipmentList.addToStockWithId( 6, 6 );

         if(gamedatas.zombieExpansion == 2)
         { // we are using the zombies expansion
             this.equipmentList.addToStockWithId( 60, 60 );
             this.equipmentList.addToStockWithId( 61, 61 );
             this.equipmentList.addToStockWithId( 62, 62 );
             this.equipmentList.addToStockWithId( 63, 63 );
             this.equipmentList.addToStockWithId( 64, 64 );
             this.equipmentList.addToStockWithId( 65, 65 );
             this.equipmentList.addToStockWithId( 66, 66 );
             this.equipmentList.addToStockWithId( 67, 67 );
             this.equipmentList.addToStockWithId( 68, 68 );
         }
         */
       },

       resetEquipmentList: function (allEquipment)
       {
           var defibrillatorIndex = 301;
           for( var i in allEquipment )
           { // go through the cards
               var equipment = allEquipment[i];

               var collectorNumber = equipment['card_type_arg']; // collector number
               var stockId = equipment['card_type_arg']; // collector number
               var equipmentId = equipment['card_id']; // equipment ID
               var location = equipment['card_location']; // location
               var locationArg = equipment['card_location_arg']; // holder
               var equipName = equipment['equip_name'];
               var equipEffect = equipment['equip_effect'];
               var discardedBy = equipment['discarded_by'];

               var equipment_played_on_turn = equipment['equipment_played_on_turn'];
               var equipmentHtmlId = 'equipment_list_item_'+collectorNumber;

               if(collectorNumber == 3)
               { // defibrillator (2 copies of the card)
                    stockId = defibrillatorIndex;
                    equipmentHtmlId = 'equipment_list_item_'+defibrillatorIndex;

                    defibrillatorIndex++; // add one to index for the second copy
               }

               this.equipmentList.addToStockWithId( collectorNumber, stockId );

               this.addLargeEquipmentTooltip(equipmentHtmlId, collectorNumber, equipName, equipEffect); // add a hoverover tooltip with a bigger version of the card

               if( locationArg == this.player_id ||
                 location == 'active' ||
                 (location == 'discard' && equipment_played_on_turn != '') ||
                  discardedBy == this.player_id )
               { // this card is in this player's hand, they previously discarded it, it's active, or it was played publicly

                  if(document.getElementById(equipmentHtmlId) && this.gamedatas.playerLetters[this.player_id])
                  { // equipment HTML node exists and they are not a spectator
                      dojo.addClass( equipmentHtmlId, 'used_equipment'); // dim the card
                  }
               }
               else
               { // this player has not seen this card

                 if(document.getElementById(equipmentHtmlId) && this.gamedatas.playerLetters[this.player_id])
                 { // equipment HTML node exists and they are not a spectator
                     dojo.removeClass( equipmentHtmlId, 'used_equipment'); // un-dim the card
                 }
               }

               if(document.getElementById(equipmentHtmlId))
               {
                  dojo.connect( $(equipmentHtmlId), 'onclick', this, 'onClickReferenceEquipmentCard' );
               }
           }

       },

       initializeOneActiveEquipment : function(playerLetter)
       {
           switch(playerLetter)
           {
               case 'a':
               this.activePlayerEquipmentA = new ebg.stock();
               this.activePlayerEquipmentA.create( this, $('player_board_active_equipment_a'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentA.image_items_per_row = 6;
               this.activePlayerEquipmentA.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentA.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentA.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentA.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentA.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentA.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentA.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentA.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentA.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentA.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentA.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentA.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentA.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentA.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentA.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentA.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentA.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentA.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentA.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentA.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentA.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentA.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentA.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentA.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentA.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentA.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentA.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentA.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentA.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentA.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentA.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentA.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentA.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentA.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
               case 'b':
               this.activePlayerEquipmentB = new ebg.stock();
               this.activePlayerEquipmentB.create( this, $('player_board_active_equipment_b'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentB.image_items_per_row = 6;
               this.activePlayerEquipmentB.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentB.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentB.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentB.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentB.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentB.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentB.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentB.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentB.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentB.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentB.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentB.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentB.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentB.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentB.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentB.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentB.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentB.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentB.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentB.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentB.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentB.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentB.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentB.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentB.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentB.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentB.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentB.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentB.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentB.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentB.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentB.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentB.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentB.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
               case 'c':
               this.activePlayerEquipmentC = new ebg.stock();
               this.activePlayerEquipmentC.create( this, $('player_board_active_equipment_c'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentC.image_items_per_row = 6;
               this.activePlayerEquipmentC.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentC.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentC.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentC.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentC.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentC.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentC.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentC.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentC.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentC.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentC.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentC.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentC.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentC.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentC.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentC.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentC.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentC.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentC.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentC.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentC.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentC.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentC.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentC.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentC.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentC.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentC.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentC.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentC.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentC.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentC.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentC.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentC.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentC.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
               case 'd':
               this.activePlayerEquipmentD = new ebg.stock();
               this.activePlayerEquipmentD.create( this, $('player_board_active_equipment_d'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentD.image_items_per_row = 6;
               this.activePlayerEquipmentD.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentD.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentD.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentD.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentD.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentD.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentD.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentD.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentD.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentD.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentD.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentD.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentD.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentD.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentD.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentD.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentD.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentD.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentD.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentD.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentD.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentD.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentD.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentD.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentD.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentD.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentD.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentD.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentD.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentD.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentD.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentD.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentD.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentD.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
               case 'e':
               this.activePlayerEquipmentE = new ebg.stock();
               this.activePlayerEquipmentE.create( this, $('player_board_active_equipment_e'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentE.image_items_per_row = 6;
               this.activePlayerEquipmentE.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentE.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentE.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentE.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentE.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentE.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentE.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentE.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentE.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentE.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentE.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentE.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentE.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentE.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentE.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentE.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentE.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentE.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentE.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentE.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentE.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentE.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentE.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentE.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentE.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentE.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentE.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentE.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentE.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentE.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentE.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentE.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentE.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentE.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
               case 'f':
               this.activePlayerEquipmentF = new ebg.stock();
               this.activePlayerEquipmentF.create( this, $('player_board_active_equipment_f'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentF.image_items_per_row = 6;
               this.activePlayerEquipmentF.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentF.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentF.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentF.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentF.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentF.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentF.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentF.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentF.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentF.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentF.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentF.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentF.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentF.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentF.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentF.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentF.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentF.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentF.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentF.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentF.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentF.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentF.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentF.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentF.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentF.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentF.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentF.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentF.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentF.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentF.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentF.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentF.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentF.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
               case 'g':
               this.activePlayerEquipmentG = new ebg.stock();
               this.activePlayerEquipmentG.create( this, $('player_board_active_equipment_g'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentG.image_items_per_row = 6;
               this.activePlayerEquipmentG.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentG.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentG.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentG.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentG.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentG.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentG.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentG.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentG.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentG.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentG.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentG.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentG.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentG.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentG.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentG.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentG.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentG.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentG.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentG.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentG.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentG.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentG.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentG.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentG.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentG.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentG.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentG.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentG.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentG.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentG.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentG.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentG.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentG.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
               case 'h':
               this.activePlayerEquipmentH = new ebg.stock();
               this.activePlayerEquipmentH.create( this, $('player_board_active_equipment_h'), this.equipmentCardWidth, this.equipmentCardHeight );
               this.activePlayerEquipmentH.image_items_per_row = 6;

               this.activePlayerEquipmentH.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 15 ); // disguise
               this.activePlayerEquipmentH.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 6 ); // coffee
               this.activePlayerEquipmentH.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 8 ); // planted evidence
               this.activePlayerEquipmentH.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 2 ); // smoke grenade
               this.activePlayerEquipmentH.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 1 ); // truth serum
               this.activePlayerEquipmentH.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 7 ); // wiretap
               this.activePlayerEquipmentH.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 19 ); // riot shield
               this.activePlayerEquipmentH.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 18 ); // restraining order
               this.activePlayerEquipmentH.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 14 ); // mobile detonator
               this.activePlayerEquipmentH.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 12 ); // evidence bag
               this.activePlayerEquipmentH.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 13 ); // med kit
               this.activePlayerEquipmentH.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 20 ); // taser
               this.activePlayerEquipmentH.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 17 ); // Defibrillator
               this.activePlayerEquipmentH.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 16 ); // Blackmail
               this.activePlayerEquipmentH.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 11 ); // Walkie Talkie
               this.activePlayerEquipmentH.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 10 ); // Polygraph
               this.activePlayerEquipmentH.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 9 ); // Surveillance Camera
               this.activePlayerEquipmentH.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 5 ); // Metal Detector
               this.activePlayerEquipmentH.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 4 ); // Deliriant
               this.activePlayerEquipmentH.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 3 ); // K-9 Unit

               this.activePlayerEquipmentH.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 24 ); // Crossbow
               this.activePlayerEquipmentH.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 27 ); // Transfusion Tube
               this.activePlayerEquipmentH.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 29 ); // Zombie Serum
               this.activePlayerEquipmentH.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 25 ); // Flamethrower
               this.activePlayerEquipmentH.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 23 ); // Chainsaw
               this.activePlayerEquipmentH.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 28 ); // Zombie Mask
               this.activePlayerEquipmentH.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 26 ); // Machete
               this.activePlayerEquipmentH.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 21 ); // Weapon Crate
               this.activePlayerEquipmentH.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 22 ); // Alarm Clock

               this.activePlayerEquipmentH.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 30 ); // Classified Orders
               this.activePlayerEquipmentH.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 31 ); // Fake ID
               this.activePlayerEquipmentH.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 32 ); // Fingerprint Kit
               this.activePlayerEquipmentH.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 33 ); // Grenade
               this.activePlayerEquipmentH.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 34 ); // Holster
               break;
           }
       },

       initializeActiveEquipment : function(playerLetters)
       {
             for( var i in playerLetters )
             { // go through the cards we want to draw
                 var letter = playerLetters[i];
                 var playerLetter = letter['player_letter'];
                 this.initializeOneActiveEquipment(playerLetter);
             }
        },

        // For handPlayerEquipmentX, we just need to reserve a spot for each equipment card in the deck.
        initializeOnePlayerHand : function(playerLetter)
        {
            switch(playerLetter)
            {
                case 'a':
                this.myHandEquipment = new ebg.stock();
                this.myHandEquipment.create( this, $('player_a_equipment_hand_holder'), this.largeEquipmentCardWidth, this.largeEquipmentCardHeight );
                this.myHandEquipment.image_items_per_row = 6;
                this.myHandEquipment.addItemType( 30, 30, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 15 ); // disguise
                this.myHandEquipment.addItemType( 2, 2, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 6 ); // coffee
                this.myHandEquipment.addItemType( 8, 8, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 8 ); // planted evidence
                this.myHandEquipment.addItemType( 12, 12, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 2 ); // smoke grenade
                this.myHandEquipment.addItemType( 15, 15, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 1 ); // truth serum
                this.myHandEquipment.addItemType( 16, 16, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 7 ); // wiretap
                this.myHandEquipment.addItemType( 44, 44, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 19 ); // riot shield
                this.myHandEquipment.addItemType( 11, 11, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 18 ); // restraining order
                this.myHandEquipment.addItemType( 37, 37, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 14 ); // mobile detonator
                this.myHandEquipment.addItemType( 4, 4, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 12 ); // evidence bag
                this.myHandEquipment.addItemType( 35, 35, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 13 ); // med kit
                this.myHandEquipment.addItemType( 14, 14, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 20 ); // taser
                this.myHandEquipment.addItemType( 3, 3, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 17 ); // Defibrillator
                this.myHandEquipment.addItemType( 1, 1, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 16 ); // Blackmail
                this.myHandEquipment.addItemType( 45, 45, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 11 ); // Walkie Talkie
                this.myHandEquipment.addItemType( 9, 9, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 10 ); // Polygraph
                this.myHandEquipment.addItemType( 13, 13, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 9 ); // Surveillance Camera
                this.myHandEquipment.addItemType( 7, 7, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 5 ); // Metal Detector
                this.myHandEquipment.addItemType( 17, 17, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 4 ); // Deliriant
                this.myHandEquipment.addItemType( 6, 6, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 3 ); // K-9 Unit

                this.myHandEquipment.addItemType( 60, 60, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 24 ); // Crossbow
                this.myHandEquipment.addItemType( 61, 61, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 27 ); // Transfusion Tube
                this.myHandEquipment.addItemType( 62, 62, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 29 ); // Zombie Serum
                this.myHandEquipment.addItemType( 63, 63, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 25 ); // Flamethrower
                this.myHandEquipment.addItemType( 64, 64, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 23 ); // Chainsaw
                this.myHandEquipment.addItemType( 65, 65, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 28 ); // Zombie Mask
                this.myHandEquipment.addItemType( 66, 66, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 26 ); // Machete
                this.myHandEquipment.addItemType( 67, 67, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 21 ); // Weapon Crate
                this.myHandEquipment.addItemType( 68, 68, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 22 ); // Alarm Clock

                this.myHandEquipment.addItemType( 18, 18, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 30 ); // Classified Orders
                this.myHandEquipment.addItemType( 19, 19, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 31 ); // Fake ID
                this.myHandEquipment.addItemType( 20, 20, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 32 ); // Fingerprint Kit
                this.myHandEquipment.addItemType( 21, 21, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 33 ); // Grenade
                this.myHandEquipment.addItemType( 22, 22, g_gamethemeurl+'img/equipment_card_sprite_240w.jpg', 34 ); // Holster


                this.handPlayerEquipmentA = new ebg.stock();
                this.handPlayerEquipmentA.create( this, $('player_board_hand_equipment_a'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentA.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentA.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                this.handPlayerEquipmentA.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                this.handPlayerEquipmentA.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentA.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                break;
                case 'b':
                this.handPlayerEquipmentB = new ebg.stock();
                this.handPlayerEquipmentB.create( this, $('player_board_hand_equipment_b'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentB.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentB.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                this.handPlayerEquipmentB.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentB.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                this.handPlayerEquipmentB.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 ); // Classified Orders
                this.handPlayerEquipmentB.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 ); // Fake ID
                this.handPlayerEquipmentB.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 ); // Fingerprint Kit
                this.handPlayerEquipmentB.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 ); // Grenade
                this.handPlayerEquipmentB.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 ); // Holster
                break;
                case 'c':
                this.handPlayerEquipmentC = new ebg.stock();
                this.handPlayerEquipmentC.create( this, $('player_board_hand_equipment_c'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentC.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentC.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 9 more spots for Zombies equipment
                this.handPlayerEquipmentC.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 5 more spots for Bombers & Traitors equipment
                this.handPlayerEquipmentC.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentC.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                break;

                case 'd':
                this.handPlayerEquipmentD = new ebg.stock();
                this.handPlayerEquipmentD.create( this, $('player_board_hand_equipment_d'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentD.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentD.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 ); // card back
                this.handPlayerEquipmentD.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 9 more spots for Zombies equipment
                this.handPlayerEquipmentD.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 5 more spots for Bombers & Traitors equipment
                this.handPlayerEquipmentD.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentD.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                break;

                case 'e':
                this.handPlayerEquipmentE = new ebg.stock();
                this.handPlayerEquipmentE.create( this, $('player_board_hand_equipment_e'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentE.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentE.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 9 more spots for Zombies equipment
                this.handPlayerEquipmentE.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 5 more spots for Bombers & Traitors equipment
                this.handPlayerEquipmentE.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentE.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                break;

                case 'f':
                this.handPlayerEquipmentF = new ebg.stock();
                this.handPlayerEquipmentF.create( this, $('player_board_hand_equipment_f'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentF.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentF.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 9 more spots for Zombies equipment
                this.handPlayerEquipmentF.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 5 more spots for Bombers & Traitors equipment
                this.handPlayerEquipmentF.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentF.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                break;

                case 'g':
                this.handPlayerEquipmentG = new ebg.stock();
                this.handPlayerEquipmentG.create( this, $('player_board_hand_equipment_g'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentG.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentG.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 9 more spots for Zombies equipment
                this.handPlayerEquipmentG.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 5 more spots for Bombers & Traitors equipment
                this.handPlayerEquipmentG.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentG.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                break;

                case 'h':
                this.handPlayerEquipmentH = new ebg.stock();
                this.handPlayerEquipmentH.create( this, $('player_board_hand_equipment_h'), this.equipmentCardWidth, this.equipmentCardHeight );
                this.handPlayerEquipmentH.image_items_per_row = 6;

                // RESERVE A SPOT FOR EACH EQUIPMENT CARD IN THE DECK
                this.handPlayerEquipmentH.addItemType( 1, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 2, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 3, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 4, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 5, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 6, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 7, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 8, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 9, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 10, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 11, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 12, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 13, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 14, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 15, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 16, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 17, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 18, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 19, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 9 more spots for Zombies equipment
                this.handPlayerEquipmentH.addItemType( 20, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 21, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 22, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 23, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 24, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 25, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 26, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 27, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 28, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );

                // add 5 more spots for Bombers & Traitors equipment
                this.handPlayerEquipmentH.addItemType( 29, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 30, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 31, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 32, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                this.handPlayerEquipmentH.addItemType( 33, 0, g_gamethemeurl+'img/equipment_card_sprite_50w.jpg', 0 );
                break;
            }
        },

        initializePreferenceToggles : function(skipEquipmentReactions)
        {
            // insert toggle into right-side-first-part
            //var htmlIdOfDestination = "maintitlebar_content";
            var htmlIdOfDestination = "player_board_"+this.player_id;

            var isChecked = 'checked';
            if(!skipEquipmentReactions)
            {
                isChecked = ''; // do NOT skip equipment reactions
            }

            if(this.gamedatas.playerLetters[this.player_id])
            { // not a spectator

                var equipmentReactionsLabel = _('Skip Equipment Reactions');

                dojo.place(
                        this.format_block( 'jstpl_toggle', {
                          isChecked: isChecked,
                          label: equipmentReactionsLabel
                        } ), htmlIdOfDestination );

                        //var htmlHandEquipmentPlacing = "<div id=player_board_hand_equipment_"+playerLetter+" class=player_board_hand_equipment><div>";
                        //dojo.place( htmlHandEquipmentPlacing, htmlBoardDestination );

                var tooltipHtml = '<div>' + _('If this is enabled, you will NOT be asked if you want to respond to actions using Equipment.') + '</div>';
                this.addTooltipHtml( 'toggle_container', tooltipHtml, 0 );

                dojo.connect( $(toggle_EquipmentReactions), 'onclick', this, 'clickToggle_EquipmentReactions' ); // re-add the onclick connection
            }
        },

        initializeHandEquipment : function(playerLetters)
        {
            for( var i in playerLetters )
            { // go through the cards we want to draw
                var letter = playerLetters[i];
                var playerLetter = letter['player_letter'];
                this.initializeOnePlayerHand(playerLetter);
            }
         },

        // PLAY card to the center area FROM HAND.
        playEquipmentCardFromHand: function(equipmentId, collectorNumber, playerLetter, equipName, equipEffect, playerIdPlaying)
        {
            this.discardEquipmentFromHand(playerLetter, equipmentId, true, playerIdPlaying, collectorNumber); // remove from player A hand and all player side board stocks
            var htmlIdOfCard = this.placeActiveCentralEquipmentCard(equipmentId, collectorNumber, 0, equipName, equipEffect); // place on active_equipment_center_holder
            var htmlOfHandLocation = "player_board_hand_equipment_"+playerLetter+"_item_"+equipmentId;
            if(document.getElementById(htmlIdOfCard))
            { // the new card exists
                if(document.getElementById(htmlOfHandLocation))
                { // the card exists in the player's hand
                    this.placeOnObject( htmlIdOfCard, htmlOfHandLocation ); // start from the hand location
                }

                // slide to active_equipment_center_holder
                var destination = 'active_equipment_center_holder';
                var anim1 = this.slideToObject(htmlIdOfCard, destination, 1000, 250);
                dojo.connect(anim1, 'onEnd', function(node)
                { // do the following after the animation ends

                  this.highlightComponent(htmlIdOfCard); // highlight the card just moved
                });
                anim1.play();
            }
        },

        destroyEquipmentDiscard: function(collectorNumber)
        {
            var equipmentHtmlId = "center_active_equipment_" + collectorNumber; // the HTML ID of the card

            if(document.getElementById(equipmentHtmlId))
            { // the card exists
                dojo.destroy(equipmentHtmlId);
            }
        },

        // PLACE active card in center area.
        placeActiveCentralEquipmentCard: function(equipmentId, collectorNumber, rotation, equipName, equipEffect)
        {
            var htmlIdCenterHolder = "active_equipment_center_holder"; // the HTML ID of the container for the card
            var equipmentHtmlId = "center_active_equipment_" + collectorNumber; // the HTML ID of the card

            dojo.place(
                    this.format_block( 'jstpl_activeCenterEquipment', {
                        x: this.equipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                        y: this.equipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                        collectorNumber: collectorNumber
                    } ), htmlIdCenterHolder );

            this.addLargeEquipmentTooltip(equipmentHtmlId, collectorNumber, equipName, equipEffect); // add a hoverover tooltip with a bigger version of the card

            dojo.connect( $(equipmentHtmlId), 'onclick', this, 'onClickReferenceEquipmentCard' ); // re-add the onclick connection
            return equipmentHtmlId;
        },

        playActivePlayerEquipmentCardFromHand: function(equipmentId, collectorNumber, playerLetterPlaying, playerLetterReceiving, rotation, equipName, equipEffect, numberOfActiveEquipmentReceiverHas, playerIdPlaying)
        {
            var equipmentHtmlId = "player_" + playerLetterPlaying + "_hand_equipment_" + equipmentId; // the HTML ID of the card we want to move (it's the same for player A and other players)
            var targetActiveEquipmentHolderHtmlId = "player_" + playerLetterReceiving + "_first_equipment_active_holder"; // use the player position letter to move the card in the equipment player's hand to the target player's active equipment spot

            var playerBoardId = 'player_board_active_equipment_'+playerLetterReceiving;
            var placedId = this.placeActivePlayerEquipmentCard(equipmentId, collectorNumber, playerLetterReceiving, rotation, equipName, equipEffect, numberOfActiveEquipmentReceiverHas); // add to receiver player board
            this.highlightComponent(placedId); // highlight the card just investigated

            this.discardEquipmentFromHand(playerLetterPlaying, equipmentId, true, playerIdPlaying, collectorNumber); // remove from giver player board

            if(collectorNumber == 21)
            { // grenade is being played or tossed

                // destroy any copies of this in other players' hands
                for( var i in this.gamedatas.playerLetters )
                {
                   var player = this.gamedatas.playerLetters[i];
                   var playerId = player['player_id'];
                   var playerLetter = player['player_letter'];

                   if(playerLetter != playerLetterReceiving)
                   { // this is a different player than the one playing the card
                        var equipmentInHandHtmlId = "player_board_active_equipment_"+playerLetter+"_item_21";

                       if(document.getElementById(equipmentInHandHtmlId))
                       { // this card exists on the player's board
                           dojo.destroy(equipmentInHandHtmlId); // destroy it
                       }
                   }
                }
            }

        },

        placeActivePlayerEquipmentCard: function(equipmentId, collectorNumber, playerLetter, rotation, equipName, equipEffect, numberOfActiveEquipmentPlayerHas)
        {
            this.addActivePlayerEquipmentToStock(playerLetter, collectorNumber, equipName, equipEffect);
            var equipmentPlayerBoardId = 'player_board_active_equipment_'+playerLetter+'_item_'+collectorNumber; // the id of the equipment on the player board
            //this.disconnect( $(equipmentPlayerBoardId), 'onclick'); // disconnect any previously registered onclicks for this
            //dojo.connect( $(equipmentPlayerBoardId), 'onclick', this, 'onClickPlayerBoardEquipmentCard' ); // connect it to the click where it
            dojo.addClass( equipmentPlayerBoardId, 'modified_glow'); // add yellow border (glow) around it so we know this card is being modified by something

            var equipmentListId = 'equipment_list_item_'+collectorNumber; // the id of the equipment in the list of equipment
            if(document.getElementById(equipmentListId) && this.gamedatas.playerLetters[this.player_id])
            { // equipment HTML node exists and they are not a spectator
                dojo.addClass(equipmentListId, 'used_equipment'); // darken the equipment in the list of equipment
            }

            dojo.addClass(equipmentPlayerBoardId, 'modified_glow');

            return equipmentPlayerBoardId;
        },

        addMyHandPlayerEquipmentToStock(collectorNumber, equipmentId, equipName, equipEffect)
        {
            this.myHandEquipment.addToStockWithId( collectorNumber, equipmentId );

            var translatedName = _(equipName);
            var translatedEffect = _(equipEffect);

            var htmlEquipmentName = "<div id=my_equipment_name_"+equipmentId+" class=large_equipment_name>"+translatedName+"<div>";
            dojo.place( htmlEquipmentName, "player_a_equipment_hand_holder_item_"+equipmentId );

            var htmlEquipmentEffect = "<div id=my_equipment_effect_"+equipmentId+" class=large_equipment_effect>"+translatedEffect+"<div>";
            dojo.place( htmlEquipmentEffect, "player_a_equipment_hand_holder_item_"+equipmentId );

            var htmlIdForCardInStock = 'player_a_equipment_hand_holder_item_'+equipmentId;
            dojo.connect( $(htmlIdForCardInStock), 'onclick', this, 'onClickEquipmentCardInHand' );

            dojo.addClass(htmlIdForCardInStock, "large_component_rounding"); // give more corner rounding and shadow to these larger equipment cards

            return htmlIdForCardInStock;
        },

        addActivePlayerEquipmentToStock(playerLetter, collectorNumber, equipName, equipEffect)
        {
            switch(playerLetter)
            {
              case 'a':
              this.activePlayerEquipmentA.addToStockWithId( collectorNumber, collectorNumber );
              break;
              case 'b':
              this.activePlayerEquipmentB.addToStockWithId( collectorNumber, collectorNumber );
              break;
              case 'c':
              this.activePlayerEquipmentC.addToStockWithId( collectorNumber, collectorNumber );
              break;
              case 'd':
              this.activePlayerEquipmentD.addToStockWithId( collectorNumber, collectorNumber );
              break;
              case 'e':
              this.activePlayerEquipmentE.addToStockWithId( collectorNumber, collectorNumber );
              break;
              case 'f':
              this.activePlayerEquipmentF.addToStockWithId( collectorNumber, collectorNumber );
              break;
              case 'g':
              this.activePlayerEquipmentG.addToStockWithId( collectorNumber, collectorNumber );
              break;
              case 'h':
              this.activePlayerEquipmentH.addToStockWithId( collectorNumber, collectorNumber );
              break;
            }

            var htmlIdForCardInStock = 'player_board_active_equipment_'+playerLetter+'_item_'+collectorNumber;
            this.addLargeEquipmentTooltip(htmlIdForCardInStock, collectorNumber, equipName, equipEffect); // add a hoverover tooltip with a bigger version of the card

            this.disconnect( $(htmlIdForCardInStock), 'onclick'); // disconnect any previously registered onclicks for this
            dojo.connect( $(htmlIdForCardInStock), 'onclick', this, 'onClickPlayerBoardEquipmentCard' );
        },

        // Add a hoverover tooltip with a bigger version of the card.
        addLargeEquipmentTooltip(htmlIdToAddItTo, collectorNumber, equipName, equipEffect)
        {
            var html = this.format_block( 'jstpl_largeEquipment', {
                x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                equipmentName: _(equipName),
                equipmentEffect: _(equipEffect)
            } ); // the HTML (image) to be displayed
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( htmlIdToAddItTo, html, delay ); // add the tooltip with the above configuration
        },

        // cardHolderDiv = the div in which we should place this card (it might move right afterwards)
        placeMyEquipmentCard: function(equipmentCardId, collectorNumber, initialPlacementDiv, equipName, equipEffect)
        {
            return this.addMyHandPlayerEquipmentToStock(collectorNumber, equipmentCardId, equipName, equipEffect);
        },

        placeOpponentEquipmentCard: function(playerLetterOrder, equipmentId)
        {
            this.addHandPlayerEquipmentToStock(playerLetterOrder, 0, equipmentId);

            return 'player_board_hand_equipment_'+playerLetterOrder+'_item_'+equipmentId;
        },

        addHandPlayerEquipmentToStock(playerLetter, collectorNumber, equipmentId, equipName, equipEffect)
        {
            switch(playerLetter)
            {
              case 'a':
              this.handPlayerEquipmentA.addToStockWithId( equipmentId, equipmentId );
              break;
              case 'b':
              this.handPlayerEquipmentB.addToStockWithId( equipmentId, equipmentId );
              break;
              case 'c':
              this.handPlayerEquipmentC.addToStockWithId( equipmentId, equipmentId );
              break;
              case 'd':
              this.handPlayerEquipmentD.addToStockWithId( equipmentId, equipmentId );
              break;
              case 'e':
              this.handPlayerEquipmentE.addToStockWithId( equipmentId, equipmentId );
              break;
              case 'f':
              this.handPlayerEquipmentF.addToStockWithId( equipmentId, equipmentId );
              break;
              case 'g':
              this.handPlayerEquipmentG.addToStockWithId( equipmentId, equipmentId );
              break;
              case 'h':
              this.handPlayerEquipmentH.addToStockWithId( equipmentId, equipmentId );
              break;
            }

            var htmlIdForCardInStock = 'player_board_hand_equipment_'+playerLetter+'_item_'+equipmentId;
            this.disconnect( $(htmlIdForCardInStock), 'onclick'); // disconnect any previously registered onclicks for this
            dojo.connect( $(htmlIdForCardInStock), 'onclick', this, 'onClickPlayerBoardEquipmentCard' );
        },

        addBombAndKnifeSymbols: function(playerLetter, cardHolderDiv, cardPosition, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives)
        {
            var symbolIndex = 0; // 0 for first symbol, 1 for second symbol
            var symbolDiv = 'symbol_player_' + playerLetter + '_integrity_card_' + cardPosition + '_'+symbolIndex;

            var seen3OffsetKnives = 0;
            if(hasSeen3Knives)
            {
                seen3OffsetKnives = 1;
            }

            var seen3OffsetBombs = 0;
            if(hasSeen3Bombs)
            {
                seen3OffsetBombs = 1;
            }

            if(hasKnifeSymbol == 1)
            { // has a KNIFE symbol on it

                if(!document.getElementById(symbolDiv))
                { // this symbol doesn't already exist


                    // place the KNIFE symbol
                    dojo.place(
                                this.format_block( 'jstpl_integritySymbol', {
                                    x: 0,
                                    y: seen3OffsetKnives * this.integritySymbolHeight,
                                    playerLetter: playerLetter,
                                    cardPosition: cardPosition,
                                    symbolIndex: symbolIndex
                            } ), cardHolderDiv );

                    // position the symbol based on the location
                    if(playerLetter == 'a' || playerLetter == 'h' || playerLetter == 'c' || playerLetter == 'e')
                    {
                        dojo.addClass(symbolDiv, 'integrity_symbol_vertical'); // put it in the upper-left corner
                    }
                    else if(playerLetter == 'b' || playerLetter == 'g')
                    {
                        dojo.addClass(symbolDiv, 'integrity_symbol_counterclockwise'); // put it in the upper-left corner
                    }
                    else if(playerLetter == 'd' || playerLetter == 'f')
                    {
                        dojo.addClass(symbolDiv, 'integrity_symbol_clockwise'); // put it in the upper-left corner
                    }
                }
            }

            if(hasBombSymbol == 1)
            { // has a BOMB symbol on it

                if(hasKnifeSymbol == 1)
                { // it ALSO has a knife symbol
                    symbolIndex = 1;
                }
                symbolDiv = 'symbol_player_' + playerLetter + '_integrity_card_' + cardPosition + '_'+symbolIndex;
                if(!document.getElementById(symbolDiv))
                { // this symbol doesn't already exist

                    // place the BOMB symbol
                    dojo.place(
                                this.format_block( 'jstpl_integritySymbol', {
                                    x: this.integritySymbolWidth,
                                    y: seen3OffsetBombs * this.integritySymbolHeight,
                                    playerLetter: playerLetter,
                                    cardPosition: cardPosition,
                                    symbolIndex: symbolIndex
                            } ), cardHolderDiv );

                    // position the symbol based on the location
                    if(playerLetter == 'a' || playerLetter == 'h' || playerLetter == 'c' || playerLetter == 'e')
                    {
                        dojo.addClass(symbolDiv, 'integrity_symbol_vertical'); // put it in the upper-left corner

                        if(hasKnifeSymbol == 1)
                        { // it ALSO has a knife symbol
                            dojo.addClass(symbolDiv, 'integrity_symbol_vertical_second_symbol'); // put it in the upper-left corner
                        }
                    }
                    else if(playerLetter == 'b' || playerLetter == 'g')
                    {
                        dojo.addClass(symbolDiv, 'integrity_symbol_counterclockwise'); // put it in the upper-left corner

                        if(hasKnifeSymbol == 1)
                        { // it ALSO has a knife symbol
                            dojo.addClass(symbolDiv, 'integrity_symbol_counterclockwise_second_symbol'); // put it in the upper-left corner
                        }
                    }
                    else if(playerLetter == 'd' || playerLetter == 'f')
                    {
                        dojo.addClass(symbolDiv, 'integrity_symbol_clockwise'); // put it in the upper-left corner

                        if(hasKnifeSymbol == 1)
                        { // it ALSO has a knife symbol
                            dojo.addClass(symbolDiv, 'integrity_symbol_clockwise_second_symbol'); // put it in the upper-left corner
                        }
                    }
                }

            }
        },

        placeIntegrityCard: function(playerLetter, cardPosition, visibilityToYou, cardType, rotation, isHidden, playersSeen, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives)
        {
            var visibilityOffset = this.getVisibilityOffset(visibilityToYou); // get sprite X value for this card type
            var cardTypeOffset = this.getCardTypeOffset(cardType, affectedByPlantedEvidence); // get sprite Y value for this card type

            var cardHolderDiv = 'player_'+playerLetter+'_integrity_card_'+cardPosition+'_holder'; // html ID of the card's container
            var cardDiv = 'player_'+playerLetter+'_integrity_card_'+cardPosition; // HTML ID of the new card



            // place the INTEGRITY CARD
            dojo.place(
                        this.format_block( 'jstpl_integrityCard', {
                            x: this.integrityCardWidth*(visibilityOffset),
                            y: this.integrityCardHeight*(cardTypeOffset),
                            playerLetter: playerLetter,
                            cardPosition: cardPosition
                    } ), cardHolderDiv );

            if(this.gamedatas.bombersTraitorsExpansion == 2)
            { // we are using the Bombers & Traitors expansion

                this.addBombAndKnifeSymbols(playerLetter, cardDiv, cardPosition, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives);
            }


            if(affectedByPlantedEvidence ||
              (isHidden && (affectedByDisguise || affectedBySurveillanceCamera)))
            {
                dojo.addClass( cardDiv, 'modified_glow'); // add yellow border (glow) around it so we know this card is being modified by something
            }

            if(visibilityToYou == 'HIDDEN_NOT_SEEN')
            { // this player has not seen the value of this card
                cardType = "Unknown"; // do not show it in the tooltip
            }



            // add tooltip
            this.addIntegrityCardTooltip(cardDiv, cardType, isHidden, playersSeen, cardPosition, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol);


            //this.rotateTo( cardDiv, rotation );

            var animation = dojo.animateProperty({
              	node: cardDiv,
              	duration: 1000,
                delay: 1000,
              	properties: {
              		propertyTransform: {start: 0, end: rotation}
              	},
              	onAnimate: function (values) {
              		dojo.style(this.node, 'transform', 'rotate(' + parseFloat(values.propertyTransform.replace("px", "")) + 'deg)');
              	}
            });
            animation.play();

            dojo.connect( $(cardDiv), 'onclick', this, 'onClickIntegrityCard' ); // re-add the onclick connection

            return cardDiv;
        },

        addIntegrityCardTooltip: function(htmlId, cardType, isHidden, playersSeen, cardPositionInt, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol)
        {
            var typeLabel = _("Type:"); // separate out labels for translation
            var stateLabel = _("State:"); // separate out labels for translation
            var positionLabel = _("Position:"); // separate out labels for translation
            var playersSeenLabel = _("Seen By:"); // separate out labels for translation
            var woundedLabel = _("Wounded:"); // separate out labels for translation
            var bombLabel = _("Bomb:"); // separate out labels for translation
            var knifeLabel = _("Knife:"); // separate out labels for translation

            var isHiddenText = this.convertIsHiddenToText(isHidden, affectedByDisguise, affectedBySurveillanceCamera); // convert whether it is hidden to a translated text
            var cardTypeText = this.convertCardTypeToText(cardType, affectedByPlantedEvidence); // convert the type of card to a translated version
            var positionText = this.convertCardPositionToText(cardPositionInt); // convert card position (1,2,3) to text (LEFT,MIDDLE,RIGHT)
            var playersSeenText = this.convertPlayersSeenToText(playersSeen);
            var woundedText = this.convertWoundedToText(isWounded);
            var bombText = this.convertBombToText(hasBombSymbol);
            var knifeText = this.convertKnifeToText(hasKnifeSymbol);

            var html = '<div>';
            html += '<div><b>'+ typeLabel + '</b> '+ cardTypeText +'</div>';
            html += '<div><b>'+ stateLabel +'</b> '+ isHiddenText +'</div>';
            html += '<div><b>'+ positionLabel + '</b> '+ positionText +'</div>';
            html += '<div><b>'+ playersSeenLabel + '</b> '+ playersSeenText + '</div>';

            if(cardTypeText == 'Agent' || cardTypeText == 'Kingpin')
            { // this is a leader
                html += '<div><b>'+ woundedLabel + '</b> '+ woundedText +'</div>';
            }

            if(this.gamedatas.bombersTraitorsExpansion == 2)
            { // we are using the zombies expansion
                html += '<div><b>'+ bombLabel + '</b> '+ bombText +'</div>';
                html += '<div><b>'+ knifeLabel + '</b> '+ knifeText + '</div>';
            }

            html += '</div>';
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( htmlId, html, delay ); // add the tooltip with the above configuration
        },

        addGunTooltip: function(gunHtmlId, heldByName, aimedAtName)
        {
            if((heldByName == null || heldByName == '') && (aimedAtName == null || aimedAtName == ''))
            { // it is not held nor aimed
                return; // we do not want to show a tool tip for guns in the center
            }

            var heldByLabel = _("Held By:"); // change to translated string
            var aimedAtLabel = _("Aimed At:"); // change to translated string
            var html = '<div><div><b>'+heldByLabel+'</b> ' + heldByName + '</div><div><b>'+aimedAtLabel+'</b> ' + aimedAtName + '</div></div>';
            var delay = 0; // any delay before it appears

            this.addTooltipHtml( gunHtmlId, html, delay ); // add the tooltip with the above configuration
        },

        getIntegrityCardRotation: function( playerPosition )
        {
          switch(playerPosition)
          { // REFECTOR THIS WHEN YOU DECIDE HOW PLAYER POSITION SHOULD WORK
            case 'a':
              return 0;
            case 'b':
              return -90;
            case 'c':
              return 0;
            case 'd':
              return 90;
            case 'e':
              return 0;
            case 'f':
              return 90;
            case 'g':
              return -90;
            case 'h':
              return 0;
          }
        },

        getCardTypeOffset: function( cardType, affectedByPlantedEvidence )
        {
            var modifiedCardType = cardType;
            if(affectedByPlantedEvidence)
            {
                if(modifiedCardType == 'honest')
                {
                    modifiedCardType = 'crooked';
                }
                else if(modifiedCardType == 'crooked')
                {
                    modifiedCardType = 'honest';
                }
            }

            cardTypeOffset = 0;
            if(modifiedCardType == 'crooked')
            {
                cardTypeOffset = 1;
            }
            else if(modifiedCardType == 'honest')
            {
                cardTypeOffset = 2;
            }
            else if(modifiedCardType == 'kingpin')
            {
                cardTypeOffset = 3;
            }
            else if(modifiedCardType == 'infector')
            {
                cardTypeOffset = 4;
            }

            return cardTypeOffset;
        },

        getVisibilityOffset: function( visibilityToYou, affectedBySurveillanceCamera )
        {
            if(affectedBySurveillanceCamera)
            {
                return 0; // revealed
            }
            else
            { // NOT affected by surveillance camera

                var visibilityOffset = 0;
                if(visibilityToYou == 'HIDDEN_NOT_SEEN')
                {
                    visibilityOffset = 2;
                }
                else if (visibilityToYou == 'HIDDEN_SEEN')
                {
                    visibilityOffset = 1;
                }
            }

            return visibilityOffset;
        },

        getEquipmentSpriteX: function(collectorNumber)
        {
            switch( collectorNumber )
            {
                case "2": // coffee
                    return 0;
                case "8": // planted evidence
                    return 2;
                case "12": // smoke grenade
                    return 2;
                case "15": // truth serum
                    return 1;
                case "16": // wiretap
                    return 1;
                case "44": // riot shield
                    return 1;
                case "11": // restraining order
                    return 0;
                case "37": // mobile detonator
                    return 2;
                case "4": // evidence bag
                    return 0;
                case "35": // med kit
                    return 1;
                case "14": // taser
                    return 2;
                case "3": // Defibrillator
                case "301": // Defibrillator
                case "302": // Defibrillator
                    return 5;
                case "1": // Blackmail
                    return 4;
                case "30": // Disguise
                    return 3;
                case "45": // Walkie Talkie
                    return 5;
                case "9": // Polygraph
                    return 4;
                case "13": // Surveillance Camera
                    return 3;
                case "7": // Metal Detector
                    return 5;
                case "17": // Deliriant
                    return 4;
                case "6": // K-9 Unit
                    return 3;

                case "60": // Crossbow
                    return 0;
                case "61": // Transfusion Tube
                    return 3;
                case "62": // Zombie Serum
                    return 5;
                case "63": // Flamethrower
                    return 1;
                case "64": // Chainsaw
                    return 5;
                case "65": // Zombie Mask
                    return 4;
                case "66": // Machete
                    return 2;
                case "67": // Weapon Crate
                    return 3;
                case "68": // Alarm Clock
                    return 4;

                case "18": // Classified Orders
                    return 0;
                case "19": // Fake ID
                    return 1;
                case "20": // Fingerprint Kit
                    return 2;
                case "21": // Grenade
                    return 3;
                case "22": // Holster
                    return 4;


            }
        },

        getEquipmentSpriteY: function(collectorNumber)
        {
            switch( collectorNumber )
            {
                case "2": // coffee
                    return 1;
                case "8": // planted evidence
                    return 1;
                case "12": // smoke grenade
                    return 0;
                case "15": // truth serum
                    return 0;
                case "16": // wiretap
                    return 1;
                case "44": // riot shield
                    return 3;
                case "11": // restraining order
                    return 3;
                case "37": // mobile detonator
                    return 2;
                case "4": // evidence bag
                    return 2;
                case "35": // med kit
                    return 2;
                case "14": // taser
                    return 3;
                case "3": // Defibrillator
                case "301": // Defibrillator
                case "302": // Defibrillator
                    return 2;
                case "1": // Blackmail
                    return 2;
                case "30": // Disguise
                    return 2;
                case "45": // Walkie Talkie
                    return 1;
                case "9": // Polygraph
                    return 1;
                case "13": // Surveillance Camera
                    return 1;
                case "7": // Metal Detector
                    return 0;
                case "17": // Deliriant
                    return 0;
                case "6": // K-9 Unit
                    return 0;


                case "60": // Crossbow
                    return 4;
                case "61": // Transfusion Tube
                    return 4;
                case "62": // Zombie Serum
                    return 4;
                case "63": // Flamethrower
                    return 4;
                case "64": // Chainsaw
                    return 3;
                case "65": // Zombie Mask
                    return 4;
                case "66": // Machete
                    return 4;
                case "67": // Weapon Crate
                    return 3;
                case "68": // Alarm Clock
                    return 3;

                case "18": // Classified Orders
                    return 5;
                case "19": // Fake ID
                    return 5;
                case "20": // Fingerprint Kit
                    return 5;
                case "21": // Grenade
                    return 5;
                case "22": // Holster
                    return 5;
            }
        },

        placeGun: function(gunId, gunType, heldByLetterOrder, aimedAtLetterOrder, heldByName, aimedAtName)
        {
            var gunIdHtml = 'gun_'+gunId;
            var gunHolderDiv = 'gun_deck'; // assume the gun is in the middle of the table
            var gunOffset = 0;
            if(gunType == 'arm')
            {  // these are zombie arms
                gunOffset = this.gunCardHeight;
                gunHolderDiv = 'arm_deck';
            }

            if(heldByLetterOrder != null && heldByLetterOrder != '')
            { // the gun is being held by a player rather than in the middle of the table
                gunHolderDiv = 'player_'+heldByLetterOrder+'_gun_holder'; // put the gun in front of the player holding it
            }

            dojo.place(
                    this.format_block( 'jstpl_gun', {
                        gunId: gunId,
                        x: 0,
                        y: gunOffset
                    } ), gunHolderDiv );

            if(document.getElementById(gunIdHtml))
            { // the gun is out on the board
                // add tooltip
                this.addGunTooltip(gunIdHtml, heldByName, aimedAtName);
            }

            return 'gun_'+gunId;
        },

        rotateGun: function(gunId, gunType, rotation, isPointingLeft)
        {
            if(rotation === null)
            {
                rotation = 0;
            }

            var gunDiv = 'gun_' + gunId ; // the html ID of the gun
            if(document.getElementById(gunDiv))
            { // this gun is out on the table somewhere
                var gunSpriteX = this.gunCardWidth*(isPointingLeft); // set the X position in the sprite to point at the LEFT or RIGHT pointing gun
                var gunSpriteY = 0;
                if(gunType == 'arm')
                { // this is a zombie arm
                    gunSpriteY = this.gunCardHeight;
                }
                dojo.style( gunDiv, 'backgroundPosition', '-' + gunSpriteX + 'px -' + gunSpriteY + 'px' ); // switch the gun to use the correct LEFT or RIGHT pointing image

                this.rotateTo( gunDiv, rotation ); // rotate the gun
            }

            return gunDiv;
        },

        placeInfectionToken: function(playerLetterOfInfected, positionOfInfectedCard)
        {
            var htmlIdOfTargetCard = 'player_' + playerLetterOfInfected + '_integrity_card_' + positionOfInfectedCard;
            var cardType = positionOfInfectedCard+''+playerLetterOfInfected;
            var movingTokenHtmlId = "integrity_token_"+cardType;

            if(!document.getElementById(movingTokenHtmlId))
            { // this element doesn't already exist

                if(positionOfInfectedCard < 4)
                { // they don't already have 3 infection tokens
                    dojo.place(
                            this.format_block( 'jstpl_integrityCardToken', {
                                cardType: cardType,
                                x: 0,
                                y: this.woundedTokenHeight
                            } ), htmlIdOfTargetCard );

                    dojo.addClass(movingTokenHtmlId, "infection_token"); // add the infection token class
                }

            }


            return movingTokenHtmlId;
        },

        placeAndMoveInfectionToken: function(playerLetterOfInfected, positionOfInfectedCard, delay)
        {
            if(positionOfInfectedCard >3)
            { // they don't already have 3 infection tokens
                return '';
            }

            var startingHtmlId = 'infection_tokens';
            var cardType = positionOfInfectedCard+''+playerLetterOfInfected;
            var movingTokenHtmlId = "integrity_token_"+cardType;

            if(!document.getElementById(movingTokenHtmlId))
            { // this element doesn't already exist

                dojo.place(
                            this.format_block( 'jstpl_integrityCardToken', {
                                cardType: cardType,
                                x: 0,
                                y: this.woundedTokenHeight
                            } ), startingHtmlId );


                var destinationHtmlId = 'player_'+playerLetterOfInfected+'_integrity_card_'+positionOfInfectedCard;
                //dojo.addClass(movingTokenHtmlId, "remove_top_left"); // remove top and left so it can move smoothly
                dojo.addClass(movingTokenHtmlId, "infection_token"); // add the infection token class (must be done before moving)

                var anim1 = this.slideToObject(movingTokenHtmlId, destinationHtmlId, 750, delay);
                dojo.connect(anim1, 'onEnd', function(node)
                { // do the following after the animation ends
                  this.attachToNewParent( movingTokenHtmlId, destinationHtmlId ); // move this in the DOM to the new player's integrity card holder (must be done BEFORE sliding because it breaks all connections to it)
                  //dojo.addClass( movingTokenHtmlId, 'remove_top_left'); // highlight the gun that just moved

                });
                anim1.play();

            }

            return movingTokenHtmlId;
        },


        placeWoundedToken: function(woundedPlayerLetterOrder, leaderCardPosition, cardType)
        {
            var htmlIdOfLeaderCard = 'player_' + woundedPlayerLetterOrder + '_integrity_card_' + leaderCardPosition;
            var movingTokenHtmlId = "integrity_token_"+cardType;

            if(!document.getElementById(movingTokenHtmlId))
            { // this element doesn't already exist

                dojo.place(
                        this.format_block( 'jstpl_integrityCardToken', {
                            cardType: cardType,
                            x: 0,
                            y: 0
                        } ), htmlIdOfLeaderCard );

                dojo.addClass(movingTokenHtmlId, "wounded_token"); // add the wounded token class

            }

            return movingTokenHtmlId;
        },

        placeAndMoveWoundedToken: function(woundedPlayerLetterOrder, leaderCardPosition, cardType)
        {

            var cardTypeOriginal = cardType;
            cardType = cardType+"_sliding";
            var startingHtmlId = 'wounded_tokens';
            var movingTokenHtmlId = "integrity_token_"+cardType;

            if(!document.getElementById(movingTokenHtmlId))
            { // this element doesn't already exist

                dojo.place(
                        this.format_block( 'jstpl_integrityCardToken', {
                            cardType: cardType,
                            x: 0,
                            y: 0
                        } ), startingHtmlId );




                var destinationHtmlId = 'player_' + woundedPlayerLetterOrder + '_integrity_card_' + leaderCardPosition;


                dojo.addClass(movingTokenHtmlId, "wounded_token"); // add the wounded token class (must be done before moving)
                this.slideToObjectAndDestroy( movingTokenHtmlId, destinationHtmlId, 1000, 0 );

    /*
                var anim1 = this.slideToObject(movingTokenHtmlId, destinationHtmlId, 750, 250);
                dojo.connect(anim1, 'onEnd', function(node)
                { // do the following after the animation ends
                    this.attachToNewParent( movingTokenHtmlId, destinationHtmlId ); // move this in the DOM to the new player's integrity card holder (must be done BEFORE sliding because it breaks all connections to it)
                    //dojo.style( movingTokenHtmlId, 'marginTop', '-10px' ); // move the token so it doesn't cover the name of the card and is visible when there is a infection token on it too
                    //dojo.addClass( movingTokenHtmlId, 'remove_top_left');
                });
                anim1.play();
    */
            }

            this.placeWoundedToken(woundedPlayerLetterOrder, leaderCardPosition, cardTypeOriginal); // gotta put this here for spectators to see it because rePlaceIntegrityCard doesn't notify all players


            return movingTokenHtmlId;
        },

        removeWoundedToken: function(woundedCardId)
        {
            var woundedTokenHtml = 'integrity_token_' + woundedCardId;

            dojo.destroy(woundedTokenHtml); // remove the existing one (the sliding doesn't seem to be working)
        },

        removeInfectionToken: function(positionPlayerId)
        {
            var tokenHtml = 'integrity_token_' + positionPlayerId;

            dojo.destroy(tokenHtml); // remove the existing one (the sliding doesn't seem to be working)
        },

        placeCenterWoundedToken: function()
        {
            var destination = 'wounded_tokens';

            dojo.place(
                  this.format_block( 'jstpl_integrityCardToken', {
                      cardType: "wounded_center",
                      x: 0,
                      y: 0
                  } ), destination );

            dojo.addClass("integrity_token_wounded_center", "center_integrity_token");
            dojo.addClass("integrity_token_wounded_center", "wounded_token");
        },

        placeCenterInfectionToken: function()
        {
            var destination = 'infection_tokens';

            dojo.place(
                    this.format_block( 'jstpl_integrityCardToken', {
                        cardType: "infection_center",
                        x: 0,
                        y: this.woundedTokenHeight
                    } ), destination );

            dojo.addClass("integrity_token_infection_center", "infection_token"); // add the infection token class
            dojo.addClass("integrity_token_infection_center", "center_integrity_token");
        },

        placeCurrentTurnToken: function(currentPlayerId, isClockwise, currentPlayerName, nextPlayerName)
        {
            var destination = 'player_board_' + currentPlayerId;
            var tokenId = 'current_player_token';

            var tokenAlreadyExists = document.getElementById(tokenId); // see if this already exists
            if(tokenAlreadyExists)
            {
                dojo.destroy(tokenId); // destroy it before placing a new one
            }

            var yOffset = 30; // we usually want to go clockwise (arrow down)
            if(isClockwise == false)
            { // we want to go counter-clockwise (arrow up)
                yOffset = 0;
            }

            dojo.place(
                    this.format_block( 'jstpl_currentPlayerToken', {
                        x: 0,
                        y: yOffset
                    } ), destination );

            dojo.style( tokenId, 'left', '160px' ); // switch to the arm pointing right image
            dojo.style( tokenId, 'top', '20px' ); // switch to the arm pointing right image

            var currentLabel = _("Current Player:"); // separate out labels for translation
            var nextLabel = _("Next Player:"); // separate out labels for translation
            var currentPlayerNameColored = "<span style=\"color:#" + this.gamedatas.player_colors[currentPlayerName] + "\"><b>" + currentPlayerName + "</b></span>"
            var nextPlayerNameColored = "<span style=\"color:#" + this.gamedatas.player_colors[nextPlayerName] + "\"><b>" + nextPlayerName + "</b></span>"
            var html = '<div><div><b>'+ currentLabel + '</b> '+ currentPlayerNameColored +'</div><div><b>'+ nextLabel +'</b> '+ nextPlayerNameColored + '</div></div>';
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( tokenId, html, delay ); // add the tooltip with the above configuration
        },

        zombifyPlayer: function(playerId, letterOfPlayerWhoIsNowAZombie)
        {
            var htmlIdOfPlayerZombieArea = 'player_' + letterOfPlayerWhoIsNowAZombie + '_area';
            var htmlIdOfRightPlayerBoardId = 'overall_player_board_' + playerId;
            var zombieClass = 'zombie_player_area';

            dojo.addClass( htmlIdOfPlayerZombieArea, zombieClass ); // add style to show this player is zombie on the player's mat
            dojo.addClass( htmlIdOfRightPlayerBoardId, zombieClass ); // add style to show this player is zombie on the right player board
        },

        eliminatePlayer: function(eliminatedPlayerId, letterOfPlayerWhoWasEliminated)
        {
            var htmlIdOfPlayerEliminatedArea = 'player_' + letterOfPlayerWhoWasEliminated + '_area';
            var htmlIdOfRightPlayerBoardId = 'overall_player_board_' + eliminatedPlayerId;
            var eliminatedClass = 'eliminated_player_area';

            dojo.addClass( htmlIdOfPlayerEliminatedArea, eliminatedClass ); // add style to show this player is eliminated on the player's mat
            dojo.addClass( htmlIdOfRightPlayerBoardId, eliminatedClass ); // add style to show this player is eliminated on the right player board
        },

        revivePlayer: function(eliminatedPlayerId, letterOfPlayerWhoWasEliminated)
        {
            var htmlIdOfPlayerEliminatedArea = 'player_' + letterOfPlayerWhoWasEliminated + '_area';
            var htmlIdOfRightPlayerBoardId = 'overall_player_board_' + eliminatedPlayerId;
            var eliminatedClass = 'eliminated_player_area';
            var zombieClass = 'zombie_player_area';

            dojo.removeClass( htmlIdOfPlayerEliminatedArea, eliminatedClass ); // remove style to show this player is eliminated on the player's mat
            dojo.removeClass( htmlIdOfRightPlayerBoardId, eliminatedClass ); // remove style to show this player is eliminated on the right player board
            dojo.removeClass( htmlIdOfPlayerEliminatedArea, zombieClass ); // remove style to show this player is a zombie on the player's mat
            dojo.removeClass( htmlIdOfRightPlayerBoardId, zombieClass ); // remove style to show this player is eliminated on the right player board
        },

        displayWinnerBorders: function(winnerPlayerId)
        {
          var letterOfPlayer = this.gamedatas.playerLetters[winnerPlayerId].player_letter;

//            var htmlIdOfPlayerArea = 'player_' + letterOfPlayer + '_area';
          var htmlIdOfPlayerName = 'player_' + letterOfPlayer + '_name_holder';
          var htmlIdOfRightPlayerBoardId = 'overall_player_board_' + winnerPlayerId;
          var winnerClass = 'game_winner_player_name';

          dojo.addClass( htmlIdOfPlayerName, winnerClass ); // add style to show this player is the winner on the player's mat
          dojo.addClass( htmlIdOfRightPlayerBoardId, winnerClass ); // add style to show this player is the winner on the player's mat
        },

        giveWinnerMedal: function(winnerPlayerId)
        {
          var letterOfPlayer = this.gamedatas.playerLetters[winnerPlayerId].player_letter;

          var htmlIdOfPlayerName = 'player_' + letterOfPlayer + '_name_holder'; // div holding the player's name in the player area
          dojo.place(
                  this.format_block( 'jstpl_medalToken', {
                      playerId: winnerPlayerId
                  } ), htmlIdOfPlayerName );



          var htmlIdOfRightPlayerBoardId = 'player_board_hand_equipment_' + letterOfPlayer; // div holding the player's equipment in hand on right side
          dojo.place(
                  this.format_block( 'jstpl_medalToken', {
                      playerId: winnerPlayerId
                  } ), htmlIdOfRightPlayerBoardId );

        },

        revealEquipmentInHand: function( playerLetter, equipmentId, collectorNumber, equipName, equipEffect )
        {
            //var playerAHandHtmlId = "player_a_equipment_hand_holder_item_" + equipmentId; // the html ID of the big card in the player A hand
            //var revealedBoardEquipmentHtmlId = "player_" + playerLetter + "_hand_equipment_" + equipmentId;
            var equipmentInHandHtmlId = "player_board_hand_equipment_"+playerLetter+"_item_"+equipmentId;

            if(document.getElementById(equipmentInHandHtmlId))
            { // this card exists on the player's board
                dojo.destroy(equipmentInHandHtmlId); // destroy it
            }

            // place a new equipment
            this.addActivePlayerEquipmentToStock(playerLetter, collectorNumber, equipName, equipEffect);
            var htmlIdForCardInStock = 'player_board_active_equipment_'+playerLetter+'_item_'+collectorNumber;
            this.highlightComponent(htmlIdForCardInStock); // highlight the card that was just revealed

            var equipmentListId = 'equipment_list_item_'+collectorNumber; // the id of the equipment in the list of equipment
            if(document.getElementById(equipmentListId) && this.gamedatas.playerLetters[this.player_id])
            { // equipment HTML node exists and they are not a spectator
                dojo.addClass(equipmentListId, 'used_equipment'); // darken the equipment in the list of equipment
            }
        },

        discardEquipmentFromHand: function( playerLetter, equipmentId, removeFromPlayerAHand, animateDiscard, playerIdDiscarding, collectorNumber )
        {
            var playerAHandHtmlId = "player_a_equipment_hand_holder_item_" + equipmentId; // the html ID of the big card in the player A hand
            var revealedBoardEquipmentHtmlId = "player_" + playerLetter + "_hand_equipment_" + equipmentId;

            var revealedEquipmentExists = document.getElementById(revealedBoardEquipmentHtmlId); // see if we can find an ID for a revealed equipment on the player's board
            if(revealedEquipmentExists)
            { // this card exists on the player's board
                if(animateDiscard)
                {
                    this.slideToObjectAndDestroy( revealedBoardEquipmentHtmlId, 'equipment_deck', 1000, 0 ); // discard from current player's hand too
                }
                else
                {
                    dojo.destroy(revealedBoardEquipmentHtmlId); // just destroy it.
                }
            }

            switch(playerLetter)
            {
                case 'a':
                this.handPlayerEquipmentA.removeFromStockById(equipmentId);

                if(removeFromPlayerAHand)
                { // we want to remove it from the player A hand too
                    this.myHandEquipment.removeFromStockById(equipmentId);

                    var handEquipmentExists = document.getElementById(playerAHandHtmlId); // see if we can find an ID for a revealed equipment on the player's large hand
                    if(handEquipmentExists)
                    { // this card exists on the player's large hand (it won't exist for spectators)
                        this.slideToObjectAndDestroy( playerAHandHtmlId, 'equipment_deck', 1000, 0 ); // discard from current player's hand too
                    }
                }
                break;
                case 'b':
                this.handPlayerEquipmentB.removeFromStockById(equipmentId);
                break;
                case 'c':
                this.handPlayerEquipmentC.removeFromStockById(equipmentId);
                break;
                case 'd':
                this.handPlayerEquipmentD.removeFromStockById(equipmentId);
                break;
                case 'e':
                this.handPlayerEquipmentE.removeFromStockById(equipmentId);
                break;
                case 'f':
                this.handPlayerEquipmentF.removeFromStockById(equipmentId);
                break;
                case 'g':
                this.handPlayerEquipmentG.removeFromStockById(equipmentId);
                break;
                case 'h':
                this.handPlayerEquipmentH.removeFromStockById(equipmentId);
                break;
            }
        },

        discardEquipmentFromActive: function( playerLetter, collectorNumber, equipmentId, equipName, equipEffect )
        {
            switch(playerLetter)
            {
                case 'a':
                this.activePlayerEquipmentA.removeFromStockById(collectorNumber);
                break;
                case 'b':
                this.activePlayerEquipmentB.removeFromStockById(collectorNumber);
                break;
                case 'c':
                this.activePlayerEquipmentC.removeFromStockById(collectorNumber);
                break;
                case 'd':
                this.activePlayerEquipmentD.removeFromStockById(collectorNumber);
                break;
                case 'e':
                this.activePlayerEquipmentE.removeFromStockById(collectorNumber);
                break;
                case 'f':
                this.activePlayerEquipmentF.removeFromStockById(collectorNumber);
                break;
                case 'g':
                this.activePlayerEquipmentG.removeFromStockById(collectorNumber);
                break;
                case 'h':
                this.activePlayerEquipmentH.removeFromStockById(collectorNumber);
                break;
            }

            var htmlIdOfCard = this.placeActiveCentralEquipmentCard(equipmentId, collectorNumber, 0, equipName, equipEffect); // place on active_equipment_center_holder

            this.placeOnObject( htmlIdOfCard, "player_board_hand_equipment_"+playerLetter );

            // slide to active_equipment_center_holder
            var destination = 'active_equipment_center_holder';
            var anim1 = this.slideToObject(htmlIdOfCard, destination, 1000, 250);
            dojo.connect(anim1, 'onEnd', function(node)
            { // do the following after the animation ends

              this.highlightComponent(htmlIdOfCard); // highlight the card just moved
            });
            anim1.play();
        },

        drawEquipmentCard: function(equipmentCardId, collectorNumber, equipName, equipEffect, playerIdDrawing)
        {
            var playerLetterOrder = 'a';

            var startHtmlId = 'equipment_deck';
            var destinationHtmlId = 'player_'+playerLetterOrder+'_equipment_hand_holder';

            var cardHtmlId = this.placeMyEquipmentCard(equipmentCardId, collectorNumber, startHtmlId, equipName, equipEffect);

            this.highlightComponent(cardHtmlId); // highlight the card just investigated

            // UPDATE THE EQUIPMENT LIST
            var equipmentHtmlId = 'equipment_list_item_'+collectorNumber; // equipment html ID in the equipment list
            if(playerIdDrawing == this.player_id)
            { // this is the player who discarded the card
                if(document.getElementById(equipmentHtmlId))
                { // equipment HTML node exists
                    dojo.addClass( equipmentHtmlId, 'used_equipment'); // dim the card
                }
            }
        },

        drawOpponentEquipmentCard: function(letterPositionOfPlayerDrawing, equipmentCardId)
        {
            var startHtmlId = 'equipment_deck';
            var destinationHtmlId = 'player_'+letterPositionOfPlayerDrawing+'_equipment_hand_holder';

            // put the back of an equipment card in this player's equipment card hand spot
            var cardDiv = this.placeOpponentEquipmentCard(letterPositionOfPlayerDrawing, equipmentCardId);

            var allEquipment = this.getAllOpponentEquipmentCards(letterPositionOfPlayerDrawing);
            for( var i in allEquipment )
            { // go through the cards we want to draw
                var card = allEquipment[i];

                var equipId = card['id'];
                var htmlId = "player_board_hand_equipment_" + letterPositionOfPlayerDrawing + "_item_"+equipId;

                this.highlightComponent(htmlId); // highlight the card just investigated
            }
        },

        getAllOpponentEquipmentCards: function(playerLetter)
        {
            switch(playerLetter)
            {
              case 'a':
              return this.handPlayerEquipmentA.getAllItems();
              break;
              case 'b':
              return this.handPlayerEquipmentB.getAllItems();
              break;
              case 'c':
              return this.handPlayerEquipmentC.getAllItems();
              break;
              case 'd':
              return this.handPlayerEquipmentD.getAllItems();
              break;
              case 'e':
              return this.handPlayerEquipmentE.getAllItems();
              break;
              case 'f':
              return this.handPlayerEquipmentF.getAllItems();
              break;
              case 'g':
              return this.handPlayerEquipmentG.getAllItems();
              break;
              case 'h':
              return this.handPlayerEquipmentH.getAllItems();
              break;
            }
        },

        convertIsHiddenToText: function(isHidden, affectedByDisguise, affectedBySurveillanceCamera)
        {
            var isHiddenText = _("Revealed");
            if(isHidden == 1)
            { // card is hidden
                isHiddenText = _("Hidden");

                if(affectedByDisguise)
                {
                    isHiddenText += _(" (Disguise)");
                }

                if(affectedBySurveillanceCamera)
                {
                    isHiddenText += _(" (Surveillance Camera)");
                }
            }
            return isHiddenText;
        },

        convertCardPositionToText: function(positionInt)
        {
            var positionText = _("Unknown");
            switch(positionInt)
            {
                case "1":
                case 1:
                  positionText = _("Left");
                break;
                case "2":
                case 2:
                  positionText = _("Middle");
                break;
                case "3":
                case 3:
                  positionText = _("Right");
                break;
            }

            return positionText;
        },

        convertPlayersSeenToText: function(playersSeenListUntranslated)
        {
            if(playersSeenListUntranslated == "All")
            {
                return _("All"); // just translate it
            }
            else
            {
                return playersSeenListUntranslated; // just return the raw list since it's just a bunch of usernames
            }
        },

        convertWoundedToText: function(isWounded)
        {
            if(isWounded == null || isWounded == '' || isWounded == false || isWounded == 'false' || isWounded == 0 || isWounded == '0')
            {
              return _("No");
            }
            else
            {
                return _("Yes");
            }
        },

        convertBombToText: function(hasBombSymbol)
        {
            if(hasBombSymbol == null || hasBombSymbol == '' || hasBombSymbol == false || hasBombSymbol == 'false' || hasBombSymbol == 0 || hasBombSymbol == '0')
            {
                return _("No");
            }
            else if(hasBombSymbol == true || hasBombSymbol == 'true' || hasBombSymbol == 1 || hasBombSymbol == '1')
            {
                return _("Yes");
            }
            else
            {
                return _("Unknown");
            }
        },

        convertKnifeToText: function(hasKnifeSymbol)
        {
            if(hasKnifeSymbol == null || hasKnifeSymbol == '' || hasKnifeSymbol == false || hasKnifeSymbol == 'false' || hasKnifeSymbol == 0 || hasKnifeSymbol == '0')
            {
                return _("No");
            }
            else if(hasKnifeSymbol == true || hasKnifeSymbol == 'true' || hasKnifeSymbol == 1 || hasKnifeSymbol == '1')
            {
                return _("Yes");
            }
            else
            {
                return _("Unknown");
            }
        },

        convertCardTypeToText: function(cardType, affectedByPlantedEvidence)
        {
            cardType = cardType.toLowerCase();
            var cardTypeText = _("Unknown");
            if(cardType == "crooked")
            {
                cardTypeText = _("Crooked");

                if(affectedByPlantedEvidence)
                {
                    cardTypeText = _("Crooked (modified to Honest)");
                }
            }
            else if(cardType == "honest")
            {
                cardTypeText = _("Honest");

                if(affectedByPlantedEvidence)
                {
                    cardTypeText = _("Honest (modified to Crooked)");
                }
            }
            else if(cardType == "agent")
            {
                cardTypeText = _("Agent");
            }
            else if(cardType == "kingpin")
            {
                cardTypeText = _("Kingpin");
            }
            else if(cardType == "infector")
            {
                cardTypeText = _("Infector");
            }

            return cardTypeText;
        },


        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        onClickCancelButton: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            this.EXTRA_DESCRIPTION_TEXT = ''; // in case special instructions were given, clear them out

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickCancelButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedCancelButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_DoneSelectingButton: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickDoneSelectingButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedDoneSelectingButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_Investigate: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickInvestigateButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedInvestigateButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_Infect: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickInfectButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedInfectButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClickIntegrityCard: function( evt )
        { // a player clicked on an opponent's integrity card

            dojo.stopEvent( evt ); // Preventing default browser reaction

            var node = evt.currentTarget.id;
            var playerPosition = node.split('_')[1]; // b, c, d, etc.
            var cardPosition = node.split('_')[4]; // 1, 2, 3


            if(playerPosition == "a")
            { // clicked MY integrity card

                // Check that this action is possible (see "possibleactions" in states.inc.php)
                //if( !this.checkAction( 'clickMyIntegrityCard' ) )
                if( !this.checkPossibleActions('clickMyIntegrityCard'))
                { // we can't click this card now
                    this.showIntegrityCardDialog(playerPosition, cardPosition);
                }
                else
                { // we can click integrity cards

                    this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedMyIntegrityCard.html", {
                                                                            lock: true,
                                                                            cardPosition: cardPosition
                                                                         },
                                 this, function( result ) {

                                    // What to do after the server call if it succeeded
                                    // (most of the time: nothing)
                                    //this.highlightComponent(node);  // highlight the card
                                    this.selectComponent(node); // show this card is selected

                                 }, function( is_error) {

                                    // What to do after the server call in anyway (success or failure)
                                    // (most of the time: nothing)

                                 }
                    );
                }
            }
            else
            { // clicked OPPONENT integrity card

                // Check that this action is possible (see "possibleactions" in states.inc.php)
                //if( !this.checkAction( 'clickOpponentIntegrityCard' ) )
                if( !this.checkPossibleActions('clickOpponentIntegrityCard'))
                { // we cannot click this integrity card
                    this.showIntegrityCardDialog(playerPosition, cardPosition);
                }
                else
                { // we can click this integrity card

                    this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedOpponentIntegrityCard.html", {
                                                                            lock: true,
                                                                            playerPosition: playerPosition,
                                                                            cardPosition: cardPosition
                                                                         },
                                 this, function( result ) {

                                    // What to do after the server call if it succeeded
                                    // (most of the time: nothing)
                                    //this.highlightComponent(node);  // highlight the card
                                    this.selectComponent(node); // show this card is selected

                                 }, function( is_error) {

                                    // What to do after the server call in anyway (success or failure)
                                    // (most of the time: nothing)

                                 }
                    );
                }
            }
        },

        onClick_DiscardEquipment: function( evt )
        {

            var node = evt.currentTarget.id;
            var equipmentId = node.split('_')[2]; // a, b, c, d, etc.

            if(this.checkPossibleActions('clickEquipmentCard'))
            { // we are allowed to select cards based on our current state
                dojo.stopEvent( evt ); // Preventing default browser reaction

                this.highlightComponent(node); // highlight the card
                this.clickEquipmentCard(equipmentId, node);
            }
            else
            {
                this.showMessage( _("You cannot do anything with this right now."), 'error' );
                return;
            }
        },

        showIntegrityCardDialog: function(playerPosition, cardPosition)
        {
            if(this.gamedatas.playerLetters[this.player_id])
            { // not a spectator
                this.ajaxcall( "/goodcopbadcop/goodcopbadcop/getIntegrityCardDetails.html", {
                                                                        lock: true,
                                                                        playerPosition: playerPosition,
                                                                        cardPosition: cardPosition
                                                                     },
                             this, function( result )
                             { // What to do after the server call if it succeeded (most of the time: nothing)

    /*
                                // Create the new dialog over the play zone. You should store the handler in a member variable to access it later
                                this.myDlg = new ebg.popindialog();
                                this.myDlg.create( 'integrityDialog' );
                                //this.myDlg.setTitle( _("my dialog title to translate") );
                                this.myDlg.setMaxWidth( this.largeEquipmentCardWidth ); // Optional

                                // Create the HTML of my dialog.
                                // The best practice here is to use Javascript templates
                                var html = this.format_block( 'jstpl_listEquipment', {
                                              x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                                              y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                                              collectorNumber: collectorNumber,
                                              equipmentName: this.gamedatas.equipmentDetails[collectorNumber].equip_name,
                                              equipmentEffect: this.gamedatas.equipmentDetails[collectorNumber].equip_effect
                                          } );

                                // Show the dialog
                                this.myDlg.setContent( html ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
                                this.myDlg.show();
    */
                             }, function( is_error) {

                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)

                             }
                );
            }
        },

        showEquipmentDialog: function(collectorNumber)
        {
             // Create the new dialog over the play zone. You should store the handler in a member variable to access it later
             this.myDlg = new ebg.popindialog();
             this.myDlg.create( 'equipmentDialog' );
             //this.myDlg.setTitle( _("my dialog title to translate") );
             //this.myDlg.setMaxWidth( this.largeEquipmentCardWidth ); // Optional

             if(collectorNumber == "301" || collectorNumber == "302")
             { // Defibrillator
                collectorNumber = "3";
             }

             var equipmentNameTranslated = _(this.gamedatas.equipmentDetails[collectorNumber].equip_name);
             var equipmentEffectTranslated = _(this.gamedatas.equipmentDetails[collectorNumber].equip_effect);

             // Create the HTML of my dialog.
             // The best practice here is to use Javascript templates
             var html = this.format_block( 'jstpl_listEquipment', {
                           x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                           y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                           collectorNumber: collectorNumber,
                           equipmentName: equipmentNameTranslated,
                           equipmentEffect: equipmentEffectTranslated
                       } );

             // Show the dialog
             this.myDlg.setContent( html ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
             this.myDlg.show();
        },

        onClickPlayerBoardEquipmentCard: function( evt )
        {
            var node = evt.currentTarget.id;
            if(node)
            { // if node is defind
                var equipmentIdOrCollectorNumber = node.split('_')[6]; // the id of the equipment clicked in hand or board (active player equipment... player_board_active_equipment_f_item_8)
                var location = node.split('_')[2]; // hand or active

                if(!equipmentIdOrCollectorNumber)
                { // the equipment ID is not valid
                    equipmentIdOrCollectorNumber = node.split('_')[3]; // the id of the equipment clicked (active central equipment center_active_equipment_12 OR equipment reference equipment_list_item_6)
                }

                //if(this.checkPossibleActions('clickEquipmentCard'))
                if(location == 'hand' || this.checkPossibleActions('clickEquipmentCardToTarget'))
                { // we are targeting equipment with Evidence Bag

                    dojo.stopEvent( evt ); // Preventing default browser reaction

                    this.clickEquipmentCard(equipmentIdOrCollectorNumber, node);
                }
                else
                {
                    this.showEquipmentDialog(equipmentIdOrCollectorNumber); // show them a larger version of the equipment
                }
            }
        },

        clickToggle_EquipmentReactions: function( evt )
        {
            var node = evt.currentTarget.id;
            var isChecked = document.getElementById(node).checked;

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedToggle.html", {
                                                                        toggleHtmlId: node,
                                                                        isChecked: isChecked,
                                                                        lock: true
                                                                     },
                             this, function( result ) {

                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)



                             }, function( is_error) {

                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)

            } );
        },

        onClickReferenceEquipmentCard: function( evt )
        {
            var node = evt.currentTarget.id;
            if(node)
            { // if node is defind
                var collectorNumber = node.split('_')[6]; // the id of the equipment clicked in hand or board (active player equipment... player_board_active_equipment_f_item_8)

                if(!collectorNumber)
                { // the equipment ID is not valid
                    collectorNumber = node.split('_')[3]; // the id of the equipment clicked (active central equipment center_active_equipment_12 OR equipment reference equipment_list_item_6)
                }

                this.showEquipmentDialog(collectorNumber); // show them a larger version of the equipment
            }
        },

        onClickEquipmentCardInHand: function( evt )
        {
            var node = evt.currentTarget.id;
            if(node)
            { // if node is defind
                var equipmentIdOrCollectorNumber = node.split('_')[6]; // the id of the equipment clicked in hand or board (active player equipment... player_a_equipment_hand_holder_item_18)

                if(this.checkPossibleActions('clickEquipmentCard'))
                { // we are allowed to select cards based on our current state

                    dojo.stopEvent( evt ); // Preventing default browser reaction

                    this.clickEquipmentCard(equipmentIdOrCollectorNumber, node);
                }
                else
                {
                      this.showMessage( _("You cannot do anything with this right now."), 'error' );
                      return;
                }
            }
        },

        clickEquipmentCard: function( cardId, node )
        {
            var type = node.split('_')[2]; // hand or active

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedEquipmentCard.html", {
                                                                        cardId: cardId,
                                                                        equipmentType: type,
                                                                        lock: true
                                                                     },
                             this, function( result ) {

                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)



                             }, function( is_error) {

                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)

            } );

        },

        onClick_PauseToUseEquipment: function( evt )
        {
            if(this.checkPossibleActions('clickUseEquipmentButton'))
            { // we are allowed to select cards based on our current state
                dojo.stopEvent( evt ); // Preventing default browser reaction
                this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedUseEquipmentButton.html", {
                                                                        lock: true
                                                                     },
                             this, function( result ) {

                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)

                             }, function( is_error) {

                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)

                             } );
            }
            else
            {
                this.showMessage( _("You cannot use Equipment right now."), 'error' );
                return;
            }
        },

        // Clicked the Use Equipment button like "Use Metal Detector"
        onClick_UseEquipment: function( evt )
        {
            var node = evt.currentTarget.id;
            var equipmentId = node.split('_')[2]; // the equipment ID the player wants to use
            if(this.isCurrentPlayerActive() && this.checkPossibleActions('clickEquipmentCard'))
            { // we are allowed to select cards based on our current state
                dojo.stopEvent( evt ); // Preventing default browser reaction
                this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedEquipmentCard.html", {
                                                                        equipmentId: equipmentId,
                                                                        lock: true
                                                                     },
                             this, function( result ) {

                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)

                             }, function( is_error) {

                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)

                             } );
            }
            else
            {
                this.showMessage( _("You cannot do anything with this right now."), 'error' );
                return;
            }
        },

        // The player does not want to take an action on their turn.
        onClick_SkipMyTurn: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickSkipButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedSkipButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_Equip: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickEquipButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedEquipButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_Arm: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickArmButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedArmButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_Shoot: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickShootButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedShootButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClickLeaderButton: function( evt )
        {
            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickLeader' ) )
            {   return; }

            var node = evt.currentTarget.id;
            var leader = node.split('_')[1]; // Agent, Kingpin

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedLeader.html", {
                                                                    lock: true,
                                                                    leader: leader
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClickPlayerButton: function( evt )
        {
            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickPlayer' ) )
            {   return; }

            var node = evt.currentTarget.id;
            var letterAim = node.split('_')[2]; // a, b, c, d, etc.
            var player = node.split('_')[3]; // a, b, c, d, etc.

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedPlayer.html", {
                                                                    lock: true,
                                                                    letterAim: letterAim,
                                                                    player: player
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClickUseEquipmentButton: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickUseEquipmentButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedUseEquipmentButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_PassOnEquipmentUse: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickPassOnUseEquipmentButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/passOnUseEquipment.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClick_PassOnOption: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickPassOnOptionButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/passOnOption.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        onClickEndTurnButton: function( evt )
        {

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickEndTurnButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedEndTurnButton.html", {
                                                                    lock: true
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your goodcopbadcop.game.php file.

        */
        setupNotifications: function()
        {
            dojo.subscribe( 'newGameMessage', this, "notif_newGameMessage" ); // this won't actually be called since it happens in setup before notifications are setup
            dojo.subscribe( 'startGameDialogInfo', this, "notif_startGameDialogInfo");
            dojo.subscribe( 'viewCard', this, "notif_viewCard" );
            dojo.subscribe( 'gunPickedUp', this, "notif_gunPickedUp" );
            dojo.subscribe( 'gunAimed', this, "notif_gunAimed" );
            dojo.subscribe( 'shootAttempt', this, "notif_shootAttempt" );
            dojo.subscribe( 'executeGunShoot', this, "notif_executeGunShoot" );
            dojo.subscribe( 'dropGun', this, "notif_dropGun" );
            dojo.subscribe( 'revealIntegrityCard', this, "notif_revealIntegrityCard" );
            dojo.subscribe( 'eliminatePlayer', this, "notif_eliminatePlayer" );
            dojo.subscribe( 'playerWinsGame', this, "notif_playerWinsGame" );
            dojo.subscribe( 'revivePlayer', this, "notif_revivePlayer" );
            dojo.subscribe( 'woundPlayer', this, "notif_woundPlayer" );
            dojo.subscribe( 'removeWoundedToken', this, "notif_removeWoundedToken" );
            //dojo.subscribe( 'iDrawEquipmentCards', this, "notif_iDrawEquipmentCards" );
            //dojo.subscribe( 'otherPlayerDrawsEquipmentCards', this, "notif_otherPlayerDrawsEquipmentCards" );
            dojo.subscribe( 'revealEquipmentCard', this, "notif_revealEquipmentCard" );
            dojo.subscribe( 'discardEquipmentCard', this, "notif_discardEquipmentCard" );
            dojo.subscribe( 'discardActivePlayerEquipmentCard', this, "notif_discardActivePlayerEquipmentCard" );
            dojo.subscribe( 'activatePlayerEquipment', this, "notif_activatePlayerEquipment" );
            dojo.subscribe( 'handEquipmentCardExchanged', this, "notif_handEquipmentCardExchanged" );
            dojo.subscribe( 'activeEquipmentCardExchanged', this, "notif_activeEquipmentCardExchanged" );
            dojo.subscribe( 'integrityCardsExchanged', this, "notif_integrityCardsExchanged" );
            dojo.subscribe( 'investigationAttempt', this, "notif_investigationAttempt" );
            dojo.subscribe( 'endTurn', this, "notif_endTurn" );
            dojo.subscribe( 'startTurn', this, "notif_startTurn" );
            dojo.subscribe( 'updateTurnMarker', this, "notif_updateTurnMarker" );
            dojo.subscribe( 'investigationComplete', this, "notif_investigationComplete" );
            dojo.subscribe( 'iWasInvestigated', this, "notif_iWasInvestigated" );
            dojo.subscribe( 'playEquipment', this, "notif_playEquipment" );
            dojo.subscribe( 'playerDrawsEquipmentCard', this, "notif_playerDrawsEquipmentCard" );
            dojo.subscribe( 'iDrawEquipmentCard', this, "notif_iDrawEquipmentCard" );
            dojo.subscribe( 'targetIntegrityCard', this, "notif_targetIntegrityCard" );
            dojo.subscribe( 'cancelEquipmentUse', this, "notif_cancelEquipmentUse" );
            dojo.subscribe( 'reverseHonestCrooked', this, "notif_reverseHonestCrooked" );
            dojo.subscribe( 'integrityCardDetails', this, "notif_integrityCardDetails" );
            dojo.subscribe( 'rePlaceIntegrityCard', this, "notif_rePlaceIntegrityCard" );
            dojo.subscribe( 'playEquipmentOnTable', this, "notif_playEquipmentOnTable" );
            dojo.subscribe( 'equipmentDeckReshuffled', this, "notif_equipmentDeckReshuffled" );
            dojo.subscribe( 'destroyEquipmentDiscard', this, "notif_destroyEquipmentDiscard" );
            dojo.subscribe( 'highlightLastUsedIntegrityCard', this, "notif_highlightLastUsedIntegrityCard");

            // ZOMBIES
            dojo.subscribe( 'addInfectionToken', this, "notif_addInfectionToken" );
            dojo.subscribe( 'rolledInfectionDie', this, "notif_rolledInfectionDie" );
            dojo.subscribe( 'zombifyPlayer', this, "notif_zombifyPlayer" );
            dojo.subscribe( 'rolledZombieDie', this, "notif_rolledZombieDie" );
            dojo.subscribe( 'removeInfectionToken', this, "notif_removeInfectionToken" );
            dojo.subscribe( 'reRollingDice', this, "notif_reRollingDice" );
            dojo.subscribe( 'moveInfectionToken', this, "notif_moveInfectionToken" );
        },

        notif_newGameMessage: function( notif )
        {
            // this will never be received because it is sent during game setup

        },

        notif_startGameDialogInfo: function( notif )
        {
            // this will never be received because it is sent during game setup

            //console.log('notif_startGameDialogInfo');
            var teamName = notif.args.team;

        },

        notif_gunPickedUp: function( notif )
        {

            var gunId = notif.args.gun_id;
            var gunType = notif.args.gun_type; // gun or arms
            var playerArming = notif.args.player_arming;
            var letterOfPlayerWhoArmed = this.gamedatas.playerLetters[playerArming].player_letter;
            var heldByName = notif.args.player_name;
            var heldByNameHtml = "<span style=\"color:#" + this.gamedatas.player_colors[heldByName] + "\"><b>" + heldByName + "</b></span>";
            var aimedAtName = ''; // if we're just picking it up, it's not aimed yet

            // move gun to the player who armed
            var centerHolder = 'gun_deck';
            var gunToMoveHtmlId = 'gun_' + gunId; // get the HTML ID of the gun we want to move
            var destinationHtmlId = 'player_' + letterOfPlayerWhoArmed + '_gun_holder';

            this.placeGun(centerHolder, gunType, null, null, '', ''); // place gun in center holder (but don't specify any player who is holding it yet)
            this.attachToNewParent( gunToMoveHtmlId, destinationHtmlId ); // move this in the DOM to the new player's integrity card holder (must be done BEFORE sliding because it breaks all connections to it)
            this.addGunTooltip(gunToMoveHtmlId, heldByName, aimedAtName); // add tooltip (must be done after attached to new parent)
            var anim1 = this.slideToObject(gunToMoveHtmlId, destinationHtmlId, 500, 750);
            dojo.connect(anim1, 'onEnd', function(node)
            { // do the following after the animation ends

              dojo.addClass( gunToMoveHtmlId, 'gun_reset'); // keep the gun from moving when the window is resized
              this.highlightComponent(gunToMoveHtmlId); // highlight the gun that just moved
            });
            anim1.play();
        },

        notif_revealIntegrityCard: function( notif )
        {

            var integrityCardPositionRevealed = notif.args.card_position;
            var cardTypeRevealed = notif.args.card_type.toLowerCase();
            var revealerPlayerId = notif.args.revealer_player_id;
            var playerLetter = this.gamedatas.playerLetters[revealerPlayerId].player_letter;

            var playersSeen = _("All");
            var affectedByPlantedEvidence = notif.args.affectedByPlantedEvidence;
            var affectedByDisguise = notif.args.affectedByDisguise;
            var affectedBySurveillanceCamera = notif.args.affectedBySurveillanceCamera;

            var isWounded = notif.args.isWounded;

            var hasBombSymbol = notif.args.hasBombSymbol;
            var hasKnifeSymbol = notif.args.hasKnifeSymbol;


            // update the integrity card for this player to the seen version of it... should be in format -${x}px -${y}px
            var visibilityOffset = this.getVisibilityOffset('REVEALED'); // get sprite X value for this card type
            var cardTypeOffset = this.getCardTypeOffset(cardTypeRevealed); // get sprite Y value for this card type
            var integrityCardSpriteX = this.integrityCardWidth*(visibilityOffset);
            var integrityCardSpriteY = this.integrityCardHeight*(cardTypeOffset);
            var integrityCardRotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90
            var integrityCardHtmlId = "player_" + playerLetter + "_integrity_card_" + integrityCardPositionRevealed;
            dojo.style( integrityCardHtmlId, 'backgroundPosition', '-' + integrityCardSpriteX + 'px -' + integrityCardSpriteY + 'px' ); // update the integrity card for this player to the seen version of it... should be in format -${x}px -${y}px

            if(this.gamedatas.bombersTraitorsExpansion == 2)
            { // we are using the Bombers & Traitors expansion
                this.addBombAndKnifeSymbols(playerLetter, integrityCardHtmlId, integrityCardPositionRevealed, hasBombSymbol, hasKnifeSymbol, false, false); // we will do a rePlace right after this so we don't need to specify whether the player has seen 3 symbols
            }

            this.highlightComponent(integrityCardHtmlId); // highlight the card just investigated

            this.addIntegrityCardTooltip(integrityCardHtmlId, cardTypeRevealed, 0, playersSeen, integrityCardPositionRevealed, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol);
        },

        notif_highlightLastUsedIntegrityCard: function( notif )
        {
            var integrityCardPositionRevealed = notif.args.card_position;
            var ownerPlayerId = notif.args.owner_player_id;
            var playerLetter = this.gamedatas.playerLetters[ownerPlayerId].player_letter;

            var integrityCardHtmlId = "player_" + playerLetter + "_integrity_card_" + integrityCardPositionRevealed;

            this.highlightComponent(integrityCardHtmlId); // highlight the card just investigated

        },

        notif_targetIntegrityCard: function( notif )
        {
            var integrityCardId = notif.args.cardIdTargeted; // 1, 2, 3, 4, etc
            var integrityCardPosition = notif.args.cardPositionTargeted; // 1, 2, 3
            var playerIdTargeting = notif.args.playerIdWhoIsTargetingCard;
            var playerLetter = this.gamedatas.playerLetters[playerIdTargeting].player_letter;
            var descriptionText = notif.args.descriptionText;

            var cardTargetedHtmlId = "player_"+playerLetter+"_integrity_card_"+integrityCardPosition;

            this.EXTRA_DESCRIPTION_TEXT = descriptionText;

            if(integrityCardId != null && integrityCardPosition != null)
            {
                this.highlightComponent(cardTargetedHtmlId); // highlight the card just investigated
            }
        },

        notif_gunAimed: function( notif )
        {

            var gunId = notif.args.gunId; // 1, 2, 3, 4
            var degreesToRotate = notif.args.degreesToRotate; // 0, 85, -15, etc.
            var isPointingLeft = notif.args.isPointingLeft; // get how many cards over on the sprite this is (whether it is pointing left or right)
            var heldByName = notif.args.player_name;
            var nameOfGunHolder = notif.args.player_name;
            var nameOfGunTarget = notif.args.player_name_2;
            var heldByNameColored = "<span style=\"color:#"+ this.gamedatas.player_colors[nameOfGunHolder] + "\"><b>" + nameOfGunHolder + "</b></span>";
            var aimedAtName = "<span style=\"color:#" + this.gamedatas.player_colors[nameOfGunTarget] + "\"><b>" + nameOfGunTarget + "</b></span>";
            var aimedAtNameColored = notif.args.aimedAtNameColored;
            var gunHolderId = notif.args.gun_holder_id;
            var aimedAtId = notif.args.aimed_at_id;
            var gunType = notif.args.gun_type; // gun or arms

            var gunHolderLetter = this.gamedatas.playerLetters[gunHolderId].player_letter;
    				var aimedAtLetter = this.gamedatas.playerLetters[aimedAtId].player_letter; // get the player letter of who it is aimed at from this player's perspective

    				var degreesToRotate = this.gamedatas.gun_rotations[gunHolderLetter][aimedAtLetter]; // get how much the gun should be rotated based on player positions
    				var isPointingLeft = this.gamedatas.is_gun_pointing_left[gunHolderLetter][aimedAtLetter]; // check if the gun should be pointing left or right based on player positions and aim

            this.rotateGun(gunId, gunType, degreesToRotate, isPointingLeft); // switch to the left or right pointing image and rotate the gun to aim at the correct player

            var tokenExists = document.getElementById('gun_'+gunId); // see if this is a valid html id
            if(tokenExists)
            { // it exists
                this.highlightComponent('gun_'+gunId); // highlight the gun
                this.addGunTooltip('gun_'+gunId, heldByNameColored, aimedAtName);   // add tooltip
            }
        },

        notif_shootAttempt: function ( notif )
        {
            var gunId = notif.args.gunId; // 1, 2, 3, 4, etc

            this.highlightComponent('gun_'+gunId); // highlight the gun

        },

        notif_executeGunShoot: function( notif )
        {
            // we may not need to do anything here
        },

        notif_dropGun: function( notif )
        {
            var gunId = notif.args.gunId; // 1, 2, 3, 4
            var gunType = notif.args.gunType; // arm or gun
            var gunToMoveHtmlId = 'gun_' + gunId; // get the HTML ID of the gun we want to move
            var destinationHtmlId = 'gun_deck'; // the HTML ID of where we want to move the gun

            if(gunType == 'arm')
            { // we are dropping zombie arms
                destinationHtmlId = 'arm_deck'; // move to arm pile instead of gun pile
            }

            this.attachToNewParent( gunToMoveHtmlId, destinationHtmlId ); // move this in the DOM to the new player's integrity card holder (must be done BEFORE sliding because it breaks all connections to it)
            var anim1 = this.slideToObject(gunToMoveHtmlId, destinationHtmlId, 1000, 750);
            dojo.connect(anim1, 'onEnd', function(node)
            { // do the following after the animation ends
              this.highlightComponent(gunToMoveHtmlId); // highlight the gun that just moved
              $(gunToMoveHtmlId).style.removeProperty('transform'); // rotate the gun to 0
              if(gunType == 'arm')
              { // we are dropping zombie arms
                  dojo.style( gunToMoveHtmlId, 'backgroundPosition', '-0px -50px' ); // switch to the arm pointing right image
                  dojo.removeClass( gunToMoveHtmlId, 'gun_reset'); // to avoid guns getting messy, remove the class that resets its left and top
                  $(gunToMoveHtmlId).style.removeProperty('top'); // remove
                  $(gunToMoveHtmlId).style.removeProperty('left'); // remove
              }
              else
              { // we are dropping a gun
                  dojo.style( gunToMoveHtmlId, 'backgroundPosition', '-0px -0px' ); // switch to the gun pointing right image
                  dojo.removeClass( gunToMoveHtmlId, 'gun_reset'); // to avoid guns getting messy, remove the class that resets its left and top
                  $(gunToMoveHtmlId).style.removeProperty('top'); // remove
                  $(gunToMoveHtmlId).style.removeProperty('left'); // remove
              }
            });
            anim1.play();



            this.removeTooltip(gunToMoveHtmlId); // remove hover over

        },

        notif_eliminatePlayer: function( notif )
        {

            var eliminatedPlayerId = notif.args.eliminated_player_id;
            var letterOfPlayerWhoWasEliminated = this.gamedatas.playerLetters[eliminatedPlayerId].player_letter;

            this.eliminatePlayer(eliminatedPlayerId, letterOfPlayerWhoWasEliminated);
        },

        notif_zombifyPlayer: function( notif )
        {
            var playerId = notif.args.zombie_player_id;
            var letterOfPlayerWhoIsNowAZombie = this.gamedatas.playerLetters[playerId].player_letter;

            this.zombifyPlayer(playerId, letterOfPlayerWhoIsNowAZombie);
        },

        notif_revivePlayer: function( notif )
        {

            var eliminatedPlayerId = notif.args.eliminated_player_id;
            var letterOfPlayerWhoWasEliminated = this.gamedatas.playerLetters[eliminatedPlayerId].player_letter;

            this.revivePlayer(eliminatedPlayerId, letterOfPlayerWhoWasEliminated);
        },

        notif_playerWinsGame: function( notif )
        {
            var winnerPlayerId = notif.args.winner_player_id;


            //this.displayWinnerBorders(winnerPlayerId);
            this.giveWinnerMedal(winnerPlayerId);
        },

        notif_woundPlayer: function( notif )
        {

            var positionOfLeaderCard = notif.args.leader_card_position;
            var playerIdOfLeaderHolder = notif.args.player_id_of_leader_holder;
            var letterOfLeaderHolder = this.gamedatas.playerLetters[playerIdOfLeaderHolder].player_letter;
            var cardType = notif.args.card_type;

            this.placeAndMoveWoundedToken(letterOfLeaderHolder, positionOfLeaderCard, cardType); // put the token on the board
        },

        notif_removeWoundedToken: function( notif )
        {

            var woundedCardType = notif.args.woundedCardType; // who is wounded... kingpin or agent

            this.removeWoundedToken(woundedCardType); // put the token on the board
        },

        notif_integrityCardDetails: function( notif )
        {
            var playerId = notif.args.player_id;
            var cardPosition = notif.args.card_position;
            var playerLetter = this.gamedatas.playerLetters[playerId].player_letter;
            var cardType = notif.args.card_type;
  					var isSeen = notif.args.is_seen;
            var isHidden = notif.args.is_hidden;
            var seenByList = notif.args.seen_by_list;

            var isHiddenInt = 0;
            if(isHidden)
            {
                isHiddenInt = 1;
            }

            var affectedByPlantedEvidence = notif.args.affectedByPlantedEvidence;
            var affectedByDisguise = notif.args.affectedByDisguise;
            var affectedBySurveillanceCamera = notif.args.affectedBySurveillanceCamera;

            var isWounded = notif.args.isWounded;
            var hasBombSymbol = notif.args.hasBombSymbol;
            var hasKnifeSymbol = notif.args.hasKnifeSymbol;

            // Create the new dialog over the play zone. You should store the handler in a member variable to access it later
            this.myDlg = new ebg.popindialog();
            this.myDlg.create( 'integrityDialog' );
            //this.myDlg.setTitle( _("my dialog title to translate") );
            //this.myDlg.setMaxWidth( this.largeEquipmentCardWidth ); // Optional

            var typeLabel = _("Type:"); // separate out labels for translation
            var stateLabel = _("State:"); // separate out labels for translation
            var positionLabel = _("Position:"); // separate out labels for translation
            var seenByLabel = _("Seen By:"); // separate out labels for translation
            var woundedLabel = _("Wounded:");
            var bombLabel = _("Bomb:");
            var knifeLabel = _("Knife:");

            var isHiddenText = this.convertIsHiddenToText(isHiddenInt, affectedByDisguise, affectedBySurveillanceCamera); // convert whether it is hidden to a translated text
            var cardTypeText = this.convertCardTypeToText(cardType, affectedByPlantedEvidence); // convert the type of card to a translated version
            var positionText = this.convertCardPositionToText(cardPosition); // convert card position (1,2,3) to text (LEFT,MIDDLE,RIGHT)
            var woundedText = this.convertWoundedToText(isWounded);
            var bombText = this.convertBombToText(hasBombSymbol);
            var knifeText = this.convertKnifeToText(hasKnifeSymbol);

            var woundedLine = '';
            if(cardTypeText == 'Agent' || cardTypeText == 'Kingpin')
            { // this is a leader
                woundedLine = '<b>'+ woundedLabel + '</b> '+ woundedText;
            }

            var bombLine = '';
            var knifeLine = '';
            if(this.gamedatas.bombersTraitorsExpansion == 2)
            { // we are using the Bombers & Traitors expansion
                bombLine = '<b>'+ bombLabel + '</b> '+ bombText;
                knifeLine = '<b>'+ knifeLabel + '</b> '+ knifeText;
            }

            // Create the HTML of my dialog.
            // The best practice here is to use Javascript templates
            var html = this.format_block( 'jstpl_integrityDetails', {
                          type: '<b>'+typeLabel+'</b> '+cardTypeText,
                          state: '<b>'+stateLabel+'</b> '+isHiddenText,
                          position: '<b>'+positionLabel+'</b> '+positionText,
                          seenBy: '<b>'+seenByLabel+'</b> '+seenByList,
                          woundedLine: woundedLine,
                          bombLine: bombLine,
                          knifeLine: knifeLine
                      } );

            // Show the dialog
            this.myDlg.setContent( html ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
            this.myDlg.show();
        },

        notif_rePlaceIntegrityCard: function( notif )
        {
            var playerId = notif.args.player_id;
            var cardPosition = notif.args.card_position;
            var cardType = notif.args.cardType;

            var playersSeenArray = notif.args.playersSeenArray;
            var playersSeenList = notif.args.playersSeenList;
            var hasWound = notif.args.hasWound;

            var hasBombSymbol = notif.args.hasBombSymbol;
            var hasKnifeSymbol = notif.args.hasKnifeSymbol;
            var hasSeen3Bombs = notif.args.hasSeen3Bombs;
            var hasSeen3Knives = notif.args.hasSeen3Knives;

            var affectedByPlantedEvidence = notif.args.affectedByPlantedEvidence;
            var affectedByDisguise = notif.args.affectedByDisguise;
            var affectedBySurveillanceCamera = notif.args.affectedBySurveillanceCamera;

            var playerLetter = this.gamedatas.playerLetters[playerId].player_letter;
            var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

            var isHidden = notif.args.cardIsHidden; // true or false
            var isHiddenInt = 1;
            var hasPlayerSeen = 0; // default to not seen

            if(playersSeenArray[this.player_id])
            { // not a spectator
                hasPlayerSeen = playersSeenArray[this.player_id]; // 1 or 0
            }

            var cardVisibility = 'HIDDEN_NOT_SEEN';
            if(hasPlayerSeen == 1)
            { // this player has seen this card
                cardVisibility = 'HIDDEN_SEEN';
            }
            if(!isHidden)
            { // this card is revealed to everyone
                cardVisibility = 'REVEALED';
                isHiddenInt = 0;
            }

            var cardHtmlId = 'player_'+playerLetter+'_integrity_card_'+cardPosition;

            if(document.getElementById(cardHtmlId))
            { // it already exists
                dojo.destroy(cardHtmlId);
            }

            var idOfCard = this.placeIntegrityCard(playerLetter, cardPosition, cardVisibility, cardType, rotation, isHiddenInt, playersSeenList, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, hasWound, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives); // place a new one with correct hover over and such

            if(this.gamedatas.bombersTraitorsExpansion == 2)
            { // we are using the Bombers & Traitors expansion
                this.addBombAndKnifeSymbols(playerLetter, idOfCard, cardPosition, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives);
            }

            if(hasWound)
            { // this integrity card has a wound on it
                this.placeWoundedToken(playerLetter, cardPosition, cardType);
            }
        },

        notif_reverseHonestCrooked: function( notif )
        {

            var playerId = notif.args.player_id;
            var cardPosition = notif.args.card_position;
            var playerLetter = this.gamedatas.playerLetters[playerId].player_letter;
            var newCardType = notif.args.new_card_type;
            var isSeen = notif.args.is_seen;
            var isRevealed = notif.args.is_revealed;

            var cardHtmlId = 'player_'+playerLetter+'_integrity_card_'+cardPosition;

            if(newCardType == 'honest')
            {
                if(isRevealed == '1')
                {

                    dojo.style( cardHtmlId, 'backgroundPosition', '-1px -141px' ); // switch to HONEST image REVEALED
                }
                else if(isSeen == '1')
                {

                    dojo.style( cardHtmlId, 'backgroundPosition', '-51px -141px' ); // switch to HONEST image SEEN
                }
            }
            else if(newCardType == 'crooked')
            {
                if(isRevealed == '1')
                {

                    dojo.style( cardHtmlId, 'backgroundPosition', '-1px -71px' ); // switch to CROOKED image REVEALED
                }
                else if(isSeen == '1')
                {

                    dojo.style( cardHtmlId, 'backgroundPosition', '-51px -71px' ); // switch to CROOKED image SEEN
                }
            }
        },

        notif_rolledZombieDie: function( notif )
        {
            var rolledFace = notif.args.rolled_face;

            var dieRolled = notif.args.die_rolled; // 1, 2, 3

            var resultInt = parseInt(rolledFace, 10);
            resultInt += 1; // add one since our numbers go 6-11 but we want them to be 7-12
            resultInt -= 6; // subtract six since the dice expect 1-6

            this.rollDie('zombieDie'+dieRolled, 'zombieDie'+dieRolled+'Result', resultInt, 'zom'+dieRolled+'Rolled', 'zom'+dieRolled+'Num');

            dojo.place(
                    this.format_block( 'jstpl_integrityCardToken', {
                        cardType: 'bite',
                        x: 0,
                        y: 0
                    } ), 'wounded_tokens' );


            var movingTokenHtmlId = "integrity_token_bite";
            var destinationHtmlId = 'wounded_tokens';
            dojo.addClass("integrity_token_bite", "wounded_token"); // add the wounded token class
            dojo.style( 'integrity_token_bite', 'display', 'none' ); // hide the die
            var anim1 = this.slideToObject(movingTokenHtmlId, destinationHtmlId, 1000, 3000);
            /*dojo.connect(anim1, 'onEnd', function(node)
            { // do the following after the animation ends

              this.tableDice.addToStock( rolledFace );
              dojo.style( 'zombieDie'+dieRolled, 'display', 'none' ); // hide the die
            });
            anim1.play();
            */
            dojo.connect(anim1, 'onEnd', dojo.hitch(this, 'afterRollingAnimation', rolledFace));
            anim1.play();
        },
        afterRollingAnimation: function(param) {
            dojo.style( 'zombieDie1', 'display', 'none' ); // hide the die
            dojo.style( 'zombieDie2', 'display', 'none' ); // hide the die
            dojo.style( 'zombieDie3', 'display', 'none' ); // hide the die

            var randomNumber = Math.floor(Math.random() * 1000); // random number between 0-999

            //this.tableDice.addToStock( param );
            this.tableDice.addToStockWithId( param, randomNumber );

            var htmlId = 'dice_item_'+randomNumber;

            if(param == 6)
            { // zombie face was rolled
                if(document.getElementById(htmlId))
                {
                    this.addTooltip( htmlId, _('Causes the victim to turn into a zombie.'), '' ); // add a tooltip to explain
                }
            }

            if(param == 7)
            { // re-aim face was rolled
                if(document.getElementById(htmlId))
                {
                    this.addTooltip( htmlId, _('Causes the zombie to aim at someone else.'), '' ); // add a tooltip to explain
                }
            }

            if(param == 8)
            { // re-aim face was rolled
                if(document.getElementById(htmlId))
                {
                    this.addTooltip( htmlId, _('Causes the zombie to aim at someone else.'), '' ); // add a tooltip to explain
                }
            }

            if(param == 9)
            { // blank face was rolled
                if(document.getElementById(htmlId))
                {
                    this.addTooltip( htmlId, _('This has no effect.'), '' ); // add a tooltip to explain
                }
            }

            if(param == 10)
            { // blank face was rolled
                if(document.getElementById(htmlId))
                {
                    this.addTooltip( htmlId, _('This has no effect.'), '' ); // add a tooltip to explain
                }
            }

            if(param == 11)
            { // infection token face was rolled
                if(document.getElementById(htmlId))
                {
                    this.addTooltip( htmlId, _('Adds an Infection Token to an Integrity Card of the victim, if possible.'), '' ); // add a tooltip to explain
                }
            }

        },

        notif_rolledInfectionDie: function( notif )
        {
            var rolledFace = notif.args.rolled_face;
            //this.tableDice.addToStock( rolledFace );

            var resultInt = parseInt(rolledFace, 10);
            resultInt += 1; // add one since our numbers go 0-5 but the dice expect 1-6

            this.rollDie('infectionDie', 'infectionDieResult', resultInt, 'rolled', 'num');

            dojo.place(
                    this.format_block( 'jstpl_integrityCardToken', {
                        cardType: 'bite',
                        x: 0,
                        y: 0
                    } ), 'wounded_tokens' );


            var movingTokenHtmlId = "integrity_token_bite";
            var destinationHtmlId = 'player_boards';
            var anim1 = this.slideToObject(movingTokenHtmlId, destinationHtmlId, 750, 250);
            dojo.connect(anim1, 'onEnd', function(node)
            { // do the following after the animation ends
              this.tableDice.addToStock( rolledFace );
            });
            anim1.play();

        },

        notif_reRollingDice: function( notif )
        {
            this.tableDice.removeAll(); // clear the dice
        },

        notif_addInfectionToken: function( notif )
        {
            var positionOfInfectedCard = notif.args.card_position;
            var playerIdOfInfected = notif.args.player_id_of_infected;
            var playerLetterOfInfected = this.gamedatas.playerLetters[playerIdOfInfected].player_letter;
            var fromBite = notif.args.from_bite;
            var delay = 0; // the number of milliseconds to wait from when the infection token appears before it slides to the card

            if(fromBite)
            {
                  delay = 2000;
            }

            delay = 0; // this delay isn't worth it because sometimes you pause after the roll and sometimes you don't

            var tokenHtmlId = this.placeAndMoveInfectionToken(playerLetterOfInfected, positionOfInfectedCard, delay); // put the token on the integrity card
        },

        notif_removeInfectionToken: function( notif )
        {
            var playerIdRemoving = notif.args.player_id_removing; // the player ID of the player removing their token
            var cardPositionRemoving = notif.args.card_position_removing; // the card position (1,2,3) of the token being removed
            var playerLetterRemoving = this.gamedatas.playerLetters[playerIdRemoving].player_letter;

            this.removeInfectionToken(cardPositionRemoving+playerLetterRemoving); // remove it
        },

        notif_moveInfectionToken: function( notif )
        {
            var tokenPlayerId = notif.args.token_player_id;
            var tokenCardPosition = notif.args.token_card_position;
            var tokenPlayerLetter = this.gamedatas.playerLetters[tokenPlayerId].player_letter;

            var destinationPlayerId = notif.args.destination_player_id;
            var destinationCardPosition = notif.args.destination_card_position;
            var destinationPlayerLetter = this.gamedatas.playerLetters[destinationPlayerId].player_letter;


            // token 1
            var cardType = tokenCardPosition+''+tokenPlayerLetter;

            var movingTokenHtmlId = "integrity_token_"+cardType;
            var destinationHtmlId = 'player_'+destinationPlayerLetter+'_integrity_card_'+destinationCardPosition;

            //dojo.addClass(movingTokenHtmlId, "infection_token"); // add the infection token class (must be done before moving)

            this.slideToObjectAndDestroy(movingTokenHtmlId, destinationHtmlId, 500, 0); // slide it to its destination and destroy it

            var newHtmlId = this.placeInfectionToken(destinationPlayerLetter, destinationCardPosition); // place a new infection token so it has the correct id
        },

        notif_playerDrawsEquipmentCard: function( notif )
        {
            var equipmentId = notif.args.equipment_id;
            var playerIdDrawing = notif.args.drawing_player_id;
            var playerLetter = this.gamedatas.playerLetters[playerIdDrawing].player_letter;

            this.drawOpponentEquipmentCard(playerLetter, equipmentId); // draw an Equipment Card into an opponent's hand
        },

        notif_iDrawEquipmentCard: function( notif )
        {
            var equipName = notif.args.equip_name;
            var equipEffect = notif.args.equip_effect;
            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerIdDrawing = notif.args.drawing_player_id;

            this.drawEquipmentCard(equipmentId, collectorNumber, equipName, equipEffect, playerIdDrawing); // draw an Equipment Card into this player's hand
        },

        notif_cancelEquipmentUse: function( notif )
        {
            var equipmentId = notif.args.equipment_id;
            var playerId = notif.args.player_id;
            var playerLetter = this.gamedatas.playerLetters[playerId].player_letter;

            var cardHtmlId = 'player_' + playerLetter + '_hand_equipment_'+equipmentId;
            if(!document.getElementById(cardHtmlId))
            { // this html id doesn't exist
              cardHtmlId = 'player_board_hand_equipment_' + playerLetter + '_item_'+equipmentId;
            }

            dojo.style( cardHtmlId, 'backgroundPosition', '-0px -0px' ); // hide the card

            dojo.query( '.cardHighlighted' ).removeClass( 'cardHighlighted' ); // remove all card highlights
            dojo.query( '.cardSelected' ).removeClass( 'cardSelected' ); // remove selected card highlights
            dojo.query( '.equipmentTargetHighlighted' ).removeClass( 'equipmentTargetHighlighted' ); // remove target option highlights

        },

        notif_discardEquipmentCard: function( notif )
        {
            // if discarding from hand, do so secretly
            // if discarding because it was played, place it where everyone can see it

            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerIdDiscarding = notif.args.player_id_discarding;
            var playerLetter = this.gamedatas.playerLetters[playerIdDiscarding].player_letter;

            this.discardEquipmentFromHand(playerLetter, equipmentId, true, playerIdDiscarding, collectorNumber); // remove from player A hand and all player side board stocks

            var activeEquipmentHtmlId = "player_board_active_equipment_" + playerLetter + "_item_" + collectorNumber; // the id of where it would be if it were active on a player's board
            var activeElementExists = document.getElementById(activeEquipmentHtmlId); // see if this equipment was active on a player's board
            if(activeElementExists)
            { // this equipment is active on a player's board
                var destination = 'equipment_deck'; // the HTML ID of where we want to move it
                this.slideToObjectAndDestroy( activeEquipmentHtmlId, destination, 1000, 0 ); // slide it to its destination
            }
        },

        notif_revealEquipmentCard: function( notif )
        {
            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerIdDiscarding = notif.args.player_id;
            var playerLetter = this.gamedatas.playerLetters[playerIdDiscarding].player_letter;
            var equipName = notif.args.equipment_name;
            var equipEffect = notif.args.equipment_effect;

            this.revealEquipmentInHand( playerLetter, equipmentId, collectorNumber, equipName, equipEffect ); // remove from player hand and put face-up where active equipment goes
        },

        notif_playEquipmentOnTable: function( notif )
        {
            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerIdEquipmentOwner = notif.args.player_id_equipment_owner;
            var playerLetter = this.gamedatas.playerLetters[playerIdEquipmentOwner].player_letter;
            var equipName = notif.args.equipment_name;
            var equipEffect = notif.args.equipment_effect;

            this.playEquipmentCardFromHand(equipmentId, collectorNumber, playerLetter, equipName, equipEffect, playerIdEquipmentOwner);

            dojo.query( '.cardSelected' ).removeClass( 'cardSelected' ); // remove selected card highlights
            dojo.query( '.equipmentTargetHighlighted' ).removeClass( 'equipmentTargetHighlighted' ); // remove target option highlights
        },

        notif_discardActivePlayerEquipmentCard: function( notif )
        {
            var equipmentId = notif.args.equipment_id;
            var equipmentOwnerPlayerId = notif.args.equipment_card_owner;

            var playerLetter = this.gamedatas.playerLetters[equipmentOwnerPlayerId].player_letter;
            var collectorNumber = notif.args.collector_number;

            var equipName = notif.args.equipment_name;
            var equipEffect = notif.args.equipment_effect;

            var equipmentHtmlId = 'player_'+playerLetter+'_hand_equipment_'+equipmentId;

            this.discardEquipmentFromActive(playerLetter, collectorNumber, equipmentId, equipName, equipEffect ); // remove from giver player board
        },

        notif_activatePlayerEquipment: function( notif )
        {
            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerIdPlaying = notif.args.player_id_playing;
            var playerIdReceiving = notif.args.player_id_receiving;
            var playerLetterPlaying = this.gamedatas.playerLetters[playerIdPlaying].player_letter;
            var playerLetterReceiving = this.gamedatas.playerLetters[playerIdReceiving].player_letter;
            var rotation = this.getIntegrityCardRotation(playerLetterReceiving); // 0, 90, -90
            var numberOfActiveEquipmentNewPlayerHas = notif.args.count_active_equipment;

            var equipName = notif.args.equipment_name;
            var equipEffect = notif.args.equipment_effect;

            this.playActivePlayerEquipmentCardFromHand(equipmentId, collectorNumber, playerLetterPlaying, playerLetterReceiving, rotation, equipName, equipEffect, numberOfActiveEquipmentNewPlayerHas, playerIdPlaying);
        },

        notif_handEquipmentCardExchanged: function( notif )
        {
            var equipmentId = notif.args.equipment_id_moving;
            var collectorNumber = notif.args.collector_number;
            var playerIdGiving = notif.args.player_id_giving;
            var playerIdReceiving = notif.args.player_id_receiving;
            var playerLetterGiving = this.gamedatas.playerLetters[playerIdGiving].player_letter;
            var playerLetterReceiving = this.gamedatas.playerLetters[playerIdReceiving].player_letter;
            var equipName = notif.args.equipment_name;
            var equipEffect = notif.args.equipment_effect;

            var cardHtmlIdInGiverHand = 'player_'+playerLetterGiving+'_hand_equipment_'+equipmentId;

            this.discardEquipmentFromHand(playerLetterGiving, equipmentId, true, playerIdGiving, collectorNumber); // remove from giver player board


            this.addHandPlayerEquipmentToStock(playerLetterReceiving, 0, equipmentId); // add to receiver player board
            if(playerLetterReceiving == 'a')
            { // I am the player receiving the new card
                this.addMyHandPlayerEquipmentToStock(collectorNumber, equipmentId, equipName, equipEffect);

                var equipmentHtmlIdReceiver = 'equipment_list_item_'+collectorNumber; // equipment html ID in the equipment list

                    if(document.getElementById(equipmentHtmlIdReceiver))
                    { // equipment HTML node exists
                        dojo.addClass( equipmentHtmlIdReceiver, 'used_equipment'); // dim the card
                    }


            }

            // UPDATE THE EQUIPMENT LIST
            var equipmentHtmlId = 'equipment_list_item_'+collectorNumber; // equipment html ID in the equipment list
            if(playerIdGiving == this.player_id)
            { // this is the player who gave the card
                if(document.getElementById(equipmentHtmlId))
                { // equipment HTML node exists
                    dojo.removeClass( equipmentHtmlId, 'used_equipment'); // un-dim the card
                }
            }
        },

        notif_equipmentDeckReshuffled: function( notif )
        {

            var allEquipment = notif.args.allEquipment;

            this.resetEquipmentList(allEquipment);
        },

        notif_activeEquipmentCardExchanged: function( notif )
        {
            var equipmentId = notif.args.equipment_id_moving;
            var collectorNumber = notif.args.collector_number;
            var playerIdGiving = notif.args.player_id_giving;
            var playerIdReceiving = notif.args.player_id_receiving;
            var playerLetterGiving = this.gamedatas.playerLetters[playerIdGiving].player_letter;
            var playerLetterReceiving = this.gamedatas.playerLetters[playerIdReceiving].player_letter;
            var equipName = notif.args.equipment_name;
            var equipEffect = notif.args.equipment_effect;
            var numberOfActiveEquipmentNewPlayerHas = notif.args.count_active_equipment;

            this.discardEquipmentFromActive(playerLetterGiving, collectorNumber, equipmentId, equipName, equipEffect); // remove from giver player board
            this.addActivePlayerEquipmentToStock(playerLetterReceiving, collectorNumber, equipName, equipEffect); // add to player board
        },

        notif_integrityCardsExchanged: function( notif )
        {
            var card1Position = notif.args.card1OriginalPosition;
            var card2Position = notif.args.card2OriginalPosition;
            var playerId1 = notif.args.playerId1;
            var playerId2 = notif.args.playerId2;
            var playerLetter1 = this.gamedatas.playerLetters[playerId1].player_letter;
            var playerLetter2 = this.gamedatas.playerLetters[playerId2].player_letter;
            var card1Type = notif.args.card1Type; // agent, kingpin, honest, crooked
            var card2Type = notif.args.card2Type; // agent, kingpin, honest, crooked
            var card1Rotation = this.getIntegrityCardRotation(playerLetter1); // 0, 90, -90
            var card2Rotation = this.getIntegrityCardRotation(playerLetter2); // 0, 90, -90
            var card1PlayersSeen = notif.args.card1PlayersSeen;
            var card2PlayersSeen = notif.args.card2PlayersSeen;
            var card1SeenList = notif.args.card1SeenList; // [player_id][0],[player_id][1]
            var card2SeenList = notif.args.card2SeenList; // [player_id][0],[player_id][1]
            var card1Wounded = notif.args.card1Wounded;
            var card2Wounded = notif.args.card2Wounded;
            var card1Infected = notif.args.card1Infected;
            var card2Infected = notif.args.card2Infected;
            var card1HasBombSymbol = notif.args.card1HasBombSymbol;
            var card2HasBombSymbol = notif.args.card2HasBombSymbol;
            var card1HasKnifeSymbol = notif.args.card1HasKnifeSymbol;
            var card2HasKnifeSymbol = notif.args.card2HasKnifeSymbol;

            var card1affectedByPlantedEvidence = notif.args.card1affectedByPlantedEvidence;
            var card2affectedByPlantedEvidence = notif.args.card2affectedByPlantedEvidence;
            var card1affectedByDisguise = notif.args.card1affectedByDisguise;
            var card2affectedByDisguise = notif.args.card2affectedByDisguise;
            var card1affectedBySurveillanceCamera = notif.args.card1affectedBySurveillanceCamera;
            var card2affectedBySurveillanceCamera = notif.args.card2affectedBySurveillanceCamera;

            var card1IsHidden = notif.args.card1IsHidden; // true or false
            var card1IsHiddenInt = 1;
            var card1HasPlayerSeen = 0; // default to not seen
            if(card1SeenList[this.player_id])
            { // not a spectator
                card1HasPlayerSeen = card1SeenList[this.player_id]; // 1 or 0
            }

            var card1Visibility = 'HIDDEN_NOT_SEEN';
            if(card1HasPlayerSeen == 1)
            { // this player has seen this card
                card1Visibility = 'HIDDEN_SEEN';
            }
            if(card1IsHidden == false)
            { // this card is revealed to everyone
                card1Visibility = 'REVEALED';
                card1IsHiddenInt = 0;
            }

            var card2IsHidden = notif.args.card2IsHidden; // true or false
            var card2IsHiddenInt = 1;
            var card2HasPlayerSeen = 0; // default to not seen
            if(card2SeenList[this.player_id])
            { // not a spectator
                card2HasPlayerSeen = card2SeenList[this.player_id]; // 1 or 0
            }
            var card2Visibility = 'HIDDEN_NOT_SEEN';
            if(card2HasPlayerSeen == 1)
            { // this player has seen this card
                card2Visibility = 'HIDDEN_SEEN';
            }
            if(card2IsHidden == false)
            { // this card is revealed to everyone
                card2Visibility = 'REVEALED';
                card2IsHiddenInt = 0;
            }

            var card1HtmlId = "player_"+playerLetter1+"_integrity_card_"+card1Position; // integrity card 1
            var card1HolderHtmlId = "player_"+playerLetter1+"_integrity_card_"+card1Position+"_holder"; // original location of integrity card 1 (future location of integrity card 2)
            var card2HtmlId = "player_"+playerLetter2+"_integrity_card_"+card2Position; // integrity card 2
            var card2HolderHtmlId = "player_"+playerLetter2+"_integrity_card_"+card2Position+"_holder"; // original location of integrity card 2 (future location of integrity card 1)

            dojo.destroy(card1HtmlId); // destroy the old one because it has the id and position associated to the other player
            dojo.destroy(card2HtmlId); // destroy the old one because it has the id and position associated to the other player



            // CREATE FAKE CARDS, ANIMATE THEM, THEN DESTROY THEM
            var visibilityOffset = this.getVisibilityOffset('HIDDEN_NOT_SEEN'); // get sprite X value for this card type
            var cardTypeOffset = 0; // get sprite Y value for this card type

            dojo.place(
                    this.format_block( 'jstpl_integrityCard', {
                        x: this.integrityCardWidth*(visibilityOffset),
                        y: this.integrityCardHeight*(cardTypeOffset),
                        playerLetter: 'fake',
                        cardPosition: '1'
                    } ), card1HolderHtmlId );

            var card1Div = 'player_fake_integrity_card_1';
            this.rotateTo( card1Div, card1Rotation );

            dojo.place(
                    this.format_block( 'jstpl_integrityCard', {
                        x: this.integrityCardWidth*(visibilityOffset),
                        y: this.integrityCardHeight*(cardTypeOffset),
                        playerLetter: 'fake',
                        cardPosition: '2'
                    } ), card2HolderHtmlId );

            var card2Div = 'player_fake_integrity_card_2';
            this.rotateTo( card2Div, card2Rotation );

            this.slideToObjectAndDestroy( card1Div, card2HolderHtmlId, 700, 0 ); // slide it to its destination
            this.slideToObjectAndDestroy( card2Div, card1HolderHtmlId, 700, 0 ); // slide it to its destination



            // PLACE NEW INTEGRITY CARDS
            this.placeIntegrityCard(playerLetter1, card1Position, card1Visibility, card1Type, card1Rotation, card1IsHiddenInt, card1PlayersSeen, card1affectedByPlantedEvidence, card1affectedByDisguise, card1affectedBySurveillanceCamera, card1Wounded, card1HasBombSymbol, card1HasKnifeSymbol, true, true); // put a revealed card face-up
            this.placeIntegrityCard(playerLetter2, card2Position, card2Visibility, card2Type, card2Rotation, card2IsHiddenInt, card2PlayersSeen, card2affectedByPlantedEvidence, card2affectedByDisguise, card2affectedBySurveillanceCamera, card2Wounded, card2HasBombSymbol, card2HasKnifeSymbol, true, true); // put a revealed card face-up

            dojo.style(card1HtmlId, 'transform', 'rotate('+card1Rotation+'deg)');
            dojo.style(card2HtmlId, 'transform', 'rotate('+card2Rotation+'deg)');

            // WOUNDED TOKENS
            if(card1Wounded)
            {
                // place a wounded token
                var htmlOfWoundedToken1 = this.placeWoundedToken(playerLetter1, card1Position, card1Type); // put the token on the integrity card

                if(document.getElementById(htmlOfWoundedToken1) && card1Rotation)
                { // our data exists
                    dojo.style(htmlOfWoundedToken1, 'transform', 'rotate('+card1Rotation+'deg)'); // rotate wounded token
                }
            }

            if(card2Wounded)
            {
                // place the wounded token
                var htmlOfWoundedToken2 = this.placeWoundedToken(playerLetter2, card2Position, card2Type); // put the token on the integrity card

                if(document.getElementById(htmlOfWoundedToken2) && card2Rotation)
                { // our data exists
                    dojo.style(htmlOfWoundedToken2, 'transform', 'rotate('+card2Rotation+'deg)'); // rotate wounded token
                }
            }


            this.highlightComponent(card2HtmlId); // highlight the card
            this.highlightComponent(card1HtmlId); // highlight the card

            dojo.connect( $(card2HtmlId), 'onclick', this, 'onClickIntegrityCard' ); // re-add the onclick connection
            dojo.connect( $(card1HtmlId), 'onclick', this, 'onClickIntegrityCard' ); // re-add the onclick connection


            setTimeout(this.destroyFakes, 1000); // in case the fake cards didn't get destroyed as happens sometimes for unknown reasons... try again
        },

        destroyFakes: function()
        {
          var card1Div = 'player_fake_integrity_card_1';
          var fake1Exists = document.getElementById(card1Div); // see if this equipment was active on a player's board
          if(fake1Exists)
          {
              dojo.destroy(card1Div);
          }

          var card2Div = 'player_fake_integrity_card_2';
          var fake2Exists = document.getElementById(card2Div); // see if this equipment was active on a player's board
          if(fake2Exists)
          {
              dojo.destroy(card2Div);
          }
        },

        notif_viewCard: function( notif )
        {
            var playerIdInvestigated = notif.args.investigated_player_id;
            var playerLetter = this.gamedatas.playerLetters[playerIdInvestigated].player_letter;
            var cardPosition = notif.args.cardPosition;
            var cardType = notif.args.cardType.toLowerCase();

            var playersSeen = notif.args.playersSeen;
            var affectedByPlantedEvidence = notif.args.affectedByPlantedEvidence;
            var affectedByDisguise = notif.args.affectedByDisguise;
            var affectedBySurveillanceCamera = notif.args.affectedBySurveillanceCamera;

            var isWounded = notif.args.isWounded;

            var hasBombSymbol = notif.args.hasBombSymbol;
            var hasKnifeSymbol = notif.args.hasKnifeSymbol;
            var hasSeen3Bombs = notif.args.hasSeen3Bombs;
            var hasSeen3Knives = notif.args.hasSeen3Knives;

            var isHidden = notif.args.isHidden;
            var isHiddenInt = 0;
            var visibilityText = 'REVEALED'; // default it to being revealed
            if(isHidden)
            {
                isHiddenInt = 1;
                visibilityText = 'HIDDEN_SEEN'; // the most hidden this can be is HIDDEN_SEEN since it's my card
            }

            var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90
            var htmlId = "player_" + playerLetter + "_integrity_card_" + cardPosition;

            // figure out how many cards to the left and down this is within the sprite based on the card type and its current state
            visibilityOffset = this.getVisibilityOffset(visibilityText, affectedBySurveillanceCamera); // get sprite X value for this card type
            cardTypeOffset = this.getCardTypeOffset(cardType, affectedByPlantedEvidence); // get sprite Y value for this card type

            // multiply by the card size to get the X and Y coordinate within the sprite
            spriteX = this.integrityCardWidth*(visibilityOffset);
            spriteY = this.integrityCardHeight*(cardTypeOffset);

            // update the integrity card for this player to the seen version of it... should be in format -${x}px -${y}px
            dojo.style( htmlId, 'backgroundPosition', '-' + spriteX + 'px -' + spriteY + 'px' );
            if(this.gamedatas.bombersTraitorsExpansion == 2)
            { // we are using the Bombers & Traitors expansion
                this.addBombAndKnifeSymbols(playerLetter, htmlId, cardPosition, hasBombSymbol, hasKnifeSymbol, hasSeen3Bombs, hasSeen3Knives);
            }

            this.highlightComponent(htmlId); // highlight the card just investigated

            this.addIntegrityCardTooltip(htmlId, cardType, isHiddenInt, playersSeen, cardPosition, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol); // add tooltip to show who has seen this card
        },

        notif_investigationAttempt: function( notif )
        {
            var playerIdInvestigated = notif.args.player_id_investigated;
            var playerLetterOfPlayerInvestigated = this.gamedatas.playerLetters[playerIdInvestigated].player_letter;
            var cardPositionInvestigated = notif.args.card_position_targeted;

            var cardInvestigatedHtmlId = "player_" + playerLetterOfPlayerInvestigated + "_integrity_card_" + cardPositionInvestigated;
            this.highlightComponent(cardInvestigatedHtmlId); // highlight the card just investigated
        },

        notif_investigationComplete: function( notif )
        {
            var investigatedPlayerId = notif.args.investigated_player_id;
            var investigateePlayerLetter = this.gamedatas.playerLetters[investigatedPlayerId].player_letter;
            var cardPosition = notif.args.cardPosition;
            var cardType = notif.args.cardType;

            var isWounded = notif.args.isWounded;
            var hasBombSymbol = notif.args.hasBombSymbol;
            var hasKnifeSymbol = notif.args.hasKnifeSymbol;

            var playersSeen = notif.args.playersSeen;
            var affectedByPlantedEvidence = notif.args.affectedByPlantedEvidence;
            var affectedByDisguise = notif.args.affectedByDisguise;
            var affectedBySurveillanceCamera = notif.args.affectedBySurveillanceCamera;

            var isHidden = notif.args.isHidden;
            var isHiddenInt = 0;
            if(isHidden)
            {
                isHiddenInt = 1;
            }

            var htmlId = "player_" + investigateePlayerLetter + "_integrity_card_" + cardPosition;

            this.highlightComponent(htmlId); // highlight the card

            this.addIntegrityCardTooltip(htmlId, cardType, isHiddenInt, playersSeen, cardPosition, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol); // add tooltip to show who has seen this card
        },

        notif_iWasInvestigated: function( notif )
        {
            var investigatedPlayerId = notif.args.investigated_player_id;
            var investigateePlayerLetter = this.gamedatas.playerLetters[investigatedPlayerId].player_letter;
            var cardPosition = notif.args.cardPosition;
            var cardType = notif.args.cardTypeCamel;

            var isWounded = notif.args.isWounded;
            var hasBombSymbol = notif.args.hasBombSymbol;
            var hasKnifeSymbol = notif.args.hasKnifeSymbol;

            var playersSeen = notif.args.playersSeen;
            var affectedByPlantedEvidence = notif.args.affectedByPlantedEvidence;
            var affectedByDisguise = notif.args.affectedByDisguise;
            var affectedBySurveillanceCamera = notif.args.affectedBySurveillanceCamera;

            var isHidden = notif.args.isHidden;
            var isHiddenInt = 0;
            if(isHidden)
            {
                isHiddenInt = 1;
            }

            var htmlId = "player_" + investigateePlayerLetter + "_integrity_card_" + cardPosition;

            this.addIntegrityCardTooltip(htmlId, cardType, isHiddenInt, playersSeen, cardPosition, affectedByPlantedEvidence, affectedByDisguise, affectedBySurveillanceCamera, isWounded, hasBombSymbol, hasKnifeSymbol); // add tooltip to show who has seen this card
        },

        notif_endTurn: function( notif )
        {
            dojo.query( '.cardHighlighted' ).removeClass( 'cardHighlighted' ); // remove all card highlights
            dojo.query( '.cardSelected' ).removeClass( 'cardSelected' ); // remove selected card highlights
            dojo.query( '.equipmentTargetHighlighted' ).removeClass( 'equipmentTargetHighlighted' ); // remove target option highlights

            if(this.gamedatas.zombieExpansion == 2)
            { // we are using the zombies expansion
                  this.tableDice.removeAll(); // remove all dice
            }
        },

        notif_updateTurnMarker: function( notif )
        {
            var currentPlayer = notif.args.current_player_id;
            var isClockwise = notif.args.is_clockwise;
            var currentPlayerName = notif.args.current_player_name;
            var nextPlayerName = notif.args.next_player_name;
            this.placeCurrentTurnToken(currentPlayer, isClockwise, currentPlayerName, nextPlayerName);
        },

        notif_startTurn: function( notif )
        {
            var playerId = notif.args.new_player_id;
            var isClockwise = notif.args.is_clockwise;
            var currentPlayerName = notif.args.current_player_name;
            var nextPlayerName = notif.args.next_player_name;
            this.placeCurrentTurnToken(playerId, isClockwise, currentPlayerName, nextPlayerName);

/*
            var tokenId = 'current_player_token';


            var destination = 'player_board_' + playerId;
            //var destination = 'player_elo_' + playerId;

            this.attachToNewParent( tokenId, destination ); // move this in the DOM to the new player's integrity card holder (must be done BEFORE sliding because it breaks all connections to it)
            var anim1 = this.slideToObject(tokenId, destination, 1000, 750);
            dojo.connect(anim1, 'onEnd', function(node)
            { // do the following after the animation ends

              //$(gunToMoveHtmlId).style.removeProperty('transform'); // rotate the gun to 0

                  dojo.style( tokenId, 'left', '160px' ); // switch to the arm pointing right image
                  dojo.style( tokenId, 'top', '20px' ); // switch to the arm pointing right image

            });
            anim1.play();
*/
        },

        notif_playEquipment: function( notif )
        {
            // this is just here so the equipment name gets the hover over in the message log
            var playerIdPlaying = notif.args.player_id_playing_equipment;
            var playerLetter = this.gamedatas.playerLetters[playerIdPlaying].player_letter;

            var equipName = notif.args.equip_name;
            var equipEffect = notif.args.equip_effect;
            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var extraDescriptionText = notif.args.descriptionText;
            var revealCard = notif.args.reveal_card;

            if(extraDescriptionText != '')
            { // we want to give the player some more instructions for this equipment play
                this.EXTRA_DESCRIPTION_TEXT = extraDescriptionText;
            }

            var equipmentHtmlId = "player_board_hand_equipment_" + playerLetter + "_item_" + equipmentId; // the HTML ID of the card (card hidden)

            var htmlIdPlayerEquipmentHolder = "player_board_hand_equipment_"+playerLetter; // the HTML ID of the container for the card
            if(revealCard)
            { // we want to show which equipment card is being played

                this.discardEquipmentFromHand(playerLetter, equipmentId, false, playerIdPlaying, collectorNumber); // remove from all player side board stocks

                // place equipment card on the stock id
                dojo.place(
                        this.format_block( 'jstpl_equipmentInHand', {
                            x: this.equipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                            y: this.equipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                            equipmentId: equipmentId,
                            playerLetter: playerLetter
                        } ), htmlIdPlayerEquipmentHolder );

                equipmentHtmlId = "player_" + playerLetter + "_hand_equipment_" + equipmentId; // the HTML ID of the card (card revealed)

                this.addLargeEquipmentTooltip(equipmentHtmlId, collectorNumber, equipName, equipEffect); // add a hoverover tooltip with a bigger version of the card
            }
            this.highlightComponent(equipmentHtmlId); // highlight the card
        },

        notif_destroyEquipmentDiscard: function( notif )
        {
            var collectorNumber = notif.args.collector_number;

            this.destroyEquipmentDiscard(collectorNumber);
        }
   });
});
