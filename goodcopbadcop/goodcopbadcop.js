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
    "ebg/counter",
    "ebg/stock"
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

            this.createEquipmentHands(); // create the stocks for all players' equipment hands

            // my equipment cards
            for( var i in gamedatas.myEquipmentCards )
            {
                var myEquipmentCards = gamedatas.myEquipmentCards[i];
                var collectorNumber = myEquipmentCards['card_type_arg'];
                var equipmentCardId = myEquipmentCards['card_id'];

                this.drawEquipmentCard(equipmentCardId, collectorNumber);
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

            // First Param: css class to target
            // Second Param: type of events
            // Third Param: the method that will be called when the event defined by the second parameter happen
            this.addEventToClass( "opponent_integrity_card_slot", "onclick", "onClickOpponentIntegrityCard" );
            this.addEventToClass( "my_integrity_card_slot", "onclick", "onClickMyIntegrityCard" );
            //this.addEventToClass( "my_equipment_card", "onclick", "onClickMyEquipmentCard" );

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
                        this.addActionButton( 'button_investigate', _('Investigate'), 'onClickInvestigateButton' );
                        this.addActionButton( 'button_equip', _('Equip'), 'onClickEquipButton' );
                        this.addActionButton( 'button_arm', _('Arm'), 'onClickArmButton' );
                        this.addActionButton( 'button_shoot', _('Shoot'), 'onClickShootButton' );
                        this.addActionButton( 'button_useEquipment', _('Use Equipment'), 'onClickUseEquipmentButton' );

                        if(false)
                        { // there are no guns available

                            // disable the Arm button (but still display it)

                            this.addTooltip( 'button_arm', _('No guns available.'), '' ); // add a tooltip to explain why it is disabled
                        }
                        else
                        { // there are guns available

                            // enable the Arm button

                            this.addTooltip( 'button_arm', _('Pick up a gun.'), '' );

                        }
                    break;

                    case 'chooseCardToInvestigate':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton' );
                    break;

                    case 'askInvestigateReaction':
                    case 'askShootReaction':
                        this.addActionButton( 'button_useEquipment', _('Use Equipment'), 'onClickUseEquipmentButton' );
                        this.addActionButton( 'button_passOnUseEquipment', _('Pass'), 'onClickPassOnUseEquipmentButton' );
                    break;

                    case 'chooseCardToRevealForArm':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton' );
                    break;

                    case 'choosePlayer':
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

                    case 'askEndTurnReaction':
                        this.addActionButton( 'button_useEquipment', _('Use Equipment'), 'onClickUseEquipmentButton' );
                        this.addActionButton( 'button_passOnUseEquipment', _('Pass'), 'onClickPassOnUseEquipmentButton' );
                    break;

                    case 'chooseIntegrityCards':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickCancelButton' );
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

        createEquipmentHands: function()
        {
            var smallEquipmentSprite = g_gamethemeurl+'img/equipment_card_sprite_50w.jpg';
            // create the equipment cards in your hand
            this.equipmentHandA = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandA.create( this, $('player_a_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandA.image_items_per_row = 3; // the number of card images per row in the image
            dojo.connect( this.equipmentHandA, 'onChangeSelection', this, 'onEquipmentHandASelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandASelectionChanged below
            //this.addEventToClass( "player_a_equipment_hand_holder", "onclick", "onClickMyEquipmentCard" );

            // Create one of each type of equipment card so we can add them to the equipmentHandA stock as needed throughout
            // the game and it will know what we're talking about when we do.

            // ARGUMENTS:
            // type id - we are using collector number for this
            // weight of the card (for sorting purpose)
            // the URL of our CSS sprite
            // the position of our card image in the CSS sprite
            this.equipmentHandA.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            this.equipmentHandA.addItemType( 15, 0, smallEquipmentSprite, 1 ); // truth serum
            this.equipmentHandA.addItemType( 12, 0, smallEquipmentSprite, 2 ); // smoke grenade
            this.equipmentHandA.addItemType( 2, 0, smallEquipmentSprite, 3 ); // coffee
            this.equipmentHandA.addItemType( 16, 0, smallEquipmentSprite, 4 ); // wiretap
            this.equipmentHandA.addItemType( 8, 0, smallEquipmentSprite, 5 ); // planted evidence
            this.equipmentHandA.addItemType( 4, 0, smallEquipmentSprite, 6 ); // evidence bag
            this.equipmentHandA.addItemType( 35, 0, smallEquipmentSprite, 7 ); // med kit
            this.equipmentHandA.addItemType( 37, 0, smallEquipmentSprite, 8 ); // mobile detonator
            this.equipmentHandA.addItemType( 11, 0, smallEquipmentSprite, 9 ); // restraining order
            this.equipmentHandA.addItemType( 44, 0, smallEquipmentSprite, 10 ); // riot shield
            this.equipmentHandA.addItemType( 14, 0, smallEquipmentSprite, 11 ); // taser

            this.equipmentHandB = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandB.create( this, $('player_b_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandB.image_items_per_row = 3; // the number of card images per row in the image
            this.equipmentHandB.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            dojo.connect( this.equipmentHandB, 'onChangeSelection', this, 'onEquipmentHandBSelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandXSelectionChanged below

            this.equipmentHandC = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandC.create( this, $('player_c_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandC.image_items_per_row = 3; // the number of card images per row in the image
            this.equipmentHandC.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            dojo.connect( this.equipmentHandC, 'onChangeSelection', this, 'onEquipmentHandCSelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandXSelectionChanged below

            this.equipmentHandD = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandD.create( this, $('player_d_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandD.image_items_per_row = 3; // the number of card images per row in the image
            this.equipmentHandD.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            dojo.connect( this.equipmentHandD, 'onChangeSelection', this, 'onEquipmentHandDSelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandXSelectionChanged below


            this.equipmentHandE = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandE.create( this, $('player_e_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandE.image_items_per_row = 3; // the number of card images per row in the image
            this.equipmentHandE.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            dojo.connect( this.equipmentHandE, 'onChangeSelection', this, 'onEquipmentHandESelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandXSelectionChanged below

            this.equipmentHandF = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandF.create( this, $('player_f_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandF.image_items_per_row = 3; // the number of card images per row in the image
            this.equipmentHandF.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            dojo.connect( this.equipmentHandF, 'onChangeSelection', this, 'onEquipmentHandFSelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandXSelectionChanged below

            this.equipmentHandG = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandG.create( this, $('player_g_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandG.image_items_per_row = 3; // the number of card images per row in the image
            this.equipmentHandG.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            dojo.connect( this.equipmentHandG, 'onChangeSelection', this, 'onEquipmentHandGSelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandXSelectionChanged below

            this.equipmentHandH = new ebg.stock(); // create the place we will store this player's hand of equipment cards
            this.equipmentHandH.create( this, $('player_h_equipment_hand_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.equipmentHandH.image_items_per_row = 3; // the number of card images per row in the image
            this.equipmentHandH.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            dojo.connect( this.equipmentHandH, 'onChangeSelection', this, 'onEquipmentHandHSelectionChanged' ); // when the onChangeSelection event is triggered on the HTML, call our callback function onEquipmentHandXSelectionChanged below



            // create the place in the center where active equipment goes
            this.activeEquipmentCenter = new ebg.stock(); // create the place we will put equipment that is active and affects the whole table rather than a particular person
            this.activeEquipmentCenter.create( this, $('active_equipment_center_holder'), this.equipmentCardWidth, this.equipmentCardHeight );
            this.activeEquipmentCenter.image_items_per_row = 3; // the number of card images per row in the image
            this.activeEquipmentCenter.addItemType( 0, 0, smallEquipmentSprite, 0 ); // card back
            this.activeEquipmentCenter.addItemType( 12, 0, smallEquipmentSprite, 2 ); // smoke grenade
        },

        // PLAY actice cards to the center area FROM HAND.
        playActiveCentralEquipmentCardFromHand: function(equipmentId, collectorNumber, playerLetter)
        {
            var equipmentHtmlIdInHand = "player_" + playerLetter + "_equipment_hand_holder_item_" + equipmentId; // the HTML ID of the card we want to move
            var equipmentHtmlIdInCenter = "active_equipment_center_holder_item_" + equipmentId; // the HTML ID of the card after it moved
            var centerHolderHtmlId = 'active_equipment_center_holder'; // the HTML ID of where we want to move it
            console.log("sliding from " + equipmentHtmlIdInHand + " to " + centerHolderHtmlId);

            this.slideToObject( equipmentHtmlIdInHand, centerHolderHtmlId, 1000, 750 ).play(); // slide it to its destination
            dojo.destroy(equipmentHtmlIdInHand); // destroy equipment card since it is discarded

            if(playerLetter == 'a')
            {
                this.equipmentHandA.removeFromStockById( equipmentId );
            }

            this.placeActiveCentralEquipmentCard(equipmentId, collectorNumber, playerLetter); // now that we've removed it, we can place it in the center
        },

        // PLACE active card in center area.
        placeActiveCentralEquipmentCard: function(equipmentId, collectorNumber, playerLetter)
        {
            var equipmentHtmlIdInHand = "player_" + playerLetter + "_equipment_hand_holder_item_" + equipmentId; // the HTML ID of the card we want to move
            var equipmentHtmlIdInCenter = "active_equipment_center_holder_item_" + equipmentId; // the HTML ID of the card after it moved
            var centerHolderHtmlId = 'active_equipment_center_holder'; // the HTML ID of where we want to move it
            console.log("sliding from " + equipmentHtmlIdInHand + " to " + centerHolderHtmlId);


            this.activeEquipmentCenter.addToStockWithId( collectorNumber, equipmentId ); // put this in the center area
            dojo.addClass( equipmentHtmlIdInCenter, 'center_equipment_card' ); // add the class

            // add a hoverover tooltip with a bigger version of the card
            var html = this.format_block( 'jstpl_largeEquipment', {
                x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber))
            } ); // the HTML (image) to be displayed
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( equipmentHtmlIdInCenter, html, delay ); // add the tooltip with the above configuration
            //dojo.addClass( nodeId, 'my_equipment_card' ); // add the class
        },

        playActivePlayerEquipmentCardFromHand: function(equipmentId, collectorNumber, playerLetterPlaying, playerLetterReceiving, rotation)
        {
            var equipmentHtmlIdInHand = "player_" + playerLetterPlaying + "_equipment_hand_holder_item_" + equipmentId; // the HTML ID of the card we want to move (it's the same for player A and other players)
            var targetActiveEquipmentHolderHtmlId = "player_" + playerLetterReceiving + "_first_equipment_active_holder"; // use the player position letter to move the card in the equipment player's hand to the target player's active equipment spot

            // move the new equipment from hand to the active area in front of the target player
            console.log("sliding from " + equipmentHtmlIdInHand + " to " + targetActiveEquipmentHolderHtmlId);
            //this.slideToObject( equipmentHtmlIdInHand, targetActiveEquipmentHolderHtmlId, 1000, 750 ).play(); // slide it to its destination
            this.slideToObjectAndDestroy( equipmentHtmlIdInHand, targetActiveEquipmentHolderHtmlId, 1000, 750 ); // slide it to its destination

            if(playerLetterPlaying == 'a')
            { // this player is the one playing it
                this.equipmentHandA.removeFromStockById( equipmentId ); // remove it from the stock since only your own hand uses a stock
            }

            //console.log("destroying " + equipmentHtmlIdInHand);
            //dojo.destroy(equipmentHtmlIdInHand); // destroy the old equipment in hand

            this.placeActivePlayerEquipmentCard(equipmentId, collectorNumber, playerLetterReceiving, rotation); // place the new card in the target's active area
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

        placeOpponentEquipmentCard: function(playerLetterOrder, stockId)
        {
            var rotation = this.getIntegrityCardRotation(playerLetterOrder);
            console.log( "Entering placeOpponentEquipmentCard with playerLetterOrder " + playerLetterOrder + " and stockid " + stockId + " and rotation " + rotation + "." );



            switch(playerLetterOrder)
            {
                case 'b':
                    this.equipmentHandB.addToStockWithId( 0, stockId ); // put this in the opponent's equipment hand (0 is card back and stockId is the index of this equipment card...usually 0)
                    this.rotateTo( 'player_b_equipment_hand_holder_item_'+stockId, rotation );
                    dojo.addClass( 'player_b_equipment_hand_holder_item_'+stockId, 'opponent_equipment_card_horizontal' ); // add the class
                break;
                case 'c':
                    this.equipmentHandC.addToStockWithId( 0, stockId ); // put this in the opponent's equipment hand (0 is card back and stockId is the index of this equipment card...usually 0)
                    this.rotateTo( 'player_c_equipment_hand_holder_item_'+stockId, rotation );
                    dojo.addClass( 'player_c_equipment_hand_holder_item_'+stockId, 'opponent_equipment_card' ); // add the class
                break;
                case 'd':
                    this.equipmentHandD.addToStockWithId( 0, stockId ); // put this in the opponent's equipment hand (0 is card back and stockId is the index of this equipment card...usually 0)
                    this.rotateTo( 'player_d_equipment_hand_holder_item_'+stockId, rotation );
                    dojo.addClass( 'player_d_equipment_hand_holder_item_'+stockId, 'opponent_equipment_card_horizontal' ); // add the class
                break;
                case 'e':
                    this.equipmentHandE.addToStockWithId( 0, stockId ); // put this in the opponent's equipment hand (0 is card back and stockId is the index of this equipment card...usually 0)
                    this.rotateTo( 'player_e_equipment_hand_holder_item_'+stockId, rotation );
                    dojo.addClass( 'player_e_equipment_hand_holder_item_'+stockId, 'opponent_equipment_card' ); // add the class
                break;
                case 'f':
                    this.equipmentHandF.addToStockWithId( 0, stockId ); // put this in the opponent's equipment hand (0 is card back and stockId is the index of this equipment card...usually 0)
                    this.rotateTo( 'player_f_equipment_hand_holder_item_'+stockId, rotation );
                    dojo.addClass( 'player_f_equipment_hand_holder_item_'+stockId, 'opponent_equipment_card_horizontal' ); // add the class
                break;
                case 'g':
                    this.equipmentHandG.addToStockWithId( 0, stockId ); // put this in the opponent's equipment hand (0 is card back and stockId is the index of this equipment card...usually 0)
                    this.rotateTo( 'player_g_equipment_hand_holder_item_'+stockId, rotation );
                    dojo.addClass( 'player_g_equipment_hand_holder_item_'+stockId, 'opponent_equipment_card_horizontal' ); // add the class
                break;
                case 'h':
                    this.equipmentHandH.addToStockWithId( 0, stockId ); // put this in the opponent's equipment hand (0 is card back and stockId is the index of this equipment card...usually 0)
                    this.rotateTo( 'player_h_equipment_hand_holder_item_'+stockId, rotation );
                    dojo.addClass( 'player_h_equipment_hand_holder_item_'+stockId, 'opponent_equipment_card' ); // add the class
                break;
            }
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

        drawEquipmentCard: function(equipmentCardId, collectorNumber)
        {
            console.log( "Drawing equipment with equipment ID " + equipmentCardId + " and collector number " + collectorNumber + "." );

            this.equipmentHandA.addToStockWithId( collectorNumber, equipmentCardId ); // put this in the player's hand. ARGS: (ID specified in addItemType, a unique ID for this card)

            // add a hoverover tooltip with a bigger version of the card
            var nodeId = 'player_a_equipment_hand_holder_item_'+equipmentCardId; // the HTML node where the item appears
            var html = this.format_block( 'jstpl_largeEquipment', {
                x: this.largeEquipmentCardWidth*(this.getEquipmentSpriteX(collectorNumber)),
                y: this.largeEquipmentCardHeight*(this.getEquipmentSpriteY(collectorNumber))
            } ); // the HTML (image) to be displayed
            var delay = 0; // any delay before it appears
            this.addTooltipHtml( nodeId, html, delay ); // add the tooltip with the above configuration
            //dojo.addClass( nodeId, 'my_equipment_card' ); // add the class

        },

        drawOpponentEquipmentCard: function(letterPositionOfPlayerDrawing, stockId)
        {
            // put the back of an equipment card in this player's equipment card hand spot
            this.placeOpponentEquipmentCard(letterPositionOfPlayerDrawing, stockId);
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
            if( ! this.checkAction( 'clickCancelButton' ) )
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

        onClickInvestigateButton: function( evt )
        {
            console.log( 'onClickInvestigateButton' );

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

        onClickMyEquipmentCard: function( evt )
        {
            var node = evt.currentTarget.id;
            var equipmentId = node.split('_')[2]; // a, b, c, d, etc.
            console.log( "eqcard clicked:"+equipmentId);
        },

        // The player is selecting an equipment card for any of these reasons:
        //     A) They are choosing the equipment card they will play.
        //     B) They are choosing the equipment card they will discard when they have too many.
        onEquipmentHandASelectionChanged: function( evt )
        {
            console.log( "An A equipment card was selected" );


            // Check that this action is possible (see "possibleactions" in states.inc.php)
            //if( ! this.checkAction( 'clickMyEquipmentCard' ) )
            //{   return; }

            var equipmentId = 0;

            if(this.isCurrentPlayerActive() && this.checkPossibleActions('clickMyEquipmentCard'))
            { // we are allowed to select cards based on our current state

                var selectedCards = this.equipmentHandA.getSelectedItems(); // get the cards that were selected
                for( var i in selectedCards )
                { // go through selected cards
                    equipmentId = selectedCards[i].id;
                    var htmlIdOfCard = 'myhand_item_' + equipmentId;
                    console.log("selecting htmlIdOfCard:"+htmlIdOfCard);
                    //dojo.addClass( htmlIdOfCard, 'cardSelected' ); // give this card a new CSS class

                    //dojo.stopEvent( evt ); // Preventing default browser reaction
                    this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedMyEquipmentCard.html", {
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

                this.equipmentHandA.unselectAll(); // unselect all cards

                /*
                // go through unselected cards
                var unselectedCards = this.equipmentHandA.getUnselectedItems(); // get the cards that were unselected
                for( var i in unselectedCards )
                {
                    equipmentId = unselectedCards[i].id;
                    var htmlIdOfCard = 'myhand_item_' + equipmentId;
                    console.log("UNselecting htmlIdOfCard:"+htmlIdOfCard);
                    //dojo.removeClass( htmlIdOfCard, 'cardSelected' ); // give this card a new CSS class
                    //dojo.addClass( htmlIdOfCard, 'cardUnselected' ); // give this card a new CSS class
                }
                */

            }
            else
            { // we are NOT in a state where we can select our equipment card
                this.equipmentHandA.unselectAll();
                var unselectedCards = this.equipmentHandA.getUnselectedItems(); // get the cards that were selected
                for( var i in unselectedCards )
                {
                    var htmlIdOfCard = 'myhand_item_'+unselectedCards[i].id;
                    //dojo.removeClass( htmlIdOfCard, 'cardSelected' ); // give this card a new CSS class
                    //dojo.addClass( htmlIdOfCard, 'cardUnselected' ); // give this card a new CSS class
                }
            }
        },

        onEquipmentHandBSelectionChanged: function( evt )
        {
            console.log( "An B equipment card was selected." );
        },

        onEquipmentHandCSelectionChanged: function( evt )
        {
            console.log( "An C equipment card was selected." );
        },

        onEquipmentHandDSelectionChanged: function( evt )
        {
            console.log( "An D equipment card was selected." );
        },

        onEquipmentHandESelectionChanged: function( evt )
        {
            console.log( "An E equipment card was selected." );
        },

        onEquipmentHandFSelectionChanged: function( evt )
        {
            console.log( "An F equipment card was selected." );
        },

        onEquipmentHandGSelectionChanged: function( evt )
        {
            console.log( "An G equipment card was selected." );
        },

        onEquipmentHandHSelectionChanged: function( evt )
        {
            console.log( "An H equipment card was selected." );
        },

        onClickEquipButton: function( evt )
        {
            console.log( 'onClickEquipButton' );

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

        onClickArmButton: function( evt )
        {
            console.log( 'onClickArmButton' );

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

        onClickShootButton: function( evt )
        {
            console.log( 'onClickShootButton' );

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

        onClickPassOnUseEquipmentButton: function( evt )
        {
            console.log( 'onClickPassOnUseEquipmentButton' );

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
            dojo.subscribe( 'woundPlayer', this, "notif_woundPlayer" );
            dojo.subscribe( 'iDrawEquipmentCards', this, "notif_iDrawEquipmentCards" );
            dojo.subscribe( 'otherPlayerDrawsEquipmentCards', this, "notif_otherPlayerDrawsEquipmentCards" );
            dojo.subscribe( 'discardEquipmentCard', this, "notif_discardEquipmentCard" );
            dojo.subscribe( 'discardActivePlayerEquipmentCard', this, "notif_discardActivePlayerEquipmentCard" );
            dojo.subscribe( 'activateCentralEquipment', this, "notif_activateCentralEquipment" );
            dojo.subscribe( 'activatePlayerEquipment', this, "notif_activatePlayerEquipment" );

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

            var letterOfPlayerWhoWasEliminated = notif.args.letterOfPlayerWhoWasEliminated;
            var htmlIdOfPlayerEliminatedArea = 'player_' + letterOfPlayerWhoWasEliminated + '_area';

            var classToAdd = 'eliminated_player_area';
            dojo.addClass( htmlIdOfPlayerEliminatedArea, classToAdd ); // add style to show this player is eliminated
        },

        notif_woundPlayer: function( notif )
        {
            console.log("Entered notif_woundPlayer.");

            var positionOfLeaderCard = notif.args.leader_card_position;
            var letterOfLeaderHolder = notif.args.letter_of_leader_holder;
            var cardType = notif.args.card_type;

            this.placeWoundedToken(letterOfLeaderHolder, positionOfLeaderCard, cardType); // put the token on the board
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

            var equipmentHtmlId = "player_" + playerLetter + "_equipment_hand_holder_item_" + equipmentId;

            if(playerLetter == 'a')
            {
                this.equipmentHandA.removeFromStockById( equipmentId );
            }

            dojo.destroy(equipmentHtmlId); // destroy equipment card since it is discarded
        },

        notif_discardActivePlayerEquipmentCard: function( notif )
        {
            console.log("Entered notif_discardActivePlayerEquipmentCard");

            var equipmentId = notif.args.equipment_id;
            var playerLetter = notif.args.player_letter;

            var equipmentHtmlId = 'player_'+playerLetter+'_active_equipment_'+equipmentId;

            dojo.destroy(equipmentHtmlId); // destroy equipment card since it is discarded
        },

        notif_activateCentralEquipment: function( notif )
        {
            console.log("Entered notif_activateCentralEquipment");

            var equipmentId = notif.args.equipment_id;
            var collectorNumber = notif.args.collector_number;
            var playerLetter = notif.args.player_letter;

            this.playActiveCentralEquipmentCard(equipmentId, collectorNumber, playerLetter);
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
        }

   });
});
