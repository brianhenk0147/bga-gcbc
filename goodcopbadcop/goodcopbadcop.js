/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * goodcopbadcop implementation : © <Your name here> <Your email address here>
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
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.goodcopbadcop", ebg.core.gamegui, {
        constructor: function(){
            console.log('goodcopbadcop constructor');

            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

            this.integrityCardWidth = 50;
            this.integrityCardHeight = 70;

            this.equipmentCardWidth = 50;
            this.equipmentCardHeight = 70;
            this.largeEquipmentCardWidth = 400;
            this.largeEquipmentCardHeight = 560;

            this.gunCardWidth = 70;
            this.gunCardHeight = 50;

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
            console.log( "Starting game setup" );

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];

                // TODO: Setting up players boards if needed
            }

            // TODO: Set up your game interface here, according to "gamedatas"

            // put all revealed cards out
            for( var i in this.gamedatas.revealedCards )
            {
                var card = this.gamedatas.revealedCards[i];
                console.log( "Revealed Card:" );
                console.log( card );
                console.log( "" );
                var playerLetter = card['player_position']; // a, b, c, etc.
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

                var cardPosition = card['card_location_arg']; // 1, 2, 3
                var cardType = card['card_type']; // kingpin, agent, honest, crooked

                this.placeIntegrityCard(playerLetter, cardPosition, 'REVEALED', cardType, rotation); // put a revealed card face-up
            }

            //hiddenCardsIHaveSeen
            for( var i in this.gamedatas.hiddenCardsIHaveSeen )
            {
                var card = this.gamedatas.hiddenCardsIHaveSeen[i];
                console.log( "Hidden Cards I Have Seen:" );
                console.log( card );
                console.log( "" );

                var playerLetter = card['player_position']; // a, b, c, etc.
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

                var cardPosition = card['card_location_arg']; // 1, 2, 3
                var cardType = card['card_type']; // kingpin, agent, honest, crooked

                this.placeIntegrityCard(playerLetter, cardPosition, 'HIDDEN_SEEN', cardType, rotation); // put a hidden card out so i can see what it is but it is clear it is not visible to everyone
            }

            //hiddenCardsIHaveNotSeen
            for( var i in this.gamedatas.hiddenCardsIHaveNotSeen )
            {
                var card = this.gamedatas.hiddenCardsIHaveNotSeen[i];
                console.log( "Hidden Cards I Have NOT Seen:" );
                console.log( card );
                console.log( "" );
                var playerLetter = card['player_position']; // a, b, c, etc.
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

                var cardPosition = card['card_location_arg']; // 1, 2, 3
                var cardType = 'unknown';

                this.placeIntegrityCard(playerLetter, cardPosition, 'HIDDEN_NOT_SEEN', cardType, rotation); // put a face-down integrity card out
            }

            for( var gun_id in gamedatas.guns )
            {
                var gun = gamedatas.guns[gun_id];
                var gunId = gun['gun_id'];
                var heldByPlayerId = gun['playerIdHeldBy'];
                var heldByLetterOrder = gun['letterPositionHeldBy'];
                var aimedAtPlayerId = gun['playerIdAimedAt'];
                var aimedAtLetterOrder = gun['letterPositionAimedAt'];

                console.log( "Gun:" );
                console.log( gun );
                console.log( "" );

                this.placeGun(gunId, heldByLetterOrder, aimedAtLetterOrder);

            }

            for( var gun_id in gamedatas.gunRotations )
            {
                var gun = gamedatas.gunRotations[gun_id];
                var gunId = gun['gun_id'];
                var rotation = gun['rotation'];
                var isPointingLeft = gun['is_pointing_left']; // 1 if pointing LEFT or 0 if pointing RIGHT

                console.log( "Gun Rotation:" );
                console.log( gun );
                console.log( "" );

                this.rotateGun(gunId, rotation, isPointingLeft);
            }

            for( var i in gamedatas.woundedTokens )
            {
                var wound = gamedatas.woundedTokens[i];
                var woundedPlayerLetterOrder = wound['woundedPlayerLetterOrder'];
                var leaderCardPosition = wound['leaderCardPosition']; // 1, 2, 3
                var cardType = wound['cardType']; // agent or kingpin

                console.log( "Wounded Token:" );
                console.log( wound );
                console.log( "" );

                this.placeWoundedToken(woundedPlayerLetterOrder, leaderCardPosition, cardType); // put the token on the integrity card
            }

            // my equipment cards
            for( var i in gamedatas.myEquipmentCards )
            {
                var myEquipmentCards = gamedatas.myEquipmentCards[i];
                var collectorNumber = myEquipmentCards['card_type_arg'];
                var equipmentCardId = myEquipmentCards['card_id'];

                var cardHtmlId = this.placeMyEquipmentCard(equipmentCardId, collectorNumber, null);
            }

            // opponent equipment cards
            for( var i in gamedatas.opponentEquipmentCards )
            {
                var playerEquipmentCards = gamedatas.opponentEquipmentCards[i];
                var player_id = playerEquipmentCards['player_id'];
                var playerLetterOrder = playerEquipmentCards['playerLetterOrder']; // a, b, c
                var equipmentCardId = playerEquipmentCards['equipmentCardIds']; // the number of cards this player has

                console.log( "Player Equipment Cards:" );
                console.log( playerEquipmentCards );
                console.log( "" );

                this.placeOpponentEquipmentCard(playerLetterOrder, equipmentCardId); // put this card out
            }

            // active SHARED equipment cards
            console.log( "Center Active Equipment Cards" );
            for( var i in gamedatas.sharedActiveEquimentCards )
            {
                var activeEquipmentCard = gamedatas.sharedActiveEquimentCards[i];
                var collectorNumber = activeEquipmentCard['card_type_arg']; // collector number
                var equipmentId = activeEquipmentCard['card_id']; // equipment ID
                var playerLetter = activeEquipmentCard['playerLetterOrder'];

                console.log( "Active Equipment Card:" );
                console.log( activeEquipmentCard );
                console.log( "" );

                this.placeActiveCentralEquipmentCard(equipmentId, collectorNumber, playerLetter); // place an equipment card in the center of the table
            }

            // active PLAYER equipment cards
            for( var i in gamedatas.playerActiveEquipmentCards )
            {
                var activeEquipmentCard = gamedatas.playerActiveEquipmentCards[i];
                var collectorNumber = activeEquipmentCard['collectorNumber']; // collector number
                var equipmentId = activeEquipmentCard['equipmentCardIds']; // equipment ID
                var playerLetter = activeEquipmentCard['playerLetterOrder'];
                var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90

                console.log( "Active Equipment Card:" );
                console.log( activeEquipmentCard );
                console.log( "" );

                this.placeActivePlayerEquipmentCard(equipmentId, collectorNumber, playerLetter, rotation); // place an equipment card in the center of the table
            }
console.log("eliminated players ");
            // eliminate players
            for( var i in gamedatas.eliminatedPlayers )
            {
                console.log("eliminated player " + i);
                var eliminatedPlayer = gamedatas.eliminatedPlayers[i];
                var eliminatedPlayerId = eliminatedPlayer['playerId']; // eliminated player ID
                var letterOfPlayerWhoWasEliminated = eliminatedPlayer['playerLetter']; // eliminated player letter for this player

                this.eliminatePlayer(eliminatedPlayerId, letterOfPlayerWhoWasEliminated); // gray out eliminated players
            }

            // First Param: css class to target
            // Second Param: type of events
            // Third Param: the method that will be called when the event defined by the second parameter happen
            this.addEventToClass( "opponent_integrity_card_slot", "onclick", "onClickOpponentIntegrityCard" );
            this.addEventToClass( "my_integrity_card_slot", "onclick", "onClickMyIntegrityCard" );
            //this.addEventToClass( "hand_equipment_card", "onclick", "onClickEquipmentCard" );

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );

            switch( stateName )
            {

            /* Example:

            case 'myGameState':

                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );

                break;
           */


            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );

            switch( stateName )
            {

            /* Example:

            case 'myGameState':

                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );

                break;
           */


            case 'dummmy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );

            if( this.isCurrentPlayerActive() )
            {
                switch( stateName )
                {
/*
                 Example:

                 case 'myGameState':

                    // Add 3 action buttons in the action status bar:

                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' );
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' );
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' );
                    break;
*/
                    case 'playerTurn':
                        //this.addActionButton( 'button_investigate', _('Investigate'), 'onClickInvestigateButton' ); // as long as there is at least one hidden card
                        //this.addActionButton( 'button_equip', _('Equip'), 'onClickEquipButton' ); // always show
                        //this.addActionButton( 'button_arm', _('Arm'), 'onClickArmButton' ); // always show but disable if no guns available
                        //this.addActionButton( 'button_shoot', _('Shoot'), 'onClickShootButton' ); // only show if holding a gun
                        //this.addActionButton( 'button_useEquipment', _('Use Equipment'), 'onClickUseEquipmentButton' ); // show one for each equipment held
                        //this.addActionButton( 'button_passOnTurn', _('Pass'), 'onClickPassOnTurnButton' ); // always show
                        //array=[buttonId,buttonText, isDisabled, hoverOverText, onClickMethod]
                        //button_shoot, 'Shoot', true, 'Arm yourself with a Gun before you can Shoot.', onClickShoot
                        //button_useEquipment_EQID, 'Use Metal Detector', false, '', onClick_useEquipment
                        var buttonList = args.buttonList;
                        console.log("buttonList:");
                        console.log(buttonList);

                        /*
                        console.log("keys:")
                        const keys = Object.keys(buttonList);
                        for (const key of keys) {
                        console.log(key);
                        }
                        */
                        const buttonKeys = Object.keys(buttonList);
                        for (const buttonKey of buttonKeys)
                        { // go through each player
                            //console.log("buttonKey:" + buttonKey);

                            var buttonLabel = buttonList[buttonKey]['buttonLabel'];
                            var isDisabled = buttonList[buttonKey]['isDisabled'];
                            var hoverOverText = buttonList[buttonKey]['hoverOverText']; // hover over text or '' if we don't want a hover over
                            var actionName = buttonList[buttonKey]['actionName']; // shoot, useEquipment
                            var equipmentId = buttonList[buttonKey]['equipmentId'];  // only used for equipment to specify which equipment in case of more than one in hand
                            var makeRed = buttonList[buttonKey]['makeRed'];
                            //console.log("buttonLabel:" + buttonLabel);

                            var buttonId = 'button_' + actionName;
                            //console.log("buttonId:" + buttonId);
                            if(equipmentId && equipmentId != '')
                            {
                                buttonId += '_' + equipmentId; // add on the equipment ID if this is an equipment we are using
                            }

                            var clickMethod = 'onClick_' + actionName;
                            //console.log("clickMethod:" + clickMethod);
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

                    case 'chooseEquipmentCardInAnyHand':
                    case 'chooseCardToInvestigate':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton' );
                    break;

                    case 'chooseCardToRevealForArm':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton' );
                    break;

                    case 'choosePlayer':
                    case 'askAimOutOfTurn':
                    case 'askAim':
                        //this.addActionButton( 'button_endTurn', _('End Turn'), 'onClickEndTurnButton' );

                        // get a list of all players, their name, and their letter from my perpsective, if possible
                        var validPlayers = args.validPlayers;
                    console.log("validPlayers:");
                    console.log(validPlayers);

/*
                    console.log("keys:")
                    const keys = Object.keys(allPlayers);
                    for (const key of keys) {
                      console.log(key);
                    }
*/
                    const players = Object.keys(validPlayers);
                    for (const playerKey of players)
                    { // go through each player
                        var owner = playerKey;
                        console.log("owner:" + owner);

                        var name = validPlayers[playerKey]['player_name'];
                        var letterPosition = validPlayers[playerKey]['player_letter'];
                        this.addActionButton( 'button_aimAt_' + letterPosition + '_' + owner, _(name), 'onClickPlayerButton' );
                    }

                        // button for each player
                    break;

                    case 'askInvestigateReaction':
                    case 'askShootReaction':
                    case 'askEndTurnReaction':

                        this.addActionButton( 'button_PauseToUseEquipment', _('Use Equipment or Pause'), 'onClick_PauseToUseEquipment' );
                        this.addActionButton( 'button_PassOnUseEquipment', _('Pass'), 'onClick_PassOnEquipmentUse', null, false, 'red' );

                        //this.addTooltip( 'button_useEquipment', _('Pause the timer and consider using equipment.'), '' ); // add a tooltip to explain

                    break;

                    case 'chooseIntegrityCards':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton' );
                    break;

                    case 'chooseEquipmentToPlayReactInvestigate':
                    case 'chooseEquipmentToPlayReactShoot':
                    case 'chooseEquipmentToPlayReactEndOfTurn':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton', null, false, 'red' );
                    break;

                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */

        // PLAY actice cards to the center area FROM HAND.
        playActiveCentralEquipmentCardFromHand: function(equipmentId, collectorNumber, playerLetter)
        {
            var equipmentHtmlId = "player_" + playerLetter + "_hand_equipment_" + equipmentId; // the HTML ID of the card we want to move
            var targetActiveEquipmentHolderHtmlId = 'active_equipment_center_holder'; // the HTML ID of where we want to move it

            // move the new equipment from hand to the active area in front of the target player
            console.log("sliding from " + equipmentHtmlId + " to " + targetActiveEquipmentHolderHtmlId);
            this.attachToNewParent( equipmentHtmlId, targetActiveEquipmentHolderHtmlId ); // move this in the DOM to the new player's active equipment holder (must be done BEFORE sliding because it breaks all connections to it)
            this.slideToObject( equipmentHtmlId, targetActiveEquipmentHolderHtmlId, 1000, 0 ).play(); // slide it to its destination

            this.rotateTo( equipmentHtmlId, rotation ); // rotate it depending on who it's now in front of

            // reveal the card to everyone... should be in format -${x}px -${y}px
            var spriteX = this.equipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber));
            var spriteY = this.equipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber));
            dojo.style( equipmentHtmlId, 'backgroundPosition', '-' + spriteX + 'px -' + spriteY + 'px' );

            this.addLargeEquipmentTooltip(equipmentHtmlId, collectorNumber); // add a large version of the equipment whenever you hover over it
            dojo.connect( $(equipmentHtmlId), 'onclick', this, 'onClickEquipmentCard' ); // re-add the onclick connection (since the attachToNewParent broke it)
        },

        // PLACE active card in center area.
        placeActiveCentralEquipmentCard: function(equipmentId, collectorNumber, playerLetter)
        {
            var htmlIdCenterHolder = "active_equipment_center_holder"; // the HTML ID of the container for the card
            var equipmentHtmlId = "center_active_equipment_" + equipmentId; // the HTML ID of the card

            //dojo.addClass( equipmentHtmlId, 'center_equipment_card' ); // add the class

            dojo.place(
                    this.format_block( 'jstpl_activeCenterEquipment', {
                        x: this.equipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                        y: this.equipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                        equipmentId: equipmentId
                    } ), htmlIdCenterHolder );

            // add a hoverover tooltip with a bigger version of the card
            var html = this.format_block( 'jstpl_largeEquipment', {
                x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber))
            } ); // the HTML (image) to be displayed
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( equipmentHtmlId, html, delay ); // add the tooltip with the above configuration
            //dojo.addClass( nodeId, 'my_equipment_card' ); // add the class

            dojo.connect( $(equipmentHtmlId), 'onclick', this, 'onClickEquipmentCard' ); // re-add the onclick connection
        },

        playActivePlayerEquipmentCardFromHand: function(equipmentId, collectorNumber, playerLetterPlaying, playerLetterReceiving, rotation)
        {
            var equipmentHtmlId = "player_" + playerLetterPlaying + "_hand_equipment_" + equipmentId; // the HTML ID of the card we want to move (it's the same for player A and other players)
            var targetActiveEquipmentHolderHtmlId = "player_" + playerLetterReceiving + "_first_equipment_active_holder"; // use the player position letter to move the card in the equipment player's hand to the target player's active equipment spot

            // move the new equipment from hand to the active area in front of the target player
            console.log("sliding from " + equipmentHtmlId + " to " + targetActiveEquipmentHolderHtmlId);
            //this.slideToObjectAndDestroy( equipmentHtmlIdInHand, targetActiveEquipmentHolderHtmlId, 1000, 0 ); // slide it to its destination
            this.attachToNewParent( equipmentHtmlId, targetActiveEquipmentHolderHtmlId ); // move this in the DOM to the new player's active equipment holder (must be done BEFORE sliding because it breaks all connections to it)
            this.slideToObject( equipmentHtmlId, targetActiveEquipmentHolderHtmlId, 1000, 0 ).play(); // slide it to its destination
            //this.placeActivePlayerEquipmentCard(equipmentId, collectorNumber, playerLetterReceiving, rotation); // place the new card in the target's active area

            this.rotateTo( equipmentHtmlId, rotation ); // rotate it depending on who it's now in front of

            // reveal the card to everyone... should be in format -${x}px -${y}px
            var spriteX = this.equipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber));
            var spriteY = this.equipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber));
            dojo.style( equipmentHtmlId, 'backgroundPosition', '-' + spriteX + 'px -' + spriteY + 'px' );

            this.addLargeEquipmentTooltip(equipmentHtmlId, collectorNumber); // add a large version of the equipment whenever you hover over it
            dojo.connect( $(equipmentHtmlId), 'onclick', this, 'onClickEquipmentCard' ); // re-add the onclick connection (since the attachToNewParent broke it)
        },

        placeActivePlayerEquipmentCard: function(equipmentId, collectorNumber, playerLetter, rotation)
        {
            var targetActiveEquipmentHolderHtmlId = "player_" + playerLetter + "_first_equipment_active_holder"; // use the player position letter to move the card in the equipment player's hand to the target player's active equipment spot

            // create a new equipment face-up in hand
            console.log( "Placing this equipment card in div " + targetActiveEquipmentHolderHtmlId );
            dojo.place(
                    this.format_block( 'jstpl_activeEquipment', {
                        x: this.equipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                        y: this.equipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                        playerLetter: playerLetter,
                        equipmentId: equipmentId
                    } ), targetActiveEquipmentHolderHtmlId );

            var newCardHtmlId = 'player_'+playerLetter+'_active_equipment_'+equipmentId;
            this.rotateTo( newCardHtmlId, rotation );

            // add a hoverover tooltip with a bigger version of the card
            var html = this.format_block( 'jstpl_largeEquipment', {
                x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber))
            } ); // the HTML (image) to be displayed
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( newCardHtmlId, html, delay ); // add the tooltip with the above configuration
            //dojo.addClass( nodeId, 'my_equipment_card' ); // add the class
        },

        addLargeEquipmentTooltip(htmlIdToAddItTo, collectorNumber)
        {
            // add a hoverover tooltip with a bigger version of the card
            var html = this.format_block( 'jstpl_largeEquipment', {
                x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber))
            } ); // the HTML (image) to be displayed
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( htmlIdToAddItTo, html, delay ); // add the tooltip with the above configuration
        },

        // cardHolderDiv = the div in which we should place this card (it might move right afterwards)
        placeMyEquipmentCard: function(equipmentCardId, collectorNumber, cardHolderDiv)
        {
            var playerLetter = 'a';

            if(!cardHolderDiv)
            { // we did not pass in an argument for where the card should be placed
                cardHolderDiv  = 'player_'+playerLetter+'_equipment_hand_holder';
            }

            console.log( "Placing this equipment card with collector number " + collectorNumber + " in div " + cardHolderDiv );
            dojo.place(
                    this.format_block( 'jstpl_equipmentInHand', {
                        x: this.equipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                        y: this.equipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber)),
                        playerLetter: playerLetter,
                        equipmentId: equipmentCardId
                    } ), cardHolderDiv );

            var cardDiv = 'player_'+playerLetter+'_hand_equipment_'+equipmentCardId;
            var rotation = 0;
            this.rotateTo( cardDiv, rotation );
            console.log("Adding onclick event to div:" + cardDiv);
            dojo.connect( $(cardDiv), 'onclick', this, 'onClickEquipmentCard' );
            //this.addEventToClass( "hand_equipment_card", "onclick", "onClickEquipmentCard" );

            // add a hoverover tooltip with a bigger version of the card
            var html = this.format_block( 'jstpl_largeEquipment', {
                x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber))
            } ); // the HTML (image) to be displayed
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( cardDiv, html, delay ); // add the tooltip with the above configuration

            return cardDiv; // return the HTML ID of the card so we can slide it after placement
        },

        placeOpponentEquipmentCard: function(playerLetterOrder, equipmentId)
        {
            var rotation = this.getIntegrityCardRotation(playerLetterOrder);
            console.log( "Entering placeOpponentEquipmentCard with playerLetterOrder " + playerLetterOrder + " and equipmentId " + equipmentId + " and rotation " + rotation + "." );

            var opponentEquipmentHolderHtmlId = "player_" + playerLetterOrder + "_equipment_hand_holder"; // use the player position letter to move the card in the equipment player's hand to the target player's active equipment spot

            dojo.place(
                    this.format_block( 'jstpl_equipmentInHand', {
                        x: this.equipmentCardWidth*(0),
                        y: this.equipmentCardHeight*(0),
                        playerLetter: playerLetterOrder,
                        equipmentId: equipmentId
                    } ), opponentEquipmentHolderHtmlId );

            var cardHtmlId = 'player_'+playerLetterOrder+'_hand_equipment_'+equipmentId;

            this.rotateTo( cardHtmlId, rotation );
            dojo.addClass( cardHtmlId, 'opponent_equipment_card_horizontal' ); // add the class
            dojo.connect( $(cardHtmlId), 'onclick', this, 'onClickEquipmentCard' );
        },

        placeIntegrityCard: function(playerLetter, cardPosition, visibilityToYou, cardType, rotation)
        {
            console.log( "Entering placeIntegrityCard with playerLetter " + playerLetter + " cardPosition " + cardPosition + " visibilityToYou " + visibilityToYou + " cardType " + cardType + "." );

            var visibilityOffset = this.getVisibilityOffset(visibilityToYou); // get sprite X value for this card type
            var cardTypeOffset = this.getCardTypeOffset(cardType); // get sprite Y value for this card type

            var cardHolderDiv = 'player_'+playerLetter+'_integrity_card_'+cardPosition+'_holder';
            console.log( "Placing this integrity card in div " + cardHolderDiv );
            dojo.place(
                    this.format_block( 'jstpl_integrityCard', {
                        x: this.integrityCardWidth*(visibilityOffset),
                        y: this.integrityCardHeight*(cardTypeOffset),
                        playerLetter: playerLetter,
                        cardPosition: cardPosition
                    } ), cardHolderDiv );

            var cardDiv = 'player_'+playerLetter+'_integrity_card_'+cardPosition;
            this.rotateTo( cardDiv, rotation );

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

        getCardTypeOffset: function( cardType )
        {
            cardTypeOffset = 0;
            if(cardType == 'crooked')
            {
                cardTypeOffset = 1;
            }
            else if(cardType == 'honest')
            {
                cardTypeOffset = 2;
            }
            else if(cardType == 'kingpin')
            {
                cardTypeOffset = 3;
            }

            return cardTypeOffset;
        },

        getVisibilityOffset: function( visibilityToYou )
        {
            var visibilityOffset = 0;
            if(visibilityToYou == 'HIDDEN_NOT_SEEN')
            {
                visibilityOffset = 2;
            }
            else if (visibilityToYou == 'HIDDEN_SEEN')
            {
                visibilityOffset = 1;
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
            }
        },

        placeGun: function(gunId, heldByLetterOrder, aimedAtLetterOrder)
        {
            console.log( "Entering placeGun with gunId " + gunId + " heldBy " + heldByLetterOrder + " aimedAt " + aimedAtLetterOrder + "." );

            var gunHolderDiv = 'gun_' + gunId + '_holder'; // assume the gun is in the middle of the table

            if(heldByLetterOrder != null && heldByLetterOrder != '')
            { // the gun is being held by a player rather than in the middle of the table
                gunHolderDiv = 'player_'+heldByLetterOrder+'_gun_holder'; // put the gun in front of the player holding it
            }

            console.log( "Placing with gunId " + gunId + " gunHolderDiv " + gunHolderDiv + "." );
            dojo.place(
                    this.format_block( 'jstpl_gun', {
                        gunId: gunId,
                        x: 0,
                        y: 0
                    } ), gunHolderDiv );
        },

        rotateGun: function(gunId, rotation, isPointingLeft)
        {
            console.log( "" );
            console.log( "rotateGun Info:" );
            console.log( "gunId: " + gunId );
            console.log( "rotation: " + rotation );
            console.log( "isPointingLeft: " + isPointingLeft );
            console.log( "" );

            if(rotation === null)
            {
                rotation = 0;
            }

            var gunDiv = 'gun_' + gunId ; // the html ID of the gun
            var gunSpriteX = this.gunCardWidth*(isPointingLeft); // set the X position in the sprite to point at the LEFT or RIGHT pointing gun
            var gunSpriteY = 0;
            dojo.style( gunDiv, 'backgroundPosition', '-' + gunSpriteX + 'px -' + gunSpriteY + 'px' ); // switch the gun to use the correct LEFT or RIGHT pointing image

            this.rotateTo( gunDiv, rotation ); // rotate the gun
        },

        placeWoundedToken: function(woundedPlayerLetterOrder, leaderCardPosition, cardType)
        {
            var htmlIdOfLeaderCard = 'player_' + woundedPlayerLetterOrder + '_integrity_card_' + leaderCardPosition;

            dojo.place(
                    this.format_block( 'jstpl_wounded', {
                        cardType: cardType
                    } ), htmlIdOfLeaderCard );
        },

        removeWoundedToken: function(woundedCardId)
        {
            var woundedTokenHtml = 'wounded_token_' + woundedCardId;
            var destination = 'gun_2_holder';

            console.log( "slide " + woundedTokenHtml + " to " + destination );

            this.slideToObjectAndDestroy( woundedTokenHtml, destination, 1000, 750 ); // slide it to its destination
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

            dojo.removeClass( htmlIdOfPlayerEliminatedArea, eliminatedClass ); // add style to show this player is eliminated on the player's mat
            dojo.removeClass( htmlIdOfRightPlayerBoardId, eliminatedClass ); // add style to show this player is eliminated on the right player board
        },

        drawEquipmentCard: function(equipmentCardId, collectorNumber)
        {
            console.log( "Drawing equipment with equipment ID " + equipmentCardId + " and collector number " + collectorNumber + "." );
            var playerLetterOrder = 'a';

            var startHtmlId = 'player_boards';
            var destinationHtmlId = 'player_'+playerLetterOrder+'_equipment_hand_holder';
/*
            dojo.place(
                    this.format_block( 'jstpl_movingEquipmentCard', {
                        x: this.equipmentCardWidth*(0),
                        y: this.equipmentCardHeight*(0)
                    } ), startHtmlId );

            var cardHtmlId = 'moving_equipment_card';
*/
            var cardHtmlId = this.placeMyEquipmentCard(equipmentCardId, collectorNumber, startHtmlId);

            console.log("slide cardHtmlId:" + cardHtmlId + " to destinationHtmlId:" + destinationHtmlId);

            //this.slideToObjectAndDestroy( cardHtmlId, destinationHtmlId, 500, 0 ); // slide it to its destination
            this.slideToObject( cardHtmlId, destinationHtmlId, 500, 0 ).play(); // slide it to its destination


        },

        drawOpponentEquipmentCard: function(letterPositionOfPlayerDrawing, equipmentCardId)
        {
            var startHtmlId = 'player_boards';
            var destinationHtmlId = 'player_'+letterPositionOfPlayerDrawing+'_equipment_hand_holder';

            dojo.place(
                    this.format_block( 'jstpl_movingEquipmentCard', {
                        x: this.equipmentCardWidth*(0),
                        y: this.equipmentCardHeight*(0)
                    } ), startHtmlId );

            var cardHtmlId = 'moving_equipment_card';

            this.slideToObjectAndDestroy( cardHtmlId, destinationHtmlId, 1000, 0 ); // slide it to its destination

            // put the back of an equipment card in this player's equipment card hand spot
            this.placeOpponentEquipmentCard(letterPositionOfPlayerDrawing, equipmentCardId);
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

        /* Example:

        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/myAction.html", {
                                                                    lock: true,
                                                                    myArgument1: arg1,
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        */

        onClickCancelButton: function( evt )
        {
            console.log( 'onClickCancelButton' );

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickCancelAction' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedCancelActionButton.html", {
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
            console.log( 'onClick_Investigate' );

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

        onClickOpponentIntegrityCard: function( evt )
        { // a player clicked on an opponent's integrity card
            console.log('clicked OPPONENT integrity card iscurrentplayeractive() ' + this.isCurrentPlayerActive());

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickOpponentIntegrityCard' ) )
            {   return; }

            var node = evt.currentTarget.id;
            var playerPosition = node.split('_')[1]; // b, c, d, etc.
            var cardPosition = node.split('_')[4]; // 1, 2, 3

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedOpponentIntegrityCard.html", {
                                                                    lock: true,
                                                                    playerPosition: playerPosition,
                                                                    cardPosition: cardPosition
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         }
            );
        },

        onClickMyIntegrityCard: function( evt )
        { // a player clicked on their own integrity card
            console.log('clicked MY integrity card iscurrentplayeractive() ' + this.isCurrentPlayerActive());

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickMyIntegrityCard' ) )
            {   return; }

            var node = evt.currentTarget.id;
            var playerPosition = node.split('_')[1]; // b, c, d, etc.
            var cardPosition = node.split('_')[4]; // 1, 2, 3

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedMyIntegrityCard.html", {
                                                                    lock: true,
                                                                    cardPosition: cardPosition
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         }
            );
        },

        onClickEquipmentCard: function( evt )
        {
            var node = evt.currentTarget.id;
            var equipmentId = node.split('_')[4]; // a, b, c, d, etc.
            console.log( "eqcard clicked:"+equipmentId);
            if(this.checkPossibleActions('clickEquipmentCard'))
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

        onClick_PauseToUseEquipment: function( evt )
        {
            console.log('entered onClick_PauseToUseEquipment');
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
            console.log( "eqcard clicked:"+equipmentId);
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
        onClick_PassOnTurn: function( evt )
        {
            console.log( 'onClick_PassOnTurn' );
        },

        onClick_Equip: function( evt )
        {
            console.log( 'onClick_Equip' );

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
            console.log( 'onClick_Arm' );

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
            console.log( 'onClick_Shoot' );

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

        onClickPlayerButton: function( evt )
        {
            console.log( 'onClickPlayerButton' );

            dojo.stopEvent( evt ); // Preventing default browser reaction

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickPlayer' ) )
            {   return; }

            var node = evt.currentTarget.id;
            var letterAim = node.split('_')[2]; // a, b, c, d, etc.
            var player = node.split('_')[3]; // a, b, c, d, etc.
            console.log("aiming at player " + letterAim);

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
            console.log( 'onClickUseEquipmentButton' );

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
            console.log( 'onClick_PassOnEquipmentUse' );

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

        onClickEndTurnButton: function( evt )
        {
            console.log( 'onClickEndTurnButton' );

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
            console.log( 'notifications subscriptions setup' );

            // TODO: here, associate your game notifications with local methods

            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //

            dojo.subscribe( 'newGameMessage', this, "notif_newGameMessage" ); // this won't actually be called since it happens in setup before notifications are setup
            dojo.subscribe( 'viewCard', this, "notif_viewCard" );
            dojo.subscribe( 'gunPickedUp', this, "notif_gunPickedUp" );
            dojo.subscribe( 'gunAimed', this, "notif_gunAimed" );
            dojo.subscribe( 'shootAttempt', this, "notif_shootAttempt" );
            dojo.subscribe( 'executeGunShoot', this, "notif_executeGunShoot" );
            dojo.subscribe( 'dropGun', this, "notif_dropGun" );
            dojo.subscribe( 'revealIntegrityCard', this, "notif_revealIntegrityCard" );
            dojo.subscribe( 'eliminatePlayer', this, "notif_eliminatePlayer" );
            dojo.subscribe( 'revivePlayer', this, "notif_revivePlayer" );
            dojo.subscribe( 'woundPlayer', this, "notif_woundPlayer" );
            dojo.subscribe( 'removeWoundedToken', this, "notif_removeWoundedToken" );
            dojo.subscribe( 'iDrawEquipmentCards', this, "notif_iDrawEquipmentCards" );
            dojo.subscribe( 'otherPlayerDrawsEquipmentCards', this, "notif_otherPlayerDrawsEquipmentCards" );
            dojo.subscribe( 'discardEquipmentCard', this, "notif_discardEquipmentCard" );
            dojo.subscribe( 'discardActivePlayerEquipmentCard', this, "notif_discardActivePlayerEquipmentCard" );
            dojo.subscribe( 'activateCentralEquipment', this, "notif_activateCentralEquipment" );
            dojo.subscribe( 'activatePlayerEquipment', this, "notif_activatePlayerEquipment" );
            dojo.subscribe( 'equipmentCardExchanged', this, "notif_equipmentCardExchanged" );
            dojo.subscribe( 'integrityCardsExchanged', this, "notif_integrityCardsExchanged" );
        },

        // TODO: from this point and below, you can write your game notifications handling methods

        /*
        Example:

        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );

            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call

            // TODO: play the card in the user interface.
        },

        */

        notif_newGameMessage: function( notif )
        {
            console.log( 'notif_newGameMessage' );
            console.log( notif );
        },

        notif_viewCard: function( notif )
        {
            console.log("Entered notif_viewCard.");

            var playerLetter = notif.args.playerLetter;
            var cardPosition = notif.args.cardPosition;
            var cardType = notif.args.cardType;

            console.log( "Seen Card:" );
            console.log( "Player Letter: " + playerLetter );
            console.log( "Card Position: " + cardPosition );
            console.log( "Card Type: " + cardType );
            console.log( "" );

            var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90
            var htmlId = "player_" + playerLetter + "_integrity_card_" + cardPosition;

            // figure out how many cards to the left and down this is within the sprite based on the card type and its current state
            visibilityOffset = this.getVisibilityOffset('HIDDEN_SEEN'); // get sprite X value for this card type
            cardTypeOffset = this.getCardTypeOffset(cardType); // get sprite Y value for this card type

            // multiply by the card size to get the X and Y coordinate within the sprite
            spriteX = this.integrityCardWidth*(visibilityOffset);
            spriteY = this.integrityCardHeight*(cardTypeOffset);

            // update the integrity card for this player to the seen version of it... should be in format -${x}px -${y}px
            dojo.style( htmlId, 'backgroundPosition', '-' + spriteX + 'px -' + spriteY + 'px' );
        },


        notif_gunPickedUp: function( notif )
        {
            console.log("Entered notif_gunPickedUp.");

            var gunId = notif.args.gunId;
            var playerArming = notif.args.playerArming;
            var letterOfPlayerWhoArmed = notif.args.letterOfPlayerWhoArmed;

            console.log( "Arm Info:" );
            console.log( "Gun ID: " + gunId );
            console.log( "Player ID: " + playerArming );
            console.log( "" );

            // move gun to the player who armed
            var centerHolder = 'gun_' + gunId + '_holder';
            var gunToMoveHtmlId = 'gun_' + gunId; // get the HTML ID of the gun we want to move
            var destinationHtmlId = 'player_' + letterOfPlayerWhoArmed + '_gun_holder';

            this.slideToObject( gunToMoveHtmlId, destinationHtmlId, 1000, 750 ).play(); // slide it to its destination
        },

        notif_revealIntegrityCard: function( notif )
        {
            console.log("Entered notif_revealIntegrityCard.");

            var integrityCardPositionRevealed = notif.args.card_position;
            var cardTypeRevealed = notif.args.card_type;
            var playerLetter = notif.args.player_letter;

            console.log( "Reveal Info:" );
            console.log( "Player Letter: " + playerLetter );
            console.log( "Card Type: " + cardTypeRevealed );
            console.log( "Integrity Card Position: " + integrityCardPositionRevealed );
            console.log( "" );

            // update the integrity card for this player to the seen version of it... should be in format -${x}px -${y}px
            var visibilityOffset = this.getVisibilityOffset('REVEALED'); // get sprite X value for this card type
            var cardTypeOffset = this.getCardTypeOffset(cardTypeRevealed); // get sprite Y value for this card type
            var integrityCardSpriteX = this.integrityCardWidth*(visibilityOffset);
            var integrityCardSpriteY = this.integrityCardHeight*(cardTypeOffset);
            var integrityCardRotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90
            var integrityCardHtmlId = "player_" + playerLetter + "_integrity_card_" + integrityCardPositionRevealed;
            dojo.style( integrityCardHtmlId, 'backgroundPosition', '-' + integrityCardSpriteX + 'px -' + integrityCardSpriteY + 'px' ); // update the integrity card for this player to the seen version of it... should be in format -${x}px -${y}px
        },

        notif_gunAimed: function( notif )
        {
            console.log("Entered notif_gunAimed.");

            var gunId = notif.args.gunId; // 1, 2, 3, 4
            var degreesToRotate = notif.args.degreesToRotate; // 0, 85, -15, etc.
            var isPointingLeft = notif.args.isPointingLeft; // get how many cards over on the sprite this is (whether it is pointing left or right)

            this.rotateGun(gunId, degreesToRotate, isPointingLeft); // switch to the left or right pointing image and rotate the gun to aim at the correct player
        },

        notif_shootAttempt: function ( notif )
        {
            // we may not need to do anything here
        },

        notif_executeGunShoot: function( notif )
        {
            // we may not need to do anything here
        },

        notif_dropGun: function( notif )
        {
            console.log("Entered notif_dropGun.");

            var gunId = notif.args.gunId; // 1, 2, 3, 4

            // send the gun back to the middle
            var gunToMoveHtmlId = 'gun_' + gunId; // get the HTML ID of the gun we want to move
            var centerHolderHtmlId = 'gun_' + gunId + '_holder'; // the HTML ID of where we want to move the gun
            this.slideToObject( gunToMoveHtmlId, centerHolderHtmlId, 1000, 750 ).play(); // slide it to its destination

            $(gunToMoveHtmlId).style.removeProperty('transform'); // rotate the gun to 0
        },

        notif_eliminatePlayer: function( notif )
        {
            console.log("Entered notif_eliminatePlayer.");

            var eliminatedPlayerId = notif.args.eliminatedPlayerId;
            var letterOfPlayerWhoWasEliminated = notif.args.letterOfPlayerWhoWasEliminated;

            this.eliminatePlayer(eliminatedPlayerId, letterOfPlayerWhoWasEliminated);
        },

        notif_revivePlayer: function( notif )
        {
            console.log("Entered notif_revivePlayer.");

            var eliminatedPlayerId = notif.args.eliminatedPlayerId;
            var letterOfPlayerWhoWasEliminated = notif.args.letterOfPlayerWhoWasEliminated;

            this.revivePlayer(eliminatedPlayerId, letterOfPlayerWhoWasEliminated);
        },

        notif_woundPlayer: function( notif )
        {
            console.log("Entered notif_woundPlayer.");

            var positionOfLeaderCard = notif.args.leader_card_position;
            var letterOfLeaderHolder = notif.args.letter_of_leader_holder;
            var cardType = notif.args.card_type;

            this.placeWoundedToken(letterOfLeaderHolder, positionOfLeaderCard, cardType); // put the token on the board
        },

        notif_removeWoundedToken: function( notif )
        {
            console.log("Entered notif_removeWoundedToken.");

            var woundedCardId = notif.args.woundedCardId; // who is wounded... kingpin or agent

            this.removeWoundedToken(woundedCardId); // put the token on the board
        },

        notif_iDrawEquipmentCards: function( notif )
        {
            console.log("Entered notif_iDrawEquipmentCards.");
            console.log("cards:");
            console.log(cards);
            var cards = notif.args.cards_drawn;
            for( var i in cards )
            { // go through the cards we want to draw
                var card = cards[i];
                var collectorNumber = card.type_arg; // player number
                var equipmentId = card.id; // the equipment card id
                this.drawEquipmentCard(equipmentId, collectorNumber); // draw an Equipment Card into this player's hand
            }
        },

        notif_otherPlayerDrawsEquipmentCards: function( notif )
        {
            console.log("Entered notif_otherPlayerDrawsEquipmentCards.");
            var numberDrawn = notif.args.number_drawn; // number of equipment cards this player is drawing
            var cardId = notif.args.card_ids_drawn; // id of equipment card drawn
            var drawingPlayerId = notif.args.drawing_player_id;
            var drawingPlayerLetter = notif.args.drawing_player_letter; // the letter around the table of this player

            this.drawOpponentEquipmentCard(drawingPlayerLetter, cardId); // draw an Equipment Card into an opponent's hand
        },

        notif_discardEquipmentCard: function( notif )
        {
            console.log("Entered notif_discardEquipmentCard");

            var equipmentCardOwner = notif.args.equipment_card_owner;
            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerLetter = notif.args.player_letter;

            var equipmentHtmlId = "player_" + playerLetter + "_hand_equipment_" + equipmentId;

            var elementExists = document.getElementById(equipmentHtmlId);
            if(elementExists)
            { // this card exists
                //dojo.destroy( equipmentHtmlId ); // destroy it

                var destination = 'player_boards'; // the HTML ID of where we want to move it
                this.slideToObjectAndDestroy( equipmentHtmlId, destination, 1000, 0 ); // slide it to its destination
            }

        },

        notif_discardActivePlayerEquipmentCard: function( notif )
        {
            console.log("Entered notif_discardActivePlayerEquipmentCard");

            var equipmentId = notif.args.equipment_id;
            var playerLetter = notif.args.player_letter;

            var equipmentHtmlId = 'player_'+playerLetter+'_hand_equipment_'+equipmentId;

            var elementExists = document.getElementById(equipmentHtmlId);
            if(elementExists)
            { // this card exists
                //dojo.destroy(equipmentHtmlId); // destroy equipment card since it is discarded

                var destination = 'player_boards'; // the HTML ID of where we want to move it
                this.slideToObjectAndDestroy( equipmentHtmlId, destination, 1000, 0 ); // slide it to its destination
            }
        },

        notif_activateCentralEquipment: function( notif )
        {
            console.log("Entered notif_activateCentralEquipment");

            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerLetter = notif.args.player_letter;

            this.playActiveCentralEquipmentCardFromHand(equipmentId, collectorNumber, playerLetter);
        },

        notif_activatePlayerEquipment: function( notif )
        {
            console.log("Entered notif_activatePlayerEquipment");

            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerLetterPlaying = notif.args.player_letter_playing;
            var playerLetterReceiving = notif.args.player_letter_receiving;
            var rotation = this.getIntegrityCardRotation(playerLetterReceiving); // 0, 90, -90



            this.playActivePlayerEquipmentCardFromHand(equipmentId, collectorNumber, playerLetterPlaying, playerLetterReceiving, rotation);
        },

        notif_equipmentCardExchanged: function( notif )
        {
            console.log("Entered notif_equipmentCardExchanged");
            var equipmentId = notif.args.equipment_id_moving;
            var collectorNumber = notif.args.collector_number;
            var playerLetterGiving = notif.args.player_letter_giving;
            var playerLetterReceiving = notif.args.player_letter_receiving;

            var cardHtmlIdInGiverHand = 'player_'+playerLetterGiving+'_hand_equipment_'+equipmentId;
            dojo.destroy(cardHtmlIdInGiverHand);


            var startHolderHtmlId = 'player_'+playerLetterGiving+'_equipment_hand_holder'; // place where GIVING card should be
            dojo.place(
                    this.format_block( 'jstpl_equipmentInHand', {
                        x: this.equipmentCardWidth*(0),
                        y: this.equipmentCardHeight*(0),
                        playerLetter: playerLetterGiving,
                        equipmentId: equipmentId
                    } ), startHolderHtmlId );

            var cardHtmlId = 'player_'+playerLetterGiving+'_hand_equipment_'+equipmentId; // the HTML of the card moving
            var destinationHolderHtmlId = 'player_'+playerLetterReceiving+'_equipment_hand_holder'; // the place where the RECEIVER will hold the card

            this.slideToObjectAndDestroy( cardHtmlId, destinationHolderHtmlId, 1000, 0 ); // slide it to its destination

            if(playerLetterReceiving == 'a')
            {
                var cardHtmlId = this.placeMyEquipmentCard(equipmentId, collectorNumber, startHolderHtmlId);
            }
            else
            {
                this.placeOpponentEquipmentCard(playerLetterReceiving, equipmentId);
            }
        },

        notif_integrityCardsExchanged: function( notif )
        {
            console.log("Entered notif_integrityCardsExchanged");
            var card1Position = notif.args.card1OriginalPosition;
            var card2Position = notif.args.card2OriginalPosition;
            var playerLetter1 = notif.args.card1PlayerLetter;
            var playerLetter2 = notif.args.card2PlayerLetter;

            var card1HtmlId = "player_"+playerLetter1+"_integrity_card_"+card1Position; // integrity card 1
            var card1HolderHtmlId = "player_"+playerLetter1+"_integrity_card_"+card1Position+"_holder"; // original location of integrity card 1 (future location of integrity card 2)
            var card2HtmlId = "player_"+playerLetter2+"_integrity_card_"+card2Position; // integrity card 2
            var card2HolderHtmlId = "player_"+playerLetter2+"_integrity_card_"+card2Position+"_holder"; // original location of integrity card 2 (future location of integrity card 1)

            console.log("sliding from " + card1HtmlId + " to " + card2HolderHtmlId);
            this.slideToObject( card1HtmlId, card2HolderHtmlId, 1000, 750 ).play(); // slide it to its new destination
            console.log("sliding from " + card2HtmlId + " to " + card1HolderHtmlId);
            this.slideToObject( card2HtmlId, card1HolderHtmlId, 1000, 750 ).play(); // slide it to its new destination

            var rotation1 = this.getIntegrityCardRotation(playerLetter1); // 0, 90, -90
            var rotation2 = this.getIntegrityCardRotation(playerLetter2); // 0, 90, -90
            this.rotateTo( card2HtmlId, rotation1 ); // rotate it depending on who it's now in front of
            this.rotateTo( card1HtmlId, rotation2 ); // rotate it depending on who it's now in front of
        }

   });
});
