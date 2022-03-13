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

                var cardPosition = 1;
                var cardType = 'crooked';

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

            // First Param: css class to target
            // Second Param: type of events
            // Third Param: the method that will be called when the event defined by the second parameter happen
            this.addEventToClass( "integrity_card", "onclick", "onClickCardToInvestigate");

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
                    break;

                    case 'investigateChooseCard':
                        this.addActionButton( 'button_cancel', _('Cancel'), 'onClickInvestigateButton' );
                    break;

                    case 'askInvestigateReaction':
                        this.addActionButton( 'button_useEquipment', _('Use Equipment'), 'onClickPassOnUseEquipmentButton' );
                        this.addActionButton( 'button_passOnUseEquipment', _('Pass'), 'onClickPassOnUseEquipmentButton' );
                    break;

                    case 'askAim':
                        this.addActionButton( 'button_endTurn', _('End Turn'), 'onClickEndTurnButton' );
                    break;

                    case 'askEndTurnReaction':
                        this.addActionButton( 'button_useEquipment', _('Use Equipment'), 'onClickPassOnUseEquipmentButton' );
                        this.addActionButton( 'button_passOnUseEquipment', _('Pass'), 'onClickPassOnUseEquipmentButton' );
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

        placeIntegrityCard: function(playerLetter, cardPosition, visibilityToYou, cardType, rotation)
        {
            console.log( "Entering placeIntegrityCard with playerLetter " + playerLetter + " cardPosition " + cardPosition + " visibilityToYou " + visibilityToYou + " cardType " + cardType + "." );

            visibilityOffset = this.getVisibilityOffset(visibilityToYou); // get sprite X value for this card type
            cardTypeOffset = this.getCardTypeOffset(cardType); // get sprite Y value for this card type

            cardHolderDiv = 'player_'+playerLetter+'_integrity_card_'+cardPosition+'_holder';
            dojo.place(
                    this.format_block( 'jstpl_integrityCard', {
                        x: this.integrityCardWidth*(visibilityOffset),
                        y: this.integrityCardHeight*(cardTypeOffset),
                        playerLetter: playerLetter,
                        cardPosition: cardPosition
                    } ), cardHolderDiv );

            cardDiv = 'player_'+playerLetter+'_integrity_card_'+cardPosition;
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
            visibilityOffset = 0;
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

        onClickInvestigateButton: function( evt )
        {
            console.log( 'onClickInvestigateButton' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickInvestigateButton' ) )
            {   return; }

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/chooseCardToInvestigate.html", {
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

        onClickCardToInvestigate: function( evt )
        { // a player clicked on an integrity card
            console.log('clicked integrity card iscurrentplayeractive() ' + this.isCurrentPlayerActive());

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'clickCardToInvestigate' ) )
            {   return; }

            var node = evt.currentTarget.id;
            var playerPosition = node.split('_')[1]; // b, c, d, etc.
            var cardPosition = node.split('_')[4]; // 1, 2, 3

            this.ajaxcall( "/goodcopbadcop/goodcopbadcop/clickedCardToInvestigateCard.html", {
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

        onClickPassOnUseEquipmentButton: function( evt )
        {
            console.log( 'onClickPassOnUseEquipmentButton' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

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

            // Preventing default browser reaction
            dojo.stopEvent( evt );

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

            //var seenCard = notif.args.seenCard;
            var playerLetter = notif.args.playerLetter;
            var cardPosition = notif.args.cardPosition;
            var cardType = notif.args.cardType;

            console.log( "Seen Card:" );
            console.log( "Player Letter: " + playerLetter );
            console.log( "Card Position: " + cardPosition );
            console.log( "Card Type: " + cardType );
            console.log( "" );

            //var playerLetter = seenCard['player_position']; // a, b, c, etc.
            var rotation = this.getIntegrityCardRotation(playerLetter); // 0, 90, -90
            //var cardPosition = seenCard['card_position'];
            //var cardType = seenCard['card_type'];
            var htmlId = "player_" + playerLetter + "_integrity_card_" + cardPosition;

            visibilityOffset = this.getVisibilityOffset('HIDDEN_SEEN'); // get sprite X value for this card type
            cardTypeOffset = this.getCardTypeOffset(cardType); // get sprite Y value for this card type

            spriteX = this.integrityCardWidth*(visibilityOffset);
            spriteY = this.integrityCardHeight*(cardTypeOffset);
            dojo.style( htmlId, 'backgroundPosition', '-' + spriteX + 'px -' + spriteY + 'px' ); // update the integrity card for this player to the seen version of it... should be in format -${x}px -${y}px
        },
   });
});
