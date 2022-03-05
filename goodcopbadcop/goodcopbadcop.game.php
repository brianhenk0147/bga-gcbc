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
  * goodcopbadcop.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class goodcopbadcop extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array(
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );

				// create Integrity Card Deck
				// `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT - unique, internal id of the card
			  // `card_type` varchar(16) NOT NULL - value of card (honest, crooked, agent, kingpin)
			  // `card_type_arg` int(11) NOT NULL - whether it is revealed (0,1)
			  // `card_location` varchar(30) NOT NULL - the player who holds this card or the deck if it wasn't dealt (123456)
			  // `card_location_arg` int(11) NOT NULL - the position where it is placed (1,2,3)
				$this->integrityCards = self::getNew( "module.common.deck" );
        $this->integrityCards->init( "integrityCards" );

	}

    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "goodcopbadcop";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
				$this->initializePlayerPositioning($players); // set where each player sits around the table from each perspective
				$this->initializeIntegrityCardDeck($players);
				$this->initializeIntegrityCardVisibility($players);
				$this->dealIntegrityCards($players);

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $currentPlayerId = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // TODO: Gather all information about current game situation (visible by player $current_player_id).
				//$result['hand'] = $this->integrityCards->getCardsInLocation( $player_id ); // get this player's integrity cards

				// get integrity cards for this player
				$result['revealedCards'] = $this->getAllRevealedCards($currentPlayerId); // all cards that are revealed for everyone
				$result['hiddenCardsIHaveSeen'] = $this->getHiddenCardsIHaveSeen($currentPlayerId); // all cards I've seen
				$result['hiddenCardsIHaveNotSeen'] = $this->getHiddenCardsIHaveNotSeen($currentPlayerId); // get all the hidden cards I have NOT seen

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */
		function initializeIntegrityCardDeck($players)
		{
				$honestCardQuantity = $this->getHonestCardQuantity($players);
				$crookedCardQuantity = $honestCardQuantity;
				// Create Integrity Cards
				// `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT - unique, internal id of the card
				// `card_type` varchar(16) NOT NULL - value of card (honest, crooked, agent, kingpin)
				// `card_type_arg` int(11) NOT NULL - whether it is revealed (0,1)
				// `card_location` varchar(30) NOT NULL - the player who holds this card or the deck if it wasn't dealt (123456)
				// `card_location_arg` int(11) NOT NULL - the position where it is placed (1,2,3)
				$integrityCardsList = array(
						array( 'type' => 'honest', 'type_arg' => 0, 'card_location' => 'deck','nbr' => $honestCardQuantity),
						array( 'type' => 'crooked', 'type_arg' => 0, 'card_location' => 'deck','nbr' => $crookedCardQuantity),
						array( 'type' => 'agent', 'type_arg' => 0, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'kingpin', 'type_arg' => 0, 'card_location' => 'deck','nbr' => 1)
				);

				$this->integrityCards->createCards( $integrityCardsList, 'deck' ); // create the deck and override locations to deck
				$this->integrityCards->shuffle( 'deck' ); // shuffle it
		}

		function initializePlayerPositioning($players)
		{
				$allIntegrityCards = $this->getAllIntegrityCards();
			  foreach( $players as $asking_id => $asking_player )
				{
						$askingPlayerOrderIndex = $this->getPlayerNumberFromPlayerId($asking_id); // the position in turn order of the asking player
						foreach( $players as $player_id => $player )
						{
								$otherPlayerOrderIndex = $this->getPlayerNumberFromPlayerId($player_id); // the position in turn order of this player
								$letterPosition = $this->getLetterOrderPosition($askingPlayerOrderIndex, $otherPlayerOrderIndex, count($players));
								$insertQuery = "INSERT INTO playerPositioning (player_asking,player_id,player_position) VALUES ";
								$insertQuery .= "(".$asking_id.",".$player_id.",'".$letterPosition."') ";

								self::DbQuery( $insertQuery );
						}
				}
		}

		function initializeIntegrityCardVisibility($players)
		{
				$allIntegrityCards = $this->getAllIntegrityCards();
				foreach( $players as $player_id => $player )
				{
						foreach( $allIntegrityCards as $integrityCard )
						{
								$card_id = $integrityCard['card_id'];
								$insertQuery = "INSERT INTO playerCardVisibility (card_id,player_id,is_seen) VALUES ";
								$insertQuery .= "(".$card_id.",".$player_id.",0) ";

								self::DbQuery( $insertQuery );
						}
				}
		}

		function moveLeadersToInitialDeal()
		{
				$sqlUpdate = "UPDATE integrityCards SET ";
				$sqlUpdate .= "card_location='initialDeal' WHERE ";
				$sqlUpdate .= "card_type='agent' OR card_type='kingpin'";

				self::DbQuery( $sqlUpdate );
		}

		function getHonestCardQuantity($players)
		{
				//4 players=5
				//5 players=7
				//6 players=8
				//7 players=10
				//8 players=11
				$numberOfPlayers = count($players);
				$totalCardsNeeded = $numberOfPlayers * 3; // 3 for each player
				$totalNonLeadersNeeded = $totalCardsNeeded - 2; // minus the two leaders
				$honestNeeded = ceil($totalNonLeadersNeeded / 2);

				return $honestNeeded;
		}

		function dealIntegrityCards($players)
		{
				$this->moveLeadersToInitialDeal(); // put the agent and kingpin in the initial deal

				$numberOfPlayers = count($players); // get number of players
				$numberOfExtraCards = $numberOfPlayers - 2; // subtract 2 to account for Agent and Kingpin
				$extraInitialIntegrityCards = $this->integrityCards->pickCardsForLocation( $numberOfExtraCards, 'deck', 'initialDeal' ); // put Integrity Cards in initial deal so each player has 1 plus Agent and Kingpin
				$this->integrityCards->shuffle( 'initialDeal' ); // shuffle initial deal cards

				foreach( $players as $player_id => $player )
				{
						//$initialIntegrityCard = $this->integrityCards->pickCards( $numberOfExtraCards, 'initialDeal', $player_id );

						$card1 = $this->integrityCards->pickCardForLocation( 'initialDeal', $player_id ); // potential leader card
						$card2 = $this->integrityCards->pickCardForLocation( 'deck', $player_id ); // non-Leader card
						$card3 = $this->integrityCards->pickCardForLocation( 'deck', $player_id ); // non-leader card

						$this->integrityCards->shuffle( $player_id ); // shuffle this player's cards

						// randomly assign one of the initial deal cards to each player in a random position
						$shuffledCards = $this->integrityCards->getCardsInLocation( $player_id ); // get this player's integrity cards
						$cardPosition = 1; // start with the left card
						foreach( $shuffledCards as $card )
						{
								$cardId = $card['id']; // internal id
								$cardType = $card['type']; // name
								$cardTypeArg = $card['type_arg']; // card id 0, 1, 2, 3

								$card['location_arg'] = $cardPosition; // set the card position to 1, 2, or 3 in this local scope
								$this->integrityCards->moveCard($cardId, $player_id, $cardPosition); // set the card position to 1, 2, or 3 on the server
								$this->setVisibilityOfIntegrityCard($cardId, $player_id, 1); // show that this player has seen this card

								// log this card for debugging
								$msgHandCard = "<b>Initial Integrity Cards:</b> id is $cardId with type of $cardType and type_arg of $cardTypeArg for this card.";
								self::warn($msgHandCard);

								$cardPosition++; // move up to the next card position
						}

						// notify player about their team (they will see the message in their log but the client won't receive it as a regular notification because notifications are not setup yet)
						self::notifyPlayer( $player_id, 'newGameMessage', 'You are on the X Team. Your objective is to Y.', array() );
				}
		}

		function getAllRevealedCards($playerId)
		{
				$sql = "SELECT ic.card_id, ic.card_type, ic.card_type_arg, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
				$sql .= "WHERE card_type_arg=1 ";
				return self::getCollectionFromDb( $sql );
		}

		function getHiddenCardsIHaveSeen($playerId)
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
				$sql .= "WHERE pcv.player_id=$playerId AND pcv.is_seen=1 and ic.card_type_arg=0 ";

				return self::getCollectionFromDb( $sql );
		}

		function getHiddenCardsIHaveNotSeen($playerId)
		{
				// only give basic information... not the card values (since this player has not seen these yet)
				$sql = "SELECT ic.card_id, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
				$sql .= "WHERE pcv.player_id=$playerId AND pcv.is_seen=0 and ic.card_type_arg=0 ";

				return self::getCollectionFromDb( $sql );
		}

		function getPlayerNumberFromPlayerId($playerId)
		{
				return self::getUniqueValueFromDb("SELECT player_no FROM player WHERE player_id=$playerId");
		}

		function getAllIntegrityCards()
		{
				return self::getObjectListFromDB( "SELECT *
																					 FROM integrityCards" );
		}

		function setVisibilityOfIntegrityCard($cardId, $playerId, $seenValue)
		{
				$sql = "UPDATE playerCardVisibility SET is_seen=$seenValue WHERE card_id=$cardId AND player_id=$playerId";
				self::DbQuery( $sql );
		}

		function getLetterOrderPosition($askingPlayerOrder, $otherPlayerOrder, $numberOfPlayers)
		{
				$orderingArray = array('a','b','c','d','e','f','g','h');
				$difference = $otherPlayerOrder - $askingPlayerOrder;
				$newIndex = $difference; // if difference is positive, the correct place in the array is just the difference
				if($difference < 0)
				{ // the ordering difference between the two players is negative
					$newIndex = $numberOfPlayers + $difference; // we need to subtract the difference from the max number of players to find the correct ordere
				}

				return $orderingArray[$newIndex];
		}


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in goodcopbadcop.action.php)
    */

    /*

    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' );

        $player_id = self::getActivePlayerId();

        // Add your game logic to play a card there
        ...

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );

    }

    */


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    /*

    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...

        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
