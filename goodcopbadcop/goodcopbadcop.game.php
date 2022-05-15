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
            //      ...\
						"CURRENT_PLAYER" => 10
        ) );

				// create Integrity Card Deck
				// `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT - unique, internal id of the card
			  // `card_type` varchar(16) NOT NULL - value of card (honest, crooked, agent, kingpin)
			  // `card_type_arg` int(11) NOT NULL - whether it is revealed (0,1)
			  // `card_location` varchar(30) NOT NULL - the player who holds this card or the deck if it wasn't dealt (123456)
			  // `card_location_arg` int(11) NOT NULL - the position where it is placed (1,2,3)
				$this->integrityCards = self::getNew( "module.common.deck" );
        $this->integrityCards->init( "integrityCards" );

				// create Trap Deck
				$this->equipmentCards = self::getNew( "module.common.deck" );
				$this->equipmentCards->init( "equipmentCards" );
				//$this->equipmentCards->autoreshuffle_custom = array('equipmentCardDeck' => 'discard');
				$this->equipmentCards->autoreshuffle = true; // automatically reshuffle when you run out of cards
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
				$this->initializeStats();

        // TODO: setup the initial game situation here
				$this->initializePlayerPositioning($players); // set where each player sits around the table from each perspective
				$this->initializeIntegrityCardDeck($players); // create the integrity card deck
				$this->initializeIntegrityCardVisibility($players);
				$this->dealIntegrityCards($players);
				$this->initializeGuns($players);
				$this->initializeEquipmentCardDeck($players); // create the equipment card deck
				$this->dealEquipmentCards($players);

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();
				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$this->setGameStateValue("CURRENT_PLAYER", $activePlayerId);

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

				// get gun details
        $result['guns'] = $this->getGunsForPlayer($currentPlayerId);
				$result['gunRotations'] = $this->getGunRotationsForPlayer($currentPlayerId);
				$result['woundedTokens'] = $this->getWoundedTokensForPlayer($currentPlayerId);

				// get equipment cards
				$result['myEquipmentCards'] = $this->getEquipmentCardsForPlayer($currentPlayerId); // get all of the requesting player's equipment cards
				$result['opponentEquipmentCards'] = $this->getEquipmentCardCountsOpponentsOf($currentPlayerId); // get all the equipment cards held by opponents of the requesting player
				$result['sharedActiveEquimentCards'] = $this->getSharedActiveEquipmentCards(); // get all the active equipment cards in the center
				$result['playerActiveEquipmentCards'] = $this->getPlayerActiveEquipmentCards($currentPlayerId); // get all the active equipment cards targeting a specific player

				$result['eliminatedPlayers'] = $this->getEliminatedPlayers($currentPlayerId);

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
				$gameProgression = 0; // we will add to this as we go to determine the total game progression

				$integrityCards = $this->getAllIntegrityCards(); // get all the integrity cards in this game
				$totalIntegrityCards = count($integrityCards); // get total number of integrity cards in this game

				$players = $this->getPlayersDeets(); // get all the players in the game
				$totalPlayers = count($players); // count the players in the game

				$maxProgressionPerCard = 100*(1 / $totalIntegrityCards); // the max number of progression you can get from this card is 1/total_integrity_cards

				// go through each card and check the percentage of players who have seen that card
				foreach( $integrityCards as $integrityCard )
				{
						$progressionThisCardAdds = 0;
						$card_id = $integrityCard['card_id'];
						if($this->getCardRevealedStatus($card_id) == 1)
						{ // this card is REVEALED
								$progressionThisCardAdds = $maxProgressionPerCard; // since all players have seen this, give it full progression points
						}
						else
						{ // this card is NOT revealed
								$numberOfPlayersWhoHaveSeenThis = $this->getNumberOfPlayersWhoHaveSeenCard($card_id);
								$percentageSeen = $numberOfPlayersWhoHaveSeenThis / $totalPlayers; // percentage of players who have seen this card

								$progressionThisCardAdds = $percentageSeen * $maxProgressionPerCard; // multiply the percentage of players who have seen this by the max progression you can get from the card
						}

						$gameProgression += $progressionThisCardAdds; // add this card to our running total
				}

				if($gameProgression > 90)
				{ // most of the cards are revealed
						$numberOfUnwoundedLeaders = $this->countUnwoundedLeaders(); // count how many leaders are not yet wounded (1 or 2)
						$gameProgression -= $numberOfUnwoundedLeaders; // we could be at 100% if all cards are revealed so let's subtract 1 for each wounded leader there is
				}

        return $gameProgression;

    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

		function initializeStats()
		{
				self::initStat( 'table', 'honest_at_start', 0 );
				self::initStat( 'table', 'crooked_at_start', 0 );
				self::initStat( 'table', 'honest_at_end', 0 );
				self::initStat( 'table', 'crooked_at_end', 0 );

				self::initStat( 'player', 'investigations_completed', 0 );
				self::initStat( 'player', 'equipment_acquired', 0 );

		}

		function initializeEquipmentCardDeck($players)
		{
				// Create Integrity Cards
				// `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT - unique, internal id of the card (NOT the collector number)
				// `card_type` varchar(16) NOT NULL - equipment
				// `card_type_arg` int(11) NOT NULL - collector number
				// `card_location` varchar(30) NOT NULL - the player ID of the holder of the card, "deck", "hand", or "discard"
				// `card_location_arg` int(11) NOT NULL - whether it is active (0,1)
				$equipmentCardsList = array(
						//array( 'type' => 'equipment', 'type_arg' => 15, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 12, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 2, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 16, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 8, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 44, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 11, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 37, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 4, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 35, 'card_location' => 'deck','nbr' => 1),
						//array( 'type' => 'equipment', 'type_arg' => 14, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 3, 'card_location' => 'deck','nbr' => 2),
						array( 'type' => 'equipment', 'type_arg' => 1, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 30, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 45, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 9, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 13, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 7, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 17, 'card_location' => 'deck','nbr' => 1),
						array( 'type' => 'equipment', 'type_arg' => 6, 'card_location' => 'deck','nbr' => 1)
				);

				$this->equipmentCards->createCards( $equipmentCardsList, 'deck' ); // create the deck and override locations to deck
				$this->equipmentCards->shuffle( 'deck' ); // shuffle it

				// setting names of equipment since doing it above in the createCards method doesn't work for an unknown reason
				$this->setEquipmentName(2, 'Coffee');
				$this->setEquipmentName(8, 'Planted Evidence');
				$this->setEquipmentName(12, 'Smoke Grenade');
				$this->setEquipmentName(15, 'Truth Serum');
				$this->setEquipmentName(16, 'Wiretap');
				$this->setEquipmentName(44, 'Riot Shield');
				$this->setEquipmentName(11, 'Restraining Order');
				$this->setEquipmentName(37, 'Mobile Detonator');
				$this->setEquipmentName(4, 'Evidence Bag');
				$this->setEquipmentName(35, 'Med Kit');
				$this->setEquipmentName(14, 'Taser');
				$this->setEquipmentName(3, 'Defibrillator');
				$this->setEquipmentName(1, 'Blackmail');
				$this->setEquipmentName(30, 'Disguise');
				$this->setEquipmentName(45, 'Walkie Talkie');
				$this->setEquipmentName(9, 'Polygraph');
				$this->setEquipmentName(13, 'Surveillance Camera');
				$this->setEquipmentName(7, 'Metal Detector');
				$this->setEquipmentName(17, 'Deliriant');
				$this->setEquipmentName(6, 'K-9 Unit');
		}

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

		function initializeGuns($players)
		{
				$insertGun1Query = "INSERT INTO guns (gun_id,gun_held_by,gun_aimed_at) VALUES ";
				$insertGun1Query .= "(1,'','') ";
				self::DbQuery( $insertGun1Query );

				$insertGun2Query = "INSERT INTO guns (gun_id,gun_held_by,gun_aimed_at) VALUES ";
				$insertGun2Query .= "(2,'','') ";
				self::DbQuery( $insertGun2Query );

				if(count($players) > 4)
				{ // 5+ players
						$insertGun3Query = "INSERT INTO guns (gun_id,gun_held_by,gun_aimed_at) VALUES ";
						$insertGun3Query .= "(3,'','') ";
						self::DbQuery( $insertGun3Query );
				}

				if(count($players) > 6)
				{ // 7+ players
						$insertGun4Query = "INSERT INTO guns (gun_id,gun_held_by,gun_aimed_at) VALUES ";
						$insertGun4Query .= "(4,'','') ";
						self::DbQuery( $insertGun4Query );
				}
		}

		function deleteIntegrityCardFromDatabase($cardId)
		{
				// delete from integrityCards
				$deleteQueryIC = "DELETE FROM integrityCards WHERE card_id=$cardId";
				self::DbQuery( $deleteQueryIC );


				// delete from playerCardVisibility
				$deleteQueryPCV = "DELETE FROM playerCardVisibility WHERE card_id=$cardId";
				self::DbQuery( $deleteQueryPCV );
		}

		function insertIntegrityCardIntoDatabase($cardType, $cardTypeArg, $cardLocation, $cardLocationArg)
		{
				$insertQuery = "INSERT INTO integrityCards (card_type,card_type_arg,card_location,card_location_arg) VALUES ";
				$insertQuery .= "('$cardType',$cardTypeArg,'$cardLocation',$cardLocationArg) ";
				self::DbQuery( $insertQuery );

				$players = $this->getPlayersDeets();
				$integrityCardsForPlayer = $this->getIntegrityCardsForPlayer($cardLocation);

				//$count = count($integrityCardsForPlayer);
				//throw new feException( "Count:$count");


				foreach( $integrityCardsForPlayer as $integrityCard )
				{
						$card_id = $integrityCard['card_id'];
						//throw new feException( "card:$card_id");

						foreach( $players as $player )
						{ // go through each player

								$player_id = $player['player_id']; // the ID of this player

								$insertQuery = "INSERT INTO playerCardVisibility (card_id,player_id,is_seen) VALUES ";
								$insertQuery .= "(".$card_id.",".$player_id.",0) "; // insert this in a way so no one has seen this card
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

		function setAllPlayerIntegrityCardsToRevealed($playerRevealingId)
		{
				$hiddenCards = $this->getHiddenCardsFromPlayer($playerRevealingId);

				foreach( $hiddenCards as $integrityCard )
				{
						$card_id = $integrityCard['card_id'];
						$cardPosition = $integrityCard['card_location_arg']; // 1, 2, 3
						$this->revealCard($playerRevealingId, $cardPosition);
				}
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
						$this->sendNewGameMessage($player_id);
				}

				$this->countPlayersOnTeams('start');
		}

		function countPlayersOnTeams($startOrEnd)
		{
			$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
			foreach( $players as $player )
			{ // go through each player
				$playerId = $player['player_id']; // the ID of this player

				if($this->getPlayerRole($playerId) == 'honest_agent')
				{ // honest leader
						if($startOrEnd == 'start')
							self::incStat( 1, 'honest_at_start' ); // increase end game table stat
						else
							self::incStat( 1, 'honest_at_end' ); // increase end game table stat
				}
				elseif($this->getPlayerRole($playerId) == 'honest_cop')
				{ // honest cop
						if($startOrEnd == 'start')
							self::incStat( 1, 'honest_at_start' ); // increase end game table stat
						else
							self::incStat( 1, 'honest_at_end' ); // increase end game table stat
				}
				elseif($this->getPlayerRole($playerId) == 'crooked_kingpin')
				{ // crooked leader
						if($startOrEnd == 'start')
							self::incStat( 1, 'crooked_at_start' ); // increase end game table stat
						else
							self::incStat( 1, 'crooked_at_end' ); // increase end game table stat
				}
				else
				{ // crooked cop
						if($startOrEnd == 'start')
							self::incStat( 1, 'crooked_at_start' ); // increase end game table stat
						else
							self::incStat( 1, 'crooked_at_end' ); // increase end game table stat
				}

			}
		}

		function sendNewGameMessage($playerId)
		{
				if($this->getPlayerRole($playerId) == 'honest_agent')
				{ // honest leader
						self::notifyPlayer( $playerId, 'newGameMessage', 'You are secretly the LEADER of the HONEST Team. Your mission is to find and eliminate the KINGPIN while keeping your identity hidden as long as possible.', array() );
				}
				elseif($this->getPlayerRole($playerId) == 'honest_cop')
				{ // honest cop
						self::notifyPlayer( $playerId, 'newGameMessage', 'You are secretly on the HONEST Team. Your mission is to find and eliminate the KINGPIN while helping your leader (the AGENT) stay hidden and unharmed.', array() );
				}
				elseif($this->getPlayerRole($playerId) == 'crooked_kingpin')
				{ // crooked leader
						self::notifyPlayer( $playerId, 'newGameMessage', 'You are secretly the LEADER of the CROOKED Team. Your mission is to find and eliminate the AGENT while keeping your identity hidden as long as possible.', array() );
				}
				else
				{ // crooked cop
						self::notifyPlayer( $playerId, 'newGameMessage', 'You are secretly on the CROOKED Team. Your mission is to find and eliminate the AGENT while helping your leader (the KINGPIN) stay hidden and unharmed.', array() );
				}
		}

		function dealEquipmentCards($players)
		{
				foreach( $players as $player_id => $player )
				{
						$this->drawEquipmentCard($player_id, 1); // have each player draw 1 equipment card
				}
		}

		function getAllRevealedCards($playerId)
		{
				$sql = "SELECT ic.card_id, ic.card_type, ic.card_type_arg, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
				$sql .= "WHERE card_type_arg=1 ";
				$cardArray = self::getCollectionFromDb( $sql );

				foreach($cardArray as $card)
				{
							$cardId = $card['card_id'];
							$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
							$cardArray[$cardId]['player_list'] = $listOfPlayersSeen; // add the list of players into this array
				}

				return $cardArray;
		}

		function getHiddenCardsIHaveSeen($playerId)
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
				$sql .= "WHERE pcv.player_id=$playerId AND pcv.is_seen=1 and ic.card_type_arg=0 ";

				$cardArray = self::getCollectionFromDb( $sql );

				foreach($cardArray as $card)
				{
							$cardId = $card['card_id'];
							$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
							$cardArray[$cardId]['player_list'] = $listOfPlayersSeen; // add the list of players into this array
				}

				return $cardArray;
		}

		function getHiddenCardsIHaveNotSeen($playerId)
		{
				// only give basic information... not the card values (since this player has not seen these yet)
				$sql = "SELECT ic.card_id, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
				$sql .= "WHERE pcv.player_id=$playerId AND pcv.is_seen=0 and ic.card_type_arg=0 ";

				$cardArray = self::getCollectionFromDb( $sql );

				foreach($cardArray as $card)
				{
							$cardId = $card['card_id'];
							$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
							$cardArray[$cardId]['player_list'] = $listOfPlayersSeen; // add the list of players into this array
				}

				return $cardArray;
		}

		function getHiddenCardsFromPlayer($playerId)
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerId AND ic.card_type_arg=0 ";

				return self::getCollectionFromDb( $sql );
		}

		function getLeaderCardIdForPlayer($playerId)
		{
				return self::getUniqueValueFromDb("SELECT * FROM `integrityCards` ic WHERE ic.card_location=$playerId AND (ic.card_type='agent' OR ic.card_type='kingpin') ");
		}

		function getPlayerTeam($playerId)
		{
				$role = $this->getPlayerRole($playerId);

				$roleSplit = explode("_", $role);
				$team = $roleSplit[0]; // honest, crooked

				return $team;
		}

		// Return honest_cop, honest_agent, crooked_kinpin, crooked_cop.
		function getPlayerRole($playerId)
		{
				// TODO: see if Planted Evidence is active

				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerId ";

				$myIntegrityCards = self::getCollectionFromDb( $sql );

				$honestCards = 0;
				$crookedCards = 0;
				foreach( $myIntegrityCards as $integrityCard )
				{
						$cardType = $integrityCard['card_type'];
						if($cardType == 'honest')
						{
								$honestCards++;
						}
						elseif($cardType == 'crooked')
						{
								$crookedCards++;
						}
						elseif($cardType == 'agent')
						{
								return 'honest_agent';
						}
						elseif($cardType == 'kingpin')
						{
								return 'crooked_kingpin';
						}
						else
						{
								throw new feException( "Unknown Team:$cardType");
						}
				}

				if($honestCards > $crookedCards)
				{ // honest team
						if($this->hasPlantedEvidence($playerId))
						{ // they have planted evidence played on them
								return 'crooked_cop';
						}
						else
						{ // normal case
								return 'honest_cop';
						}
				}
				else
				{ // crooked team
						if($this->hasPlantedEvidence($playerId))
						{ // they have planted evidence played on them
							  return 'honest_cop';
						}
						else
						{ // normal case
								return 'crooked_cop';
						}
				}
		}

		function getNumberOfGunsHeldByPlayersWithHiddenIntegrityCards()
		{
				$gunsHeld = 0;
				$guns = $this->getGunsHeld(); // get all guns currently being held by a player
				foreach( $guns as $gun )
				{ // go through each gun that is currently shooting
						$gunHolderPlayerId = $gun['gun_held_by']; // get the PLAYER ID of the player holding this gun

						$hiddenCards = $this->getHiddenCardsFromPlayer($gunHolderPlayerId); // get all this player's hidden integrity cards
						//$count = count($hiddenCards);
						//throw new feException( "count of hidden cards by player ($gunHolderPlayerId) is ($count)");

						if(count($hiddenCards) > 0)
						{ // they have at least one hidden card
								$gunsHeld++;
						}
				}
				//throw new feException( "guns held by players with hidden integrity cards ($gunsHeld)");
				return $gunsHeld;
		}

		function getNumberOfEquipmentTargets()
		{
				$equipmentCardId = $this->getEquipmentCardIdInUse();
				$numberOfTargets = 0;
				$target1 = $this->getEquipmentTarget1($equipmentCardId);

				//throw new feException( "target1:$target1 target2:$target2");
				if( !is_null($target1) && $target1 != '' )
				{ // this target is set
					//throw new feException( "false");
						$numberOfTargets++;
				}

				$target2 = $this->getEquipmentTarget2($equipmentCardId);
				if( !is_null($target2) && $target2 != '' )
				{ // this target is set
					//throw new feException( "false");
						$numberOfTargets++;
				}

				$target3 = $this->getEquipmentTarget3($equipmentCardId);
				if( !is_null($target3) && $target3 != '' )
				{ // this target is set
					//throw new feException( "false");
						$numberOfTargets++;
				}

				$target4 = $this->getEquipmentTarget4($equipmentCardId);
				if( !is_null($target4) && $target4 != '' )
				{ // this target is set
					//throw new feException( "false");
						$numberOfTargets++;
				}

				return $numberOfTargets;
		}

		function getNumberOfPlayersWhoHaveSeenCard($card_id)
		{
				$totalPlayersWhoHaveSeenThisCard = 0;

				$sql = "SELECT is_seen FROM `playerCardVisibility` ";
				$sql .= "WHERE card_id=$card_id ";

				$cardSeenList = self::getCollectionFromDb( $sql ); // get the list of players and whether they have seen this card
				foreach( $cardSeenList as $seen )
				{
						$totalPlayersWhoHaveSeenThisCard += $seen['is_seen']; // add 0 or 1 depending on whether it was seen
				}

				return $totalPlayersWhoHaveSeenThisCard;
		}

		function getListOfPlayersWhoHaveSeenCard($card_id)
		{
				$playerList = "";

				$isHidden = $this->isIntegrityCardHidden($card_id); // true if this card is hidden
				if($isHidden == false)
				{ // this card is revealed
						return clienttranslate('All');
				}

				$sql = "SELECT player_name, player_color ";
				$sql .= "FROM `playerCardVisibility` pcv ";
				$sql .= "JOIN `player` p ON p.player_id=pcv.player_id  ";
				$sql .= "WHERE pcv.card_id=$card_id && pcv.is_seen=1 ";
				$sql .= "ORDER BY p.player_name ";

				$cardSeenList = self::getCollectionFromDb( $sql ); // get the list of players and whether they have seen this card
				foreach( $cardSeenList as $seen )
				{
						$playerName = $seen['player_name'];
						$playerColor = $seen['player_color'];

						$playerList .= "<span style=\"color:#$playerColor\"><b>$playerName</b></span>"; // add on this player name who saw it
						$playerList .= ", "; // add a comma
				}

				$playerList = substr($playerList, 0, -2); // remove the last comma and space (it won't go negative even if there are no players)

				return $playerList;
		}

		function getPlayersDeets()
		{
				$sql = "SELECT * FROM `player` ";
				$sql .= "WHERE 1 ";

				//var_dump( $sqlUpdate );
				//die('ok');

				return self::getObjectListFromDB( $sql );
		}

		function getEquipmentCardCountForPlayer($playerId)
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_owner=$playerId AND equipment_is_active<>1 ";

				$equipmentCardsForThisPlayer = self::getObjectListFromDB( $sql );

				return count($equipmentCardsForThisPlayer); // return the count
		}

		function getEquipmentCardsForPlayer($askingPlayer)
		{
				$cardArray = array(); // this is what we will return

				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_owner=$askingPlayer AND equipment_is_active<>1 ";

				$cards = self::getObjectListFromDB( $sql );

				$index = 0;
				foreach( $cards as $card )
				{
						$cardId = $card['card_id'];
						$collectorNumber = $card['card_type_arg'];
						$equipName = $this->getTranslatedEquipmentName($collectorNumber);
						$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

						$cardArray[$index] = array( 'card_id' => $cardId, 'card_type_arg' => $collectorNumber, 'equip_name' => $equipName, 'equip_effect' => $equipEffect );

						$index++;
				}

				return $cardArray;
		}

		function getSharedActiveEquipmentCards()
		{
				// smoke grenade is card_type_arg=12

				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=12 ";

				return self::getObjectListFromDB( $sql );
		}

		function isCoffeeActive()
		{
				// coffee has card_type_arg=2

				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=2 ";

				$coffeeList = self::getObjectListFromDB( $sql );

				if(count($coffeeList) > 0)
				{
						return true;
				}
				else
				{
					return false;
				}
		}

		function getActivePlayerEquipmentCardIdsForPlayer($playerId)
		{
				$cardIdArray = array();

				$sql = "SELECT card_id FROM `equipmentCards` ";
				$sql .= "WHERE equipment_owner=$playerId AND equipment_is_active=1 AND player_target_1 IS NOT NULL AND player_target_1 <> ''";

				//var_dump( $sql );
				//die('ok');

				$ids = self::getObjectListFromDB( $sql );

				$index = 0;
				foreach( $ids as $id )
				{ // go through each card (usually only 1)

						$cardIdArray[$index] = $id['card_id']; // equipment card id... 0, 1, 2, 3, 4, etc.
						$index++;
				}

				return $cardIdArray;
		}

		function getEquipmentCardIdsForPlayer($playerId)
		{
				$cardIdArray = array();

				$sql = "SELECT card_id FROM `equipmentCards` ";
				$sql .= "WHERE equipment_owner=$playerId AND equipment_is_active<>1 ";

				//var_dump( $sql );
				//die('ok');

				$ids = self::getObjectListFromDB( $sql );

				$index = 0;
				foreach( $ids as $id )
				{ // go through each card (usually only 1)

						$cardIdArray[$index] = $id['card_id']; // equipment card id... 0, 1, 2, 3, 4, etc.
						$index++;
				}

				return $cardIdArray;
		}

		function getPlayerActiveEquipmentCards($askingPlayer)
		{
				$playerEquipmentCards = array();

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player

						$playerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of this player from the player asking's perspective
						$equipmentCardIds = $this->getActivePlayerEquipmentCardIdsForPlayer($playerId);

						//var_dump( $equipmentCardIds );
						//die('ok');
						foreach($equipmentCardIds as $id)
						{
								$collectorNumber = $this->getCollectorNumberFromId($id);
								$equipmentName = $this->getTranslatedEquipmentName($collectorNumber);
								$equipmentEffect = $this->getTranslatedEquipmentEffect($collectorNumber);
								$playerEquipmentCards[$playerId] = array( 'player_id' => $playerId, 'playerLetterOrder' => $playerLetterOrder, 'equipmentCardIds' => $id, 'collectorNumber' => $collectorNumber, 'equipmentName' => $equipmentName, 'equipmentEffect' => $equipmentEffect ); // save the count of equipment cards to the 2D array we will be returning
						}
				}

				return $playerEquipmentCards;
		}

		function getEquipmentCardCountsOpponentsOf($askingPlayer)
		{
				$opponentEquipmentCards = array();

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player

						if($playerId != $askingPlayer)
						{ // skip the asking player because we have another process for getting their equipment

								$playerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of this player from the player asking's perspective
								$equipmentCardIds = $this->getEquipmentCardIdsForPlayer($playerId);

								//var_dump( $equipmentCardIds );
								//die('ok');
								foreach($equipmentCardIds as $id)
								{
										$opponentEquipmentCards[$playerId] = array( 'player_id' => $playerId, 'playerLetterOrder' => $playerLetterOrder, 'equipmentCardIds' => $id); // save the count of equipment cards to the 2D array we will be returning
								}
						}
				}

				return $opponentEquipmentCards;
		}

		function getGunsForPlayer($askingPlayer)
		{
				$sql = "SELECT gun_id, gun_held_by, gun_aimed_at, pp.player_asking, pp.player_id playerIdHeldBy, pp.player_position letterPositionHeldBy, pp2.player_id playerIdAimedAt, pp2.player_position letterPositionAimedAt ";
				$sql .= ", (SELECT player_name FROM player WHERE player_id=gun_held_by) heldByName, (SELECT player_color FROM player WHERE player_id=gun_held_by) heldByColor, (SELECT player_name FROM player WHERE player_id=gun_aimed_at) aimedAtName, (SELECT player_color FROM player WHERE player_id=gun_aimed_at) aimedAtColor ";
				$sql .= "FROM guns g ";
				$sql .= "LEFT JOIN `playerPositioning` pp ON (pp.player_id=g.gun_held_by AND pp.player_asking=$askingPlayer) ";
				$sql .= "LEFT JOIN `playerPositioning` pp2 ON (pp2.player_id=g.gun_aimed_at AND pp2.player_asking=$askingPlayer) ";

				return self::getCollectionFromDb( $sql );


		}

		function getGunsHeldByPlayer($playerId)
		{
				$sql = "SELECT * FROM guns WHERE gun_held_by=$playerId ";

				return self::getCollectionFromDb( $sql );
		}

		function getGunsShooting()
		{
				$sql = "SELECT * FROM guns WHERE gun_state='shooting' ";

				return self::getCollectionFromDb( $sql );
		}

		function getGunsHeld()
		{
				$sql = "SELECT * FROM guns WHERE gun_held_by IS NOT NULL AND gun_held_by<>'' ";

				return self::getCollectionFromDb( $sql );
		}

		function getGunsNotHeld()
		{
				$sql = "SELECT * FROM guns WHERE gun_held_by NULL OR gun_held_by='' ";

				return self::getCollectionFromDb( $sql );
		}

		function getAllGuns()
		{
				$sql = "SELECT * FROM guns ";

				return self::getCollectionFromDb( $sql );
		}

		function getHeldUnaimedGuns()
		{
				$sql = "SELECT * FROM guns WHERE gun_held_by IS NOT NULL AND gun_held_by<>'' AND (gun_aimed_at IS NULL OR gun_aimed_at='') ";

				return self::getCollectionFromDb( $sql );
		}

		function getGunRotationsForPlayer($askingPlayer)
		{
				$rotationArray = array();

				$guns = $this->getGunsForPlayer($askingPlayer);
				foreach( $guns as $gun )
				{
						$gunId = $gun['gun_id']; // internal id
						$gunHolderLetter = $gun['letterPositionHeldBy'];
						$aimedAtLetter = $gun['letterPositionAimedAt'];

						$rotation = $this->getGunRotationFromLetters($gunHolderLetter, $aimedAtLetter); // find the rotation for this gun
						$isPointingLeft = $this->getIsGunPointingLeft($gunHolderLetter, $aimedAtLetter); // decide if we use the gun that points left or right

						$rotationArray[$gunId] = array( 'gun_id' => $gunId, 'rotation' => $rotation, 'is_pointing_left' => $isPointingLeft); // save this rotation to the array
				}

				return $rotationArray;
		}

		function getGunRotationFromLetters($gunHolderLetter, $aimedAtLetter)
		{
				if(is_null($gunHolderLetter) || is_null($aimedAtLetter))
				{ // either the gun is not being held by a player or it is not aimed
						return 0;
				}

				$rotationArray = array();

				if(true)
				{
						// because it will be faster than querying a database table, create a 2D array to hold how much a gun should be rotated based on where it is aimed
						$rotationArray['a'] = array( 'a' => 90, 'b' => 70, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
						$rotationArray['b'] = array( 'a' => 80, 'b' => 0, 'c' => -20, 'd' => 15, 'e' => -50, 'f' => 0, 'g' => 105, 'h' => 50);
						$rotationArray['c'] = array( 'a' => -70, 'b' => -15, 'c' => 90, 'd' => 65, 'e' => 200, 'f' => 45, 'g' => 160, 'h' => 90);
						$rotationArray['d'] = array( 'a' => -30, 'b' => 15, 'c' => 75, 'd' => 0, 'e' => 230, 'f' => -45, 'g' => 180, 'h' => 105);
						$rotationArray['e'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
						$rotationArray['f'] = array( 'a' => -30, 'b' => 15, 'c' => 75, 'd' => -115, 'e' => 230, 'f' => -45, 'g' => 180, 'h' => 105);
						$rotationArray['g'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
						$rotationArray['h'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
				}
				else
				{
						// because it will be faster than querying a database table, create a 2D array to hold how much a gun should be rotated based on where it is aimed
						$rotationArray['a'] = array( 'a' => 90, 'b' => 70, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
						$rotationArray['b'] = array( 'a' => 80, 'b' => 0, 'c' => -20, 'd' => 15, 'e' => -50, 'f' => 0, 'g' => 105, 'h' => 50);
						$rotationArray['c'] = array( 'a' => -70, 'b' => -15, 'c' => 90, 'd' => 65, 'e' => 200, 'f' => 45, 'g' => 160, 'h' => 90);
						$rotationArray['d'] = array( 'a' => -30, 'b' => 15, 'c' => 75, 'd' => 0, 'e' => 230, 'f' => -45, 'g' => 180, 'h' => 105);
						$rotationArray['e'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
						$rotationArray['f'] = array( 'a' => -30, 'b' => 15, 'c' => 75, 'd' => -115, 'e' => 230, 'f' => -45, 'g' => 180, 'h' => 105);
						$rotationArray['g'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
						$rotationArray['h'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15);
				}

				return $rotationArray[$gunHolderLetter][$aimedAtLetter];
		}

		function getIsGunPointingLeft($gunHolderLetter, $aimedAtLetter)
		{
				if(is_null($gunHolderLetter) || is_null($aimedAtLetter))
				{ // either the gun is not being held by a player or it is not aimed
						return 0;
				}

				// because it will be faster than querying a database table, create a 2D array to hold whether a gun is pointed left or right based on where the holder is sitting and where it is aimed
				$isLeftArray = array();
				$isLeftArray['a'] = array( 'a' => 0, 'b' => 1, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 0);
				$isLeftArray['b'] = array( 'a' => 0, 'b' => 1, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0);
				$isLeftArray['c'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 1, 'f' => 0, 'g' => 1, 'h' => 1);
				$isLeftArray['d'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 1, 'f' => 1, 'g' => 1, 'h' => 1);
				$isLeftArray['e'] = array( 'a' => 0, 'b' => 1, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 0);
				$isLeftArray['f'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 1, 'e' => 1, 'f' => 1, 'g' => 1, 'h' => 1);
				$isLeftArray['g'] = array( 'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 0);
				$isLeftArray['h'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 1, 'f' => 0, 'g' => 1, 'h' => 1);

				return $isLeftArray[$gunHolderLetter][$aimedAtLetter];
		}

		function getEliminatedPlayers($askingPlayer)
		{
				$eliminatedPlayers = array();

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player
						$isEliminated = $player['player_eliminated']; // 1 if player is wounded

						if($isEliminated == 1)
						{ // this player is eliminated

								$eliminatedPlayerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of the wounded player from the player asking's perspective

								$eliminatedPlayers[$playerId] = array( 'playerId' => $playerId, 'playerLetter' => $eliminatedPlayerLetterOrder); // save the eliminated player info to the 2D array we will be returning
						}
				}

				return $eliminatedPlayers;
		}

		// The ASKING PLAYER wants to know where WOUNDED TOKENS should be placed.
		function getWoundedTokensForPlayer($askingPlayer)
		{
				$woundedTokens = array();

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player
						$isWounded = $player['is_wounded']; // 1 if player is wounded

						if($isWounded == 1)
						{ // this player is wounded

								$woundedPlayerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of the wounded player from the player asking's perspective
								$leaderCardPosition = $this->getLeaderCardPositionFromPlayer($playerId); // the integrity card position of the leader card (1, 2, 3)
								$cardType = $this->getCardIdFromPlayerAndPosition($playerId, $leaderCardPosition); // agent or kingpin

								$woundedTokens[$playerId] = array( 'player_id' => $playerId, 'woundedPlayerLetterOrder' => $woundedPlayerLetterOrder, 'leaderCardPosition' => $leaderCardPosition, 'cardType' => $cardType); // save the wounded token info to the 2D array we will be returning
						}
				}

				return $woundedTokens;
		}

		// Return the name of the current state.
		public function getStateName()
		{
       $state = $this->gamestate->state();
       return $state['name'];
   	}

		function getCardIdFromPlayerAndPosition($playerId, $positionId)
		{
				return self::getUniqueValueFromDb("SELECT card_id FROM integrityCards WHERE card_location=$playerId AND card_location_arg=$positionId");
		}

		function getCardTypeFromCardId($cardId)
		{
				return self::getUniqueValueFromDb("SELECT card_type FROM integrityCards WHERE card_id=$cardId");
		}

		function getCardTypeFromPlayerIdAndPosition($playerId, $positionId)
		{
				return self::getUniqueValueFromDb("SELECT card_type FROM integrityCards WHERE card_location=$playerId AND card_location_arg=$positionId");
		}

		function getIntegrityCardOwner($integrityCardId)
		{
				return self::getUniqueValueFromDb("SELECT card_location FROM integrityCards WHERE card_id=$integrityCardId");
		}

		function getIntegrityCardPosition($integrityCardId)
		{
				return self::getUniqueValueFromDb("SELECT card_location_arg FROM integrityCards WHERE card_id=$integrityCardId");
		}

		function getIntegrityCardFlippedState($integrityCardId)
		{
				return self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_id=$integrityCardId");
		}

		// If this CARD_ID is REVEALED, return 1, otherwise return 0.
		function getCardRevealedStatus($card_id)
		{
				return self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_id=$card_id");
		}

		// Get the player number (1, 2, 3, 4, 5, 6, 7, 8) from the player ID (1234567).
		function getPlayerNumberFromPlayerId($playerId)
		{
				return self::getUniqueValueFromDb("SELECT player_no FROM player WHERE player_id=$playerId");
		}

		function getAllIntegrityCards()
		{
				return self::getObjectListFromDB( "SELECT *
																					 FROM integrityCards" );
		}

		function getIntegrityCardsForPlayer($playerId)
		{
				return self::getObjectListFromDB( "SELECT * FROM integrityCards WHERE card_location=$playerId");
		}

		function setVisibilityOfIntegrityCard($cardId, $playerId, $seenValue)
		{
				$sql = "UPDATE playerCardVisibility SET is_seen=$seenValue WHERE card_id=$cardId AND player_id=$playerId";
				self::DbQuery( $sql );
		}

		function isIntegrityCardHidden($cardId)
		{
				$hiddenValue = self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_id=$cardId");
				if($hiddenValue == 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function getLetterOrderPosition($askingPlayerOrder, $otherPlayerOrder, $numberOfPlayers)
		{
				$orderingArray = array('a','b','c','f','e','h','g','d');
				$difference = $otherPlayerOrder - $askingPlayerOrder;
				$newIndex = $difference; // if difference is positive, the correct place in the array is just the difference
				if($difference < 0)
				{ // the ordering difference between the two players is negative
					$newIndex = $numberOfPlayers + $difference; // we need to subtract the difference from the max number of players to find the correct ordere
				}

				return $orderingArray[$newIndex];
		}

		function getLetterOrderFromPlayerIds($askingPlayer, $targetPlayer)
		{
				return self::getUniqueValueFromDb("SELECT player_position FROM playerPositioning WHERE player_asking=$askingPlayer AND player_id=$targetPlayer");
		}

		function getPlayerIdFromLetterOrder($playerAsking, $letterOrder)
		{
				$sql = "SELECT p.player_id FROM `playerPositioning` pp ";
				$sql .= "JOIN `player` p ON p.player_id=pp.player_id ";
				$sql .= "WHERE pp.player_asking=$playerAsking AND pp.player_position='$letterOrder' ";

				return self::getUniqueValueFromDb($sql);
		}

		function getPlayerNameFromLetterOrder($playerAsking, $letterOrder)
		{
				$sql = "SELECT p.player_name FROM `playerPositioning` pp ";
				$sql .= "JOIN `player` p ON p.player_id=pp.player_id ";
				$sql .= "WHERE pp.player_asking=$playerAsking AND pp.player_position='$letterOrder' ";

				return self::getUniqueValueFromDb($sql);
		}

		// Convert a player ID into a player NAME.
		function getPlayerNameFromPlayerId($playerId)
		{
				$sql = "SELECT player_name FROM `player` ";
				$sql .= "WHERE player_id=$playerId ";

				if(is_null($playerId) || $playerId == '')
				{
						return 10;
				}

				$name = self::getUniqueValueFromDb($sql);

				return $name;
		}

		function getPlayerColorFromId($playerId)
		{
				$sql = "SELECT player_color FROM `player` ";
				$sql .= "WHERE player_id=$playerId ";

				$color = self::getUniqueValueFromDb($sql);

				return $color;
		}

		function getKingpinPlayerId()
		{
				$sql = "SELECT card_location FROM `integrityCards` ";
				$sql .= "WHERE card_type='kingpin' ";

				return self::getUniqueValueFromDb($sql);
		}

		function getAgentPlayerId()
		{
				$sql = "SELECT card_location FROM `integrityCards` ";
				$sql .= "WHERE card_type='agent' ";

				return self::getUniqueValueFromDb($sql);
		}

		function canPlayerInvestigate($playerAsking)
		{
				$players = $this->getPlayersDeets(); // get player details
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id'];

						$hiddenCards = $this->getHiddenCardsFromPlayer($playerId);
						if(count($hiddenCards) > 0)
						{ // they have at least one hidden card
								return true;
						}
				}

				return false; // no players have hidden cards
		}

		function canPlayerArm($playerAsking)
		{
				if($this->isPlayerHoldingGun($playerAsking))
				{ // they are already holding a gun
						return false;
				}

				$allGuns = $this->getAllGuns();
				foreach( $allGuns as $gun )
				{ // go through each gun
						$gunId = $gun['gun_id'];
						$gunState = $gun['gun_state'];
						$gunHeldBy = $gun['gun_held_by'];

						if(is_null($gunHeldBy) || $gunHeldBy == '')
						{ // this gun is available
								return true; // as long as at least one gun is available
						}
				}

				return false; // the player is not holding a gun but there are no guns available
		}

		function canPlayerShoot($playerAsking)
		{
				if($this->isPlayerHoldingGun($playerAsking))
				{ // they are holding a gun
						$gunId = $this->getGunIdHeldByPlayer($playerAsking);
						$gunTarget = $this->getPlayerIdOfGunTarget($gunId);

						if(!is_null($gunTarget) && $gunTarget != '')
						{ // the gun is aimed at someone
								if(!$this->isPlayerEliminated($gunTarget))
								{ // the gun target is NOT eliminated
										return true;
								}
						}
				}

				return false; // we're not holding a gun or our gun is not aimed or we're aimed at an eliminated player
		}

		function isPlayerALeader($playerId)
		{
				$kingpinPlayerId = $this->getKingpinPlayerId();
				$agentPlayerId = $this->getAgentPlayerId();

				if($playerId == $kingpinPlayerId || $playerId == $agentPlayerId)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		// Returns true if the player is WOUNDED, false otherwise.
		function isPlayerWounded($playerId)
		{
				$sql = "SELECT is_wounded FROM `player` ";
				$sql .= "WHERE player_id=$playerId ";

				$isWoundedInt = self::getUniqueValueFromDb($sql);

				if($isWoundedInt == 1)
				{ // player is wounded
						return true;
				}
				else
				{ // player is NOT wounded
						return false;
				}
		}

		function countUnwoundedLeaders()
		{
				$countUnwoundedLeaders = 0;
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying
						if($this->isPlayerALeader($playerId))
						{ // this is a leader
								if(!$this->isPlayerWounded($playerId))
								{ // they are NOT wounded
										$countUnwoundedLeaders++;
								}
						}
				}

				return $countUnwoundedLeaders;
		}

		// Returns true if the player is ELIMINATED, false otherwise.
		function isPlayerEliminated($playerId)
		{
				$sql = "SELECT player_eliminated FROM `player` ";
				$sql .= "WHERE player_id=$playerId ";

				$isEliminatedInt = self::getUniqueValueFromDb($sql);

				if($isEliminatedInt == 1)
				{ // player is eliminated
						return true;
				}
				else
				{ // player is NOT eliminated
						return false;
				}
		}

		function isPlayerHoldingGun($playerId)
		{
				// get all guns held by this player
				$sql = "SELECT gun_id FROM `guns` ";
				$sql .= "WHERE gun_held_by=$playerId ";

				$gunsHeldByPlayer = self::getObjectListFromDB( $sql );

				if(count($gunsHeldByPlayer) > 0)
				{ // player is holding a gun
						return true;
				}
				else
				{ // player is NOT holding a gun
						return false;
				}
		}

		function isPlayerHoldingEquipment($playerId)
		{
				$equipmentInHand = $this->getEquipmentInPlayerHand($playerId);

				if(count($equipmentInHand) > 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function doesPlayerNeedToDiscard($playerWhoseTurnItIs)
		{
				$equipmentInHand = $this->getEquipmentInPlayerHand($playerWhoseTurnItIs);

				if(count($equipmentInHand) > 1)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function isInstantEquipment($collectorNumber)
		{
				switch($collectorNumber)
				{
						default:
								return false;
				}
		}

		function getPlayerDisplayInfo($playerAsking)
		{
				$sql = "SELECT pp.player_position, p.player_name, p.player_color FROM `playerPositioning` pp ";
				$sql .= "JOIN `player` p ON p.player_id=pp.player_id ";
				$sql .= "WHERE pp.player_asking=$playerAsking ";

				return self::getObjectListFromDB( $sql );
		}

		function getEquipmentInPlayerHand($playerAsking)
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE card_location='hand' AND equipment_owner=$playerAsking";

				return self::getObjectListFromDB( $sql );
		}

		function getLastPlayerInvestigated($playerId)
		{
				return self::getUniqueValueFromDb("SELECT last_player_investigated FROM player WHERE player_id=$playerId");
		}

		function getLastCardPositionInvestigated($playerId)
		{
				return self::getUniqueValueFromDb("SELECT last_card_position_investigated FROM player WHERE player_id=$playerId");
		}

		function getLastCardPositionRevealed($playerId)
		{
				return self::getUniqueValueFromDb("SELECT last_card_position_revealed FROM player WHERE player_id=$playerId");
		}

		function getLeaderCardPositionFromPlayer($playerId)
		{
				return self::getUniqueValueFromDb("SELECT card_location_arg FROM integrityCards WHERE card_location=$playerId AND (card_type='agent' OR card_type='kingpin') LIMIT 1");
		}

		function convertCardPositionToFriendlyName($cardPosition)
		{
				if($cardPosition == 1 || $cardPosition == '1')
				{
					return "LEFT";
				}
				elseif($cardPosition == 2 || $cardPosition == '2')
				{
					return "MIDDLE";
				}
				else
				{
					return "RIGHT";
				}
		}

		function getEquipmentCardIdInUse()
		{
				return self::getUniqueValueFromDb("SELECT card_id FROM equipmentCards WHERE card_location='playing' LIMIT 1 ");
		}

		function getCoffeeId()
		{
				return self::getUniqueValueFromDb("SELECT card_id FROM equipmentCards WHERE card_type_arg=2 LIMIT 1 ");
		}

		function isTurnOrderClockwise()
		{
				$isSmokeGrenadeActive = self::getUniqueValueFromDb("SELECT equipment_is_active FROM equipmentCards WHERE card_type_arg=12 LIMIT 1 ");

				if($isSmokeGrenadeActive == 1)
				{ // smoke grenade has been played so we are going COUNTER-CLOCKWISE
						return false;
				}
				else
				{ // smnoke grenade has not been played so we are going CLOCKWISE
						return true;
				}
		}

		// Return true if this player has Planted Evidence played on them, false otherwise.
		function hasPlantedEvidence($playerId)
		{
				$sql = "SELECT card_id FROM equipmentCards WHERE card_type_arg=8 && equipment_is_active=1 && player_target_1=$playerId LIMIT 1 ";
        $plantedEvidenceMatches = self::getCollectionFromDb( $sql );

				if(count($plantedEvidenceMatches) > 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		// Return true if this player has Disguise played on them, false otherwise.
		function hasDisguise($playerId)
		{
				$sql = "SELECT card_id FROM equipmentCards WHERE card_type_arg=30 && equipment_is_active=1 && player_target_1=$playerId LIMIT 1 ";
        $matches = self::getCollectionFromDb( $sql );

				if(count($matches) > 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		// Return true if this player has Deliriant played on them, false otherwise.
		function hasDeliriant($playerId)
		{
				$sql = "SELECT card_id FROM equipmentCards WHERE card_type_arg=17 && equipment_is_active=1 && player_target_1=$playerId LIMIT 1 ";
        $matches = self::getCollectionFromDb( $sql );

				if(count($matches) > 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		// Return true if this player has Surveillance Camera played on them, false otherwise.
		function hasSurveillanceCamera($playerId)
		{
				$sql = "SELECT card_id FROM equipmentCards WHERE card_type_arg=13 && equipment_is_active=1 && player_target_1=$playerId LIMIT 1 ";
        $matches = self::getCollectionFromDb( $sql );

				if(count($matches) > 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function getEquipmentPlayedInState($equipmentId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_played_in_state FROM equipmentCards WHERE card_id=$equipmentId LIMIT 1 ");
		}

		function getEquipmentPlayedOnTurn($equipmentId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_played_on_turn FROM equipmentCards WHERE card_id=$equipmentId LIMIT 1 ");
		}

		function getCollectorNumberFromId($cardId)
		{
				return self::getUniqueValueFromDb("SELECT card_type_arg FROM equipmentCards WHERE card_id=$cardId LIMIT 1 ");
		}

		function getEquipmentCardOwner($cardId)
		{
				$equipmentOwner = self::getUniqueValueFromDb( "SELECT equipment_owner FROM equipmentCards
	                                                         WHERE card_id=$cardId " );
				if(is_null($equipmentOwner) || $equipmentOwner == '')
				{ // for some reason, this query is returning empty when it shouldn't be and I don't know why
						return self::getUniqueValueFromDb( "SELECT card_location_arg FROM equipmentCards
			                                                         WHERE card_id=$cardId " );
				}
				else
				{ // not empty so it's doing what it's supposed to be doing
						return $equipmentOwner;
				}
		}

		function getEquipmentName($cardId)
		{
				return self::getUniqueValueFromDb( "SELECT equipment_name FROM equipmentCards
	                                                         WHERE card_id=$cardId " );
		}

		function setEquipmentName($collectorNumber, $name)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "equipment_name='$name' WHERE ";
				$sqlUpdate .= "card_type_arg=$collectorNumber";

				self::DbQuery( $sqlUpdate );
		}

		function getTranslatedEquipmentName($collectorNumber)
		{
				switch($collectorNumber)
				{
						case 2: // coffee
							return clienttranslate( 'Coffee' );

						case 8: // planted evidence
							return clienttranslate( 'Planted Evidence' );

						case 12: // smoke grenade
							return clienttranslate( 'Smoke Grenade' );

						case 15: // truth serum
							return clienttranslate( 'Truth Serum' );

						case 16: // wiretap
							return clienttranslate( 'Wiretap' );

						case 44: // riot shield
							return clienttranslate( 'Riot Shield' );

						case 11: // restraining order
							return clienttranslate( 'Restraining Order' );

						case 37: // mobile detonator
							return clienttranslate( 'Mobile Detonator' );

						case 4: // evidence bag
							return clienttranslate( 'Evidence Bag' );

						case 35: // med kit
							return clienttranslate( 'Med Kit' );

						case 14: // taser
							return clienttranslate( 'Taser' );

						case 3: // Defibrillator
								return clienttranslate( 'Defibrillator' );

						case 1: // Blackmail
								return clienttranslate( 'Blackmail' );

						case 30: // Disguise
								return clienttranslate( 'Disguise' );

						case 45: // Walkie Talkie
								return clienttranslate( 'Walkie Talkie' );

						case 9: // Polygraph
								return clienttranslate( 'Polygraph' );

						case 13: // Surveillance Camera
								return clienttranslate( 'Surveillance Camera' );

						case 7: // Metal Detector
								return clienttranslate( 'Metal Detector' );

						case 17: // Deliriant
								return clienttranslate( 'Deliriant' );

						case 6: // K-9 Unit
								return clienttranslate( 'K-9 Unit' );

						default:
							return clienttranslate( 'Equipment' );

				}
		}

		function getTranslatedEquipmentEffect($collectorNumber)
		{
				switch($collectorNumber)
				{
						case 2: // coffee
							return clienttranslate( 'Take your turn after the current turn ends.' );

						case 8: // planted evidence
							return clienttranslate( 'Choose a player. All of their Honest cards are now Crooked and all of their Crooked cards are now Honest.' );

						case 12: // smoke grenade
							return clienttranslate( 'Permanently reverse the turn order.' );

						case 15: // truth serum
							return clienttranslate( 'Reveal any Integrity Card.' );

						case 16: // wiretap
							return clienttranslate( 'Investigate any 2 players.' );

						case 44: // riot shield
							return clienttranslate( 'Use when any player is shot. That player chooses someone to their left or right to be shot instead.' );

						case 11: // restraining order
							return clienttranslate( 'Use when someone shoots. They must aim at a different palyer before they shoot.' );

						case 37: // mobile detonator
							return clienttranslate( 'When a player is shot, choose another non-wounded player to also be shot.' );

						case 4: // evidence bag
							return clienttranslate( 'Give an Equipment Card held by one player to another player.' );

						case 35: // med kit
							return clienttranslate( 'Remove a Wounded Token.' );

						case 14: // taser
							return clienttranslate( 'Use only on your turn. Steal a Gun from any player. You may not shoot it this turn.' );

						case 3: // Defibrillator
								return clienttranslate( 'Bring another non-leader player back to life.' );

						case 1: // Blackmail
								return clienttranslate( 'Choose 2 Integrity Cards held by other players. Those cards are exchanged.' );

						case 30: // Disguise
								return clienttranslate( 'Choose a player who cannot be investigated for the rest of the game.' );

						case 45: // Walkie Talkie
								return clienttranslate( 'Aim all Guns at one player.' );

						case 9: // Polygraph
								return clienttranslate( 'Investigate all of a player\'s Integrity Cards. They investigate all of yours.' );

						case 13: // Surveillance Camera
								return clienttranslate( 'Choose a player. Each time one of their Integrity Cards is investigated, they must reveal it.' );

						case 7: // Metal Detector
								return clienttranslate( 'Investigate each player who is holding a Gun.' );

						case 17: // Deliriant
								return clienttranslate( 'Choose a player who must hide and shuffle their Integrity Cards. They may not view them for the rest of the game.' );

						case 6: // K-9 Unit
								return clienttranslate( 'Choose a player to drop their Gun.' );

						default:
							return clienttranslate( 'Equipment' );

				}
		}

		function swapIntegrityCards($cardId1, $cardId2)
		{
				$oldOwnerOfCardId1 = $this->getIntegrityCardOwner($cardId1);
				$oldOwnerOfCardId2 = $this->getIntegrityCardOwner($cardId2);
				$oldPosition1 = $this->getIntegrityCardPosition($cardId1); // 1, 2, 3
				$oldPosition2 = $this->getIntegrityCardPosition($cardId2); // 1, 2, 3
				$oldHiddenState1 = $this->getIntegrityCardFlippedState($cardId1); // 1 if revealed
				$oldHiddenState2 = $this->getIntegrityCardFlippedState($cardId2); // 0 if hidden

				$sqlUpdate1 = "UPDATE integrityCards SET ";
				$sqlUpdate1 .= "card_location='$oldOwnerOfCardId2',card_location_arg=$oldPosition2, card_type_arg=$oldHiddenState2 WHERE ";
				$sqlUpdate1 .= "card_id=$cardId1";
				self::DbQuery( $sqlUpdate1 );


				$sqlUpdate2 = "UPDATE integrityCards SET ";
				$sqlUpdate2 .= "card_location='$oldOwnerOfCardId1',card_location_arg=$oldPosition1, card_type_arg=$oldHiddenState1 WHERE ";
				$sqlUpdate2 .= "card_id=$cardId2";
				self::DbQuery( $sqlUpdate2 );

				$card1NewOwnerName = $this->getPlayerNameFromPlayerId($oldOwnerOfCardId2);
				$card2NewOwnerName = $this->getPlayerNameFromPlayerId($oldOwnerOfCardId1);

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying
						$card1OriginalPosition = $this->getIntegrityCardPosition($cardId1);
						$card2OriginalPosition = $this->getIntegrityCardPosition($cardId2);
						$card1PlayerLetter = $this->getLetterOrderFromPlayerIds($playerId, $oldOwnerOfCardId1);
						$card2PlayerLetter = $this->getLetterOrderFromPlayerIds($playerId, $oldOwnerOfCardId2);

						self::notifyPlayer( $playerId, "integrityCardsExchanged", clienttranslate( '${player_name} and ${player_name_2} have exchanged Integrity Cards.' ), array(
							'player_name' => $card1NewOwnerName,
						  'player_name_2' => $card2NewOwnerName,
						  'card1OriginalPosition' => $card1OriginalPosition,
							'card2OriginalPosition' => $card2OriginalPosition,
							'card1PlayerLetter' => $card1PlayerLetter,
							'card2PlayerLetter' => $card2PlayerLetter
						) );
				}
		}

		function getEquipmentTarget1($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_1 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function getEquipmentTarget2($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_2 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function getEquipmentTarget3($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_3 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function getEquipmentTarget4($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_4 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function getPlayerTarget1($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT player_target_1 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function getPlayerTarget2($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT player_target_2 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function getGunTarget1($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT gun_target_1 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function getGunTarget2($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT gun_target_2 FROM equipmentCards WHERE card_id=$equipmentCardId");
		}

		function setLastPlayerInvestigated($playerInvestigating, $playerBeingInvestigated)
		{
				// set player.last_player_investigated
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "last_player_investigated=$playerBeingInvestigated WHERE ";
				$sqlUpdate .= "player_id='$playerInvestigating'";

				self::DbQuery( $sqlUpdate );
		}

		function setLastCardPositionInvestigated($playerInvestigating, $cardPosition)
		{
				// set player.last_card_position_investigated
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "last_card_position_investigated=$cardPosition WHERE ";
				$sqlUpdate .= "player_id='$playerInvestigating'";

				self::DbQuery( $sqlUpdate );
		}

		function setLastCardPositionRevealed($playerRevealing, $cardPosition)
		{
				// set player.last_card_position_investigated
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "last_card_position_revealed=$cardPosition WHERE ";
				$sqlUpdate .= "player_id='$playerRevealing'";

				self::DbQuery( $sqlUpdate );
		}

		function setEquipmentCardState($activePlayerId, $equipmentId, $stateName)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "equipment_played_in_state='$stateName',card_location='playing',equipment_played_on_turn=$activePlayerId WHERE ";
				$sqlUpdate .= "card_id=$equipmentId";

				self::DbQuery( $sqlUpdate );
		}

		function setEquipmentCardOwner($cardId, $playerId)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "equipment_owner=$playerId WHERE ";
				$sqlUpdate .= "card_id=$cardId";

				self::DbQuery( $sqlUpdate );
		}

		// Set all players who are holding equipment cards to active.
		function setEquipmentHoldersToActive()
		{
				$this->gamestate->setAllPlayersMultiactive(); // set all players to active

				$players = $this->getPlayersDeets(); // get players
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id'];

						if(!$this->isPlayerHoldingEquipment($playerId))
						{ // this player is not holding equipment
								$this->gamestate->setPlayerNonMultiactive( $playerId, "allPassedOnReactions" ); // just make them inactive
						}
				}

		}

		function makeCentralEquipmentActive($cardId)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "equipment_is_active=1 WHERE ";
				$sqlUpdate .= "card_id=$cardId";

				//var_dump( $sqlUpdate );
				//die('ok');

				self::DbQuery( $sqlUpdate );

				// notify everyone that an equipment card is now active
				$equipmentOwner = $this->getEquipmentCardOwner($cardId); // get the player ID of the player who played the equipment
				$equipmentName = $this->getEquipmentName($cardId); // get the name of this equipment
				$equipmentOwnerPlayerName = $this->getPlayerNameFromPlayerId($equipmentOwner); // name of the player being investigated
				$collectorNumber = $this->getCollectorNumberFromId($cardId);

				$equipName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying
						$playerLetter = $this->getLetterOrderFromPlayerIds($playerId, $equipmentOwner); // the letter order from the player who we are sending this to's perspective

						self::notifyPlayer( $playerId, "activateCentralEquipment", clienttranslate( '${player_name} has activated ${equipment_name}.' ), array(
												 'player_name' => $equipmentOwnerPlayerName,
												 'equipment_name' => $equipmentName,
						 						 'equipment_id' => $cardId,
						 						 'collector_number' => $collectorNumber,
						 						 'player_letter' => $playerLetter,
												 'equipment_name' => $equipName,
												 'equipment_effect' => $equipEffect
						) );
				}
		}

		function makePlayerEquipmentActive($cardId, $targetPlayer)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "equipment_is_active=1,card_location='active' WHERE ";
				$sqlUpdate .= "card_id=$cardId";

				//var_dump( $sqlUpdate );
				//die('ok');

				self::DbQuery( $sqlUpdate );

				$targetPlayerName = $this->getPlayerNameFromPlayerId($targetPlayer);

				$collectorNumber = $this->getCollectorNumberFromId($cardId);
				$equipmentName = $this->getEquipmentName($cardId); // get the name of this equipment
				$equipmentOwner = $this->getEquipmentCardOwner($cardId); // get the player ID of the player who played the equipment
				$equipmentOwnerPlayerName = $this->getPlayerNameFromPlayerId($equipmentOwner);

				$equipName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying
						$playerLetterPlaying = $this->getLetterOrderFromPlayerIds($playerId, $equipmentOwner); // the letter order from the player who we are sending this to's perspective
						$playerLetterReceiving = $this->getLetterOrderFromPlayerIds($playerId, $targetPlayer); // the letter order from the player who we are sending this to's perspective

						self::notifyPlayer( $playerId, 'activatePlayerEquipment', clienttranslate( '${player_name} has activated ${equipment_name} targeting ${target_player_name}.' ), array(
												 'player_name' => $equipmentOwnerPlayerName,
												 'target_player_name' => $targetPlayerName,
												 'equipment_name' => $equipmentName,
						 						 'equipment_id' => $cardId,
						 						 'collector_number' => $collectorNumber,
												 'player_letter_playing' => $playerLetterPlaying,
						 						 'player_letter_receiving' => $playerLetterReceiving,
												 'equipment_name' => $equipName,
												 'equipment_effect' => $equipEffect
						) );
				}
		}

		function resetEquipmentAfterDiscard($cardId)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "equipment_owner=0,equipment_played_in_state='',equipment_target_1='',equipment_target_2='',equipment_target_3='',equipment_target_4='',player_target_1='',player_target_2='',equipment_is_active=0 WHERE ";
				$sqlUpdate .= "card_id=$cardId";

				self::DbQuery( $sqlUpdate );
		}

		// Return true if it is valid to play this equipment right now. Throw an error otherwise to explain why it can't be used.
		function validateEquipmentUsage($equipmentCardId)
		{
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);
				switch($collectorNumber)
				{
						case 2: // coffee
							return true; // this is always valid to use
						break;
						case 8: // planted evidence
							return true; // this is always valid to use
						break;
						case 12: // smoke grenade
							return true; // this is always valid to use
						break;
						case 15: // truth serum
							return true; // this is always valid to use
						break;
						case 16: // wiretap
							return true; // this is always valid to use
						break;

						case 44: // riot shield
							return true; // this is always valid to use
						break;

						case 11: // restraining order
							return true; // this is always valid to use
						break;

						case 37: // mobile detonator
							return true; // this is always valid to use
						break;

						case 4: // evidence bag
							return true; // this is always valid to use
						break;

						case 35: // med kit
							return true; // this is always valid to use
						break;

						case 14: // taser
								$playerUsingEquipment = $this->getEquipmentCardOwner($equipmentCardId);
								$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)

								if($playerWhoseTurnItIs != $playerUsingEquipment)
								{ // it's not this player's turn
										return false;
								}

								$guns = $this->getGunsHeld(); // get all guns currently being held by a player
								foreach( $guns as $gun )
								{ // go through each gun that is currently held
										$gunHolderPlayerId = $gun['gun_held_by']; // get the PLAYER ID of the player holding this gun

										if($gunHolderPlayerId != $playerUsingEquipment)
										{ // this gun is held by a different player
												return true; // this means there is at least one player other than the equipment user holding a gun
										}
								}

								return false; // if we get here, there isn't at leats one player other than the equipment user holding a gun
						break;

						case 3: // Defibrillator
								return true; // this is always valid
						break;
						case 1: // Blackmail
								return true; // this is always valid
						break;
						case 30: // Disguise
								return true; // this is always valid
						break;
						case 45: // Walkie Talkie
								$guns = $this->getGunsHeld(); // get all guns currently being held by a player
								if(count($guns) > 0)
								{ // there is at least one gun is being held by a player
										return true;
								}
								else
								{
										return false;
								}
						break;
						case 9: // Polygraph
								return true; // this is always valid
						break;
						case 13: // Surveillance Camera
								return true; // this is always valid
						break;
						case 7: // Metal Detector
								$guns = $this->getGunsHeld(); // get all guns currently being held by a player
								if(count($guns) > 0)
								{ // there is at least one gun is being held by a player
										return true;
								}
								else
								{
										return false;
								}
						break;
						case 17: // Deliriant
								return true; // this is always valid
						break;
						case 6: // K-9 Unit
								$gunsHeld = $this->getGunsHeld(); // get all guns currently being held by a player
								if(count($gunsHeld) > 0)
								{ // at least one player is holding a gun
										return true;
								}
								else
								{
										return false;
								}
						break;

						default:
							return false; // return false by default
							break;

				}
		}

		// Called when someone clicks a player as an Equipment target. Return true if they can use it. Throw an error if not
		// explaining why it can't be used.
		function validateEquipmentPlayerSelection($playerId, $equipmentCardId)
		{
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);
				$equipmentCardOwner = $this->getEquipmentCardOwner($equipmentCardId); // get the player ID who is playing the equipment card
				switch($collectorNumber)
				{
						case 2: // coffee
							return true;
						break;
						case 8: // planted evidence
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										throw new BgaUserException( self::_("Please target a living player.") );
								}
								else
								{ // they are targeting a living player
										return true;
								}
						break;
						case 44: // riot shield
						// TODO: Make sure the player chosen is to the left or right of the player being shot and is not eliminated
						return true;
						break;
						case 11: // restraining order
						// TODO: Make sure it is a different player than the one being shot and that they are not eliminated.
						return true;
						break;
						case 37: // mobile detonator
						// TODO: Make sure the chosen player is not wounded or eliminated.

						// get the player whose turn it is
						// get the gun they are holding
						// get the target of that gun
						// make sure player1 is not the target of that gun
						// make sure player1 is not wounded

						return true;
						break;

						case 35: // med kit
						if(!$this->isPlayerWounded($playerId))
						{ // the selected player is NOT wounded
								throw new BgaUserException( self::_("Please choose a wounded player.") );
						}
						return true;
						break;
						case 14: // taser
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										throw new BgaUserException( self::_("Please target a living player.") );
								}
								elseif(!$this->isPlayerHoldingGun($playerId))
								{ // player isn't holding a gun
										throw new BgaUserException( self::_("Please target a player holding a gun.") );
								}
								else
								{ // they are targeting a living player holding a gun
										return true;
								}
								return false; // won't get here
						break;
						case 4: // evidence bag
								$ownerOfEvidenceBag = $this->getEquipmentCardOwner($equipmentCardId);
								if($playerId == $ownerOfEvidenceBag)
								{ // they are trying to give an equipment card to themselves
										throw new BgaUserException( self::_("You must choose a player other than yourself.") );
								}
						return true;
						break;
						case 3: // Defibrillator
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										return true;
								}
								else
								{ // they are targeting a living player
										throw new BgaUserException( self::_("Please target an eliminated player.") );
								}
						break;
						case 30: // Disguise
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										throw new BgaUserException( self::_("Please target a living player.") );
								}
								else
								{ // they are targeting a living player
										return true;
								}
						break;
						case 45: // Walkie Talkie
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										throw new BgaUserException( self::_("Please target a living player.") );
								}
								else
								{ // they are targeting a living player
										return true;
								}
						break;
						case 9: // Polygraph
								return true; // there are no restrictions on the player chosen
						break;
						case 13: // Surveillance Camera
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										throw new BgaUserException( self::_("Please target a living player.") );
								}
								else
								{ // they are targeting a living player
										return true;
								}
						break;
						case 17: // Deliriant
								return true; // there are no restrictions on the player chosen
						break;
						case 6: // K-9 Unit
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										throw new BgaUserException( self::_("Please target a living player.") );
								}
								elseif(!$this->isPlayerHoldingGun($playerId))
								{ // player isn't holding a gun
										throw new BgaUserException( self::_("Please target a player holding a gun.") );
								}
								else
								{ // they are targeting a living player holding a gun
										return true;
								}
								return false; // won't get here
						break;
						default:
							throw new feException( "Unrecognized collector number:$collectorNumber");
							break;

				}
		}

		function validateInvestigatePlayer($playerInvestigating, $playerBeingInvestigated)
		{
			if($this->isPlayerEliminated($playerBeingInvestigated))
			{ // this player is not in the game
					throw new BgaUserException( self::_("This player is eliminated. Please choose a living player.") );
			}
			elseif($this->hasDisguise($playerBeingInvestigated))
			{ // this player has the Disguise equipment card active in front of them
					throw new BgaUserException( self::_("This player is disguised. Investigate a different player.") );
			}

			return true;
		}

		// returns TRUE if this integrity card selection is valid for this equipment card, FALSE otherwise.
		function validateEquipmentIntegrityCardSelection($integrityCardId, $equipmentCardId)
		{
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);
				$equipmentCardOwner = $this->getEquipmentCardOwner($equipmentCardId); // get the player ID who is playing the equipment card
				$ownerOfNewIntegrityCardTarget = $this->getIntegrityCardOwner($integrityCardId);
				$target1 = $this->getEquipmentTarget1($equipmentCardId);
				$target2 = $this->getEquipmentTarget2($equipmentCardId);
				$target3 = $this->getEquipmentTarget3($equipmentCardId);
				$target4 = $this->getEquipmentTarget4($equipmentCardId);
				switch($collectorNumber)
				{
						case 15: // truth serum
								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please target a living player.") );
								}

								if(!$this->isIntegrityCardHidden($integrityCardId))
								{ // this card is already revealed
										throw new BgaUserException( self::_("Please target a hidden card.") );
								}

								return true;

						break;
						case 16: // wiretap
								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please target a living player.") );
								}

								if(is_null($target1) || $target1 == '')
								{ // this is the first card we're selcting
										return true;
								}

								if(!is_null($target1) && $target1 != '')
								{ // this is the second card we're targeting
										$ownerOfFirst = $this->getIntegrityCardOwner($target1);

										if($ownerOfNewIntegrityCardTarget == $ownerOfFirst)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								return true;

						break;
						case 1: // Blackmail
								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please target a living player.") );
								}

								if($equipmentCardOwner == $ownerOfNewIntegrityCardTarget)
								{ // they are trying to target their own integrity card
										throw new BgaUserException( self::_("Please target a player other than yourself.") );
								}

								// if target1 is selected, make sure this additional target also does not match the owner of target 1
								if(is_null($target1) || $target1 == '')
								{ // this is the FIRST card we're selecting
										return true;
								}

								if(!is_null($target1) && $target1 != '')
								{ // this is the second card we're targeting
										$ownerOfFirst = $this->getIntegrityCardOwner($target1);

										if($ownerOfNewIntegrityCardTarget == $ownerOfFirst)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								return true;

						break;
						case 7: // Metal Detector

								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please investigate a living player.") );
								}

								if(!$this->isPlayerHoldingGun($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target someone not holding a gun
										throw new BgaUserException( self::_("Please investigate a player holding a Gun.") );
								}

								if(!$this->isIntegrityCardHidden($integrityCardId))
								{ // they are trying to target a revealed integrity card
										throw new BgaUserException( self::_("Please investigate a hidden card.") );
								}

								if(is_null($target1) || $target1 == '')
								{ // this is the first card we're selcting
										return true;
								}

								if(!is_null($target1) && $target1 != '')
								{ // this is the second, third, or fourth card we're targeting
										$ownerOfFirst = $this->getIntegrityCardOwner($target1);

										if($ownerOfNewIntegrityCardTarget == $ownerOfFirst)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								if(!is_null($target2) && $target2 != '')
								{ // this is the third or fourth card we're targeting
										$ownerOfSecond = $this->getIntegrityCardOwner($target2);
										if($ownerOfNewIntegrityCardTarget == $ownerOfSecond)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								if(!is_null($target3) && $target3 != '')
								{ // this is the fourth card we're targeting
										$ownerOfThird = $this->getIntegrityCardOwner($target3);
										if($ownerOfNewIntegrityCardTarget == $ownerOfThird)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								return true;
						break;
						default:
							return false;
							break;
				}
		}

		function isAllInputAcquiredForEquipment($equipmentCardId)
		{
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);
				switch($collectorNumber)
				{
						case 2: // coffee
							return true; // we don't need any input
						break;
						case 8: // planted evidence
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
									return true;
								}
						break;
						case 12: // smoke grenade
							return true; // we don't need any input
						break;
						case 15: // truth serum
							$target1 = $this->getEquipmentTarget1($equipmentCardId);
							if(is_null($target1) || $target1 == '')
							{ // we do NOT have all we need for this equipment card
									return false;
							}
							else
							{ // we have all we need for this equipment card
								return true;
							}

						break;
						case 16: // wiretap
								$target1 = $this->getEquipmentTarget1($equipmentCardId);
								$target2 = $this->getEquipmentTarget2($equipmentCardId);
								//throw new feException( "target1:$target1 target2:$target2");
								if( is_null($target1) || $target1 === '' ||
								    is_null($target2) || $target2 === '' )
								{ // we do NOT have all we need for this equipment card
									//throw new feException( "false");
										return false;
								}
								else
								{ // we have all we need for this equipment card
									//throw new feException( "true");
										return true;
								}

						break;

						case 44: // riot shield
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								$target2 = $this->getPlayerTarget2($equipmentCardId);
								//throw new feException( "target1 $target1 target2 $target2");
								if( is_null($target1) || $target1 === '' ||
										is_null($target2) || $target2 === '' )
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
										return true;
								}
						break;

						case 11: // restraining order
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								$target2 = $this->getPlayerTarget2($equipmentCardId);
								if( is_null($target1) || $target1 === '' ||
										is_null($target2) || $target2 === '' )
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
										return true;
								}
						break;

						case 37: // mobile detonator
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if( is_null($target1) || $target1 === '' )
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
										return true;
								}
						break;

						case 4: // evidence bag
								$equipmentCardTarget = $this->getEquipmentTarget1($equipmentCardId);
								$playerTarget = $this->getPlayerTarget1($equipmentCardId);
								if( is_null($equipmentCardTarget) || $equipmentCardTarget === '' ||
										is_null($playerTarget) || $playerTarget === '' )
								{ // either the player or equipment card is not selected
										return false;
								}
								else
								{
										return true;
								}

						break;

						case 35: // med kit
								// TODO: maybe allow a wounded token target
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if( is_null($target1) || $target1 === '' )
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
										return true;
								}
						break;

						case 14: // taser
								$targetGun1 = $this->getGunTarget1($equipmentCardId);
								$targetPlayer1 = $this->getPlayerTarget1($equipmentCardId);
								if( (is_null($targetPlayer1) || $targetPlayer1 === '') &&
								 		(is_null($targetGun1) || $targetGun1 === ''))
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
										return true;
								}
						break;

						case 3: // Defibrillator
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
									return true;
								}
						break;
						case 1: // Blackmail
								$target1 = $this->getEquipmentTarget1($equipmentCardId);
								$target2 = $this->getEquipmentTarget2($equipmentCardId);
								//throw new feException( "target1:$target1 target2:$target2");
								if( is_null($target1) || $target1 === '' ||
										is_null($target2) || $target2 === '' )
								{ // we do NOT have all we need for this equipment card
									//throw new feException( "false");
										return false;
								}
								else
								{ // we have all we need for this equipment card
									//throw new feException( "true");
										return true;
								}
						break;
						case 30: // Disguise
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
									return true;
								}
						break;
						case 45: // Walkie Talkie
								$target1 = $this->getPlayerTarget1($equipmentCardId);

								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
										return true;
								}
						break;
						case 9: // Polygraph
								$target1 = $this->getPlayerTarget1($equipmentCardId);

								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
										return true;
								}
						break;
						case 13: // Surveillance Camera
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
									return true;
								}
						break;
						case 7: // Metal Detector
								$numberOfGunsHeldWithHiddenCards = $this->getNumberOfGunsHeldByPlayersWithHiddenIntegrityCards(); // get guns held by players with at least one hidden integrity card
								$numberOfEquipmentTargets = $this->getNumberOfEquipmentTargets(); // get number of integrity cards targeted
//throw new feException( "metal detector guns held with hidden($numberOfGunsHeldWithHiddenCards) and equipment card targets($numberOfEquipmentTargets)");
								if($numberOfEquipmentTargets < $numberOfGunsHeldWithHiddenCards)
								{
										return false;
								}
								else
								{
										return true;
								}
						break;
						case 17: // Deliriant
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
									return true;
								}
						break;
						case 6: // K-9 Unit
								$target1 = $this->getPlayerTarget1($equipmentCardId);
								if(is_null($target1) || $target1 == '')
								{ // we do NOT have all we need for this equipment card
										return false;
								}
								else
								{ // we have all we need for this equipment card
									return true;
								}
						break;

						default:
							return false; // return false by default
							break;

				}
		}

		// The player has just completed their action for their turn so we need to figure out
		// which state is next.
		function setStateAfterTurnAction($playerWhoseTurnItIs)
		{
				if ($this->doesPlayerNeedToDiscard($playerWhoseTurnItIs))
				{ // too many cards in hand
						$this->gamestate->nextState( "discardEquipment" );
				}
				elseif($this->isPlayerHoldingGun($playerWhoseTurnItIs))
				{ // this player IS holding a gun
						$this->gamestate->nextState( "askAim" ); // ask the player to aim their gun
				}
				else
				{ // this player is NOT holding a gun
						$this->gamestate->setAllPlayersMultiactive(); // set all players to active (TODO: only set players holding an equipment card to be active)
						$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
				}
		}

		// The player has just played an equipment card and we need to put them into the state that will allow
		// them to give us the input required for that equipment card like which cards or players they are targeting.
		function setStateForEquipment($equipmentId)
		{

//throw new feException( "setStateForEquipment equipmentId:$equipmentId");

				$collectorNumber = $this->getCollectorNumberFromId($equipmentId);
				switch($collectorNumber)
				{
						case 2: // coffee
								//throw new feException( "setState Coffee");
								$equipmentOwner = $this->getEquipmentCardOwner($equipmentId);
								$this->setEquipmentPlayerTarget($equipmentId, $equipmentOwner);
								$this->gamestate->nextState( "executeEquipment" ); // use the equipment
						break;
						case 8: // planted evidence
								//throw new feException( "setState Planted Evidence");
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;
						case 12: // smoke grenade
								//throw new feException( "setState Smoke Grenade");
								$this->gamestate->nextState( "executeEquipment" ); // use the equipment
						break;
						case 15: // truth serum
								//throw new feException( "setState Truth Serum");
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // everything required has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // an integrity card is not yet targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // choose the integrity card you will reveal
								}
						break;
						case 16: // wiretap
								//throw new feException( "setState Wiretap");
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // everything required has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // an integrity card is not yet targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // choose the integrity card you will reveal
								}

						break;

						case 44: // riot shield

								$target1 = $this->getPlayerTarget1($equipmentId);
								if(is_null($target1) || $target1 === '')
								{ // riot shield is just being played

										$this->gamestate->nextState("chooseEquipmentTargetOutOfTurn");
								}
								else
								{ // riot shield was NOT JUST played
										if($this->isAllInputAcquiredForEquipment($equipmentId))
										{ // the player has been targeted
												$this->gamestate->nextState( "executeEquipment" ); // use the equipment
										}
										else
										{ // the player has not yet been targeted
												$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
										}
								}
						break;

						case 11: // restraining order
								$target1 = $this->getPlayerTarget1($equipmentId);
								if(is_null($target1) || $target1 === '')
								{ //  restraining order is just being played

										$this->gamestate->nextState("chooseEquipmentTargetOutOfTurn");
								}
								else
								{ // restraining order was NOT JUST played
										if($this->isAllInputAcquiredForEquipment($equipmentId))
										{ // the player has been targeted
												$this->gamestate->nextState( "executeEquipment" ); // use the equipment
										}
										else
										{ // the player has not yet been targeted
												$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
										}
								}
						break;

						case 37: // mobile detonator
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;

						case 4: // evidence bag
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player and equipment has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player or equipment has not yet been targeted
										$equipmentCardTarget = $this->getEquipmentTarget1($equipmentId);
										$playerTarget = $this->getPlayerTarget1($equipmentId);
										if( is_null($equipmentCardTarget) || $equipmentCardTarget == '')
										{ // we don't have the equipment card target
												$this->gamestate->nextState( "chooseEquipmentCardInAnyHand" );
										}
										else
										{ // we do have the equipment card but we don't have the player
												$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
										}
								}
						break;

						case 35: // med kit
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player/gun has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player/gun has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;

						case 14: // taser
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player/gun has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player/gun has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;

						case 3: // Defibrillator
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;
						case 1: // Blackmail
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // everything required has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // an integrity card is not yet targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // choose the integrity card you will reveal
								}
						break;
						case 30: // Disguise
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;
						case 45: // Walkie Talkie
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted

										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;
						case 9: // Polygraph
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;
						case 13: // Surveillance Camera
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;
						case 7: // Metal Detector
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // ask them to target a player
								}
						break;
						case 17: // Deliriant
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;
						case 6: // K-9 Unit
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;

						default:
								throw new feException( "Unrecognized equipment card: ".$stateName );
								break;
				}

		}

		// This sets the next unset equipment card target.
		function setEquipmentCardTarget($equipmentCardId, $target)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";

				$target1 = $this->getEquipmentTarget1($equipmentCardId);
				$target2 = $this->getEquipmentTarget2($equipmentCardId);
				$target3 = $this->getEquipmentTarget3($equipmentCardId);
				$target4 = $this->getEquipmentTarget4($equipmentCardId);
				if(is_null($target1) || $target1 == '')
				{ // we don't yet have a first target
						$sqlUpdate .= "equipment_target_1='$target' WHERE ";
				}
				elseif(is_null($target2) || $target2 == '')
				{ // we don't yet have a second target
						$sqlUpdate .= "equipment_target_2='$target' WHERE ";
				}
				elseif(is_null($target3) || $target3 == '')
				{ // we don't yet have a second target
						$sqlUpdate .= "equipment_target_3='$target' WHERE ";
				}
				else
				{ // we don't yet have a second target
						$sqlUpdate .= "equipment_target_4='$target' WHERE ";
				}

				$sqlUpdate .= "card_id=$equipmentCardId";

				//var_dump( $sqlUpdate );
				//die('ok');
				self::DbQuery( $sqlUpdate );
		}

		function setEquipmentPlayerTarget($equipmentCardId, $target)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";

				$target1 = $this->getPlayerTarget1($equipmentCardId);
				if(is_null($target1) || $target1 == '')
				{ // we don't yet have a first target
						$sqlUpdate .= "player_target_1='$target' WHERE ";
				}
				else
				{ // we already have a first target set
						$sqlUpdate .= "player_target_2='$target' WHERE ";
				}

				$sqlUpdate .= "card_id=$equipmentCardId";

				//var_dump( $sqlUpdate );
				//die('ok');
				self::DbQuery( $sqlUpdate );
		}

		function setEquipmentGunTarget($equipmentCardId, $target)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";

				$target1 = $this->getGunTarget1($equipmentCardId);
				if(is_null($target1) || $target1 == '')
				{ // we don't yet have a first target
						$sqlUpdate .= "gun_target_1='$target' WHERE ";
				}
				else
				{ // we already have a first target set
						$sqlUpdate .= "gun_target_2='$target' WHERE ";
				}

				$sqlUpdate .= "card_id=$equipmentCardId";

				//var_dump( $sqlUpdate );
				//die('ok');
				self::DbQuery( $sqlUpdate );
		}

		function getIntegrityCard($cardId, $playerAsking)
		{
				$sql = "SELECT ic.card_id, pp.player_position AS player_position, ic.card_location_arg AS card_position, ic.card_type AS card_type, pp.player_id AS card_owner_id FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerAsking) ";
				$sql .= "WHERE pcv.player_id=$playerAsking AND ic.card_id=$cardId ";

				//var_dump( $sqlUpdate );
				//die('ok');

				return self::getObjectListFromDB( $sql );
		}

		function isSeen($playerAsking, $playerTargeting, $cardPosition)
		{
				$sql = "SELECT pcv.is_seen FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerAsking) ";
				$sql .= "WHERE pcv.player_id=$playerAsking AND ic.card_location=$playerTargeting AND ic.card_location_arg=$cardPosition ";

				//var_dump( $sql );
				//die('ok');

				return self::getUniqueValueFromDb($sql);
		}

		function getIntegrityCardId($playerTargeting, $cardPosition)
		{
				$sql = "SELECT ic.card_id FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerTargeting AND ic.card_location_arg=$cardPosition ";

				//var_dump( $sql );
				//die('ok');

				return self::getUniqueValueFromDb($sql);
		}

		// Get a gun that is not being held by a player.
		function getNextGunAvailable()
		{
				$sql = "SELECT * FROM `guns` ";
				$sql .= "WHERE gun_held_by='' OR gun_held_by IS NULL ";
				$sql .= "ORDER BY gun_id asc ";
				$sql .= "LIMIT 1 ";

				//var_dump( $sqlUpdate );
				//die('ok');

				return self::getObjectListFromDB( $sql );
		}

		// Get the PLAYER ID who is being TARGETED by a GUN.
		function getPlayerIdOfGunTarget($gunId)
		{
				return self::getUniqueValueFromDb("SELECT gun_aimed_at FROM guns WHERE gun_id=$gunId");
		}

		function setGunState($gunId, $newState)
		{
				$sqlUpdate = "UPDATE guns SET ";
				$sqlUpdate .= "gun_state='$newState' WHERE ";
				$sqlUpdate .= "gun_id=$gunId";

				self::DbQuery( $sqlUpdate );
		}

		function getGunState($gunId)
		{
				return self::getUniqueValueFromDb("SELECT gun_state FROM guns WHERE gun_id=$gunId");
		}

		// If gun_can_shoot is set to 0, it cannot shoot. Otherwise it can shoot.
		function setGunCanShoot($gunId, $value)
		{
				$sqlUpdate = "UPDATE guns SET ";
				$sqlUpdate .= "gun_can_shoot=$value WHERE ";
				$sqlUpdate .= "gun_id=$gunId";

				self::DbQuery( $sqlUpdate );
		}

		function getGunCanShoot($gunId)
		{
				return self::getUniqueValueFromDb("SELECT gun_can_shoot FROM guns WHERE gun_id=$gunId");
		}

		function setGunAcquiredInState($gunId, $value)
		{
				$sqlUpdate = "UPDATE guns SET ";
				$sqlUpdate .= "gun_acquired_in_state='$value' WHERE ";
				$sqlUpdate .= "gun_id=$gunId";

				self::DbQuery( $sqlUpdate );
		}

		function getGunAcquiredInState($gunId)
		{
				return self::getUniqueValueFromDb("SELECT gun_acquired_in_state FROM guns WHERE gun_id=$gunId");
		}

		function getPlayerGettingShot()
		{
				$guns = $this->getGunsShooting();
				foreach( $guns as $gun )
				{ // go through each gun that is currently shooting
						$targetPlayerId = $gun['gun_aimed_at']; // get the PLAYER ID of the target of this gun

						return $targetPlayerId;
				}

				return null; // return null if you can't find a player getting shot (which shouldn't happen)
		}

		function getPlayerShooting()
		{
				$guns = $this->getGunsShooting();
				foreach( $guns as $gun )
				{ // go through each gun that is currently shooting
						$heldByPlayerId = $gun['gun_held_by']; // get the player shooting

						return $heldByPlayerId;
				}

				return null; // return null if you can't find a player getting shot (which shouldn't happen)
		}

		function getPlayersOverEquipmentHandLimit()
		{
				$sql = "SELECT player_id, COUNT(player_id) equipmentCardsInHand FROM player p JOIN equipmentCards e ON p.player_id=e.equipment_owner where e.equipment_is_active<>1 GROUP BY player_id HAVING COUNT(player_id)>1";

				return self::getObjectListFromDB( $sql );
		}

		// Get the PLAYER ID of the player HOLDING the GUN.
		function getPlayerIdOfGunHolder($gunId)
		{
				return self::getUniqueValueFromDb("SELECT gun_held_by FROM guns WHERE gun_id=$gunId");
		}

		// Get the GUN held by a specific PLAYER ID.
		function getGunIdHeldByPlayer($playerId)
		{
				return self::getUniqueValueFromDb("SELECT gun_id FROM guns WHERE gun_held_by=$playerId");
		}

		function pickUpGun($playerWhoArmed, $previousState)
		{
				$guns = $this->getNextGunAvailable(); // get the next gun available
				if(count($guns) < 1)
				{ // no guns available
						throw new feException( "There are no guns available." );
				}

				foreach( $guns as $gun )
				{ // go through each card (should only be 1)

						// UPDATE THE DATABASE
						$gun_id = $gun['gun_id']; // 1, 2, 3, 4

						$sqlUpdate = "UPDATE guns SET ";
						$sqlUpdate .= "gun_held_by=$playerWhoArmed, gun_aimed_at='', gun_state='aimed', gun_acquired_in_state='$previousState' WHERE ";
						$sqlUpdate .= "gun_id=$gun_id";

						self::DbQuery( $sqlUpdate );

						// NOTIFY EACH PLAYER
						$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
						foreach( $players as $player )
						{ // go through each player

								$playerId = $player['player_id']; // the ID of the player we're notifying
								$playerName = $this->getPlayerNameFromPlayerId($playerWhoArmed); // get name of player who armed
								$letterOfPlayerWhoArmed = $this->getLetterOrderFromPlayerIds($playerId, $playerWhoArmed); // the letter order from the player who we are sending this to's perspective
								$playerColor = $this->getPlayerColorFromId($playerId);
								$playerNameColored = "<span style=\"color:#$playerColor\"><b>$playerName</b></span>"; // add on this player name who saw it

								// notify this player
								self::notifyPlayer( $playerId, 'gunPickedUp', clienttranslate( '${player_name} has picked up a gun.' ), array(
										 'playerArming' => $playerWhoArmed,
										 'letterOfPlayerWhoArmed' => $letterOfPlayerWhoArmed,
										 'gunId' => $gun_id,
										 'player_name' => $playerName,
										 'player_name_colored' => $playerNameColored
								) );
						}

						return $gun; // return the gun in case we need it and return just in case we have two next available guns for some strange reason
				}
		}

		function aimGun($gunHolderPlayer, $aimedAtPlayer)
		{
				$sqlUpdate = "UPDATE guns SET ";
				$sqlUpdate .= "gun_aimed_at=$aimedAtPlayer WHERE ";
				$sqlUpdate .= "gun_held_by=$gunHolderPlayer";

				self::DbQuery( $sqlUpdate );

				$gunId = $this->getGunIdHeldByPlayer($gunHolderPlayer); // get the GUN ID this player is holding
				$players = $this->getPlayersDeets(); // get list of players
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id'];

						$gunHolderLetter = $this->getLetterOrderFromPlayerIds($playerId, $gunHolderPlayer); // get the player letter of the gun holder from this player's perspective
						$aimedAtLetter = $this->getLetterOrderFromPlayerIds($playerId, $aimedAtPlayer); // get the player letter of who it is aimed at from this player's perspective

						$degreesToRotate = $this->getGunRotationFromLetters($gunHolderLetter, $aimedAtLetter); // get how much the gun should be rotated based on player positions
						$isPointingLeft = $this->getIsGunPointingLeft($gunHolderLetter, $aimedAtLetter); // check if the gun should be pointing left or right based on player positions and aim

						$nameOfGunHolder = $this->getPlayerNameFromPlayerId($gunHolderPlayer);
						$nameOfGunTarget = $this->getPlayerNameFromPlayerId($aimedAtPlayer);

						$colorOfGunHolder = $this->getPlayerColorFromId($gunHolderPlayer);
						$colorOfGunTarget = $this->getPlayerColorFromId($aimedAtPlayer);

						$nameOfGunHolderColored = "<span style=\"color:#$colorOfGunHolder\"><b>$nameOfGunHolder</b></span>";
						$nameOfGunTargetColored = "<span style=\"color:#$colorOfGunTarget\"><b>$nameOfGunTarget</b></span>";
						// notify players individually of which gun is aimed at which player (a aimed at b)
						self::notifyPlayer( $playerId, 'gunAimed', clienttranslate( '${player_name} has aimed their gun at ${player_name_2}.' ), array(
												 'degreesToRotate' => $degreesToRotate,
												 'gunId' => $gunId,
												 'isPointingLeft' => $isPointingLeft,
												 'player_name' => $nameOfGunHolder,
												 'player_name_2' => $nameOfGunTarget,
												 'heldByNameColored' => $nameOfGunHolderColored,
												 'aimedAtNameColored' => $nameOfGunTargetColored
						) );
				}
		}

		function investigateCard($cardId, $playerInvestigating)
		{
				$this->setVisibilityOfIntegrityCard($cardId, $playerInvestigating, 1); // show that this player has seen this card
				$investigatingPlayerName = $this->getPlayerNameFromPlayerId($playerInvestigating); // name of the player conducting the investigation

				$seenCards = $this->getIntegrityCard($cardId, $playerInvestigating); // get details about this card as a list of cards

				foreach( $seenCards as $seenCard )
				{ // go through each card (should only be 1)

						$cardId = $seenCard['card_id'];
						$playerLetter = $seenCard['player_position']; // a, b, c, etc.
						$cardPosition = $seenCard['card_position']; // 1, 2, 3
						$cardType = $seenCard['card_type']; // honest, crooked, kingpin, agent
						$investigatedPlayerId = $seenCard['card_owner_id']; // the player ID of the player who was investigated
						$investigateePlayerName = $this->getPlayerNameFromPlayerId($investigatedPlayerId); // name of the player being investigated

						self::incStat( 1, 'investigations_completed', $playerInvestigating ); // increase end game player stat

						// player_name saw a card of player_name
						self::notifyAllPlayers( "investigationComplete", clienttranslate( '${player_name} has completed their investigation of ${player_name_2}.' ), array(
								'player_name' => $investigatingPlayerName,
								'player_name_2' => $investigateePlayerName
						) );

						// notify the player who investigated of their new card
						$isHidden = $this->isIntegrityCardHidden($cardId); // true if this card is hidden
						$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
						self::notifyPlayer( $playerInvestigating, 'viewCard', clienttranslate( 'You completed your investigation of ${player_name} and you saw a ${cardType} card.' ), array(
																 'playerLetter' => $playerLetter,
																 'cardPosition' => $cardPosition,
																 'cardType' => $cardType,
																 'player_name' => $investigateePlayerName,
																 'isHidden' => $isHidden,
																 'playersSeen' => $listOfPlayersSeen
						) );

						// if the investigated player has Surveillance camera active, reveal the card
						if($this->hasSurveillanceCamera($investigatedPlayerId))
						{ // the player investigated has survillance camera active in front of them
								$this->revealCard($investigatedPlayerId, $cardPosition);
						}
				}
		}

		// This happens when someone draws an equipment card (not during a refresh).
		function drawEquipmentCard($playerDrawingId, $numberToDraw)
		{
				$cardId = 0;
				$cards = $this->equipmentCards->pickCards( $numberToDraw, 'deck', $playerDrawingId ); // draw a card
				$cardsWithName = array();
				foreach($cards as $card)
				{
						$cardId = $card['id'];
						$this->setEquipmentCardOwner($card['id'], $playerDrawingId);

						$collectorNumber = $this->getCollectorNumberFromId($cardId);
						$equipName = $this->getTranslatedEquipmentName($collectorNumber);
						$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

						$cardsWithName[$cardId] = array( 'id' => $cardId, 'type_arg' => $collectorNumber, 'equipment_name' => $equipName, 'equipment_effect' => $equipEffect );
				}
				$drawingPlayerName = $this->getPlayerNameFromPlayerId($playerDrawingId); // name of the player drawing

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying

						if($playerId == $playerDrawingId)
						{ // tell drawing player which cards they drew
								self::notifyPlayer( $playerDrawingId, 'iDrawEquipmentCards', clienttranslate( '${player_name} draws Equipment.' ), array(
										 'player_name' => $drawingPlayerName,
										 'cards_drawn' => $cardsWithName
								) );
						}
						else
						{ // tell others the number the other player drew
								$drawingPlayerLetter = $this->getLetterOrderFromPlayerIds($playerId, $playerDrawingId); // the letter order from the player who we are sending this to's perspective

										self::notifyPlayer( $playerId, 'otherPlayerDrawsEquipmentCards', clienttranslate( '${player_name} draws Equipment.' ), array(
												 'player_name' => $drawingPlayerName,
												 'drawing_player_id' => $playerDrawingId,
												 'drawing_player_letter' => $drawingPlayerLetter,
												 'number_drawn' => $numberToDraw,
												 'card_ids_drawn' => $cardId
										) );

						}
				}

				self::incStat( 1, 'equipment_acquired', $playerDrawingId ); // increase end game player stat
		}

		// Reveal a card for all players.
		function revealCard($playerRevealingId, $cardPosition)
		{
				  if($cardPosition == 0)
				  { // the player did not have a valid integrity card to reveal
						  return;
				  }

					$sqlUpdate = "UPDATE integrityCards SET ";
					$sqlUpdate .= "card_type_arg=1 WHERE ";
					$sqlUpdate .= "card_location=$playerRevealingId AND card_location_arg=$cardPosition";

					self::DbQuery( $sqlUpdate );

					$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes

					$playerName = $this->getPlayerNameFromPlayerId($playerRevealingId); // get the player's name who is revealing the card
					$cardId = $this->getCardIdFromPlayerAndPosition($playerRevealingId, $cardPosition);
					$cardType = $this->getCardTypeFromCardId($cardId);

					foreach( $players as $player )
					{ // go through each player
							$playerWeAreNotifyingId = $player['player_id']; // the ID of the player we're notifying

							$playerLetter = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $playerRevealingId); // the letter order from the player who we are sending this to's perspective

							// notify this player
							self::notifyPlayer( $playerWeAreNotifyingId, 'revealIntegrityCard', clienttranslate( 'A ${card_type} card of ${player_name} has been revealed.' ), array(
									 'player_name' => $playerName,
									 'card_type' => $cardType,
									 'card_position' => $cardPosition,
									 'player_letter' => $playerLetter
							) );
					}

					$this->setLastCardPositionRevealed($playerRevealingId, 0); // set the last card position back to default
		}

		function removeWoundedToken($woundedPlayerId)
		{
				$woundedCardId = $this->getLeaderCardIdForPlayer($woundedPlayerId); // get the card ID so we can pass it to the notification so the client knows which one to remove
//throw new feException( "woundedCardId: $woundedCardId" );
				// reset the wounded token in the database
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "is_wounded=0 WHERE ";
				$sqlUpdate .= "player_id=$woundedPlayerId";

				self::DbQuery( $sqlUpdate );

				$playerName = $this->getPlayerNameFromPlayerId($woundedPlayerId); // get the player's name

				// notify all players that the wounded token has been removed
				self::notifyAllPlayers( "removeWoundedToken", clienttranslate( 'The wounded token has been removed from ${player_name}.' ), array(
						'player_name' => $playerName,
						'woundedCardId' => $woundedCardId
				) );
		}

		function giveEquipmentFromOnePlayerToAnother($equipmentIdTargeted, $playerIdGivingEquipment, $playerIdGettingEquipment)
		{
				$this->setEquipmentCardOwner($equipmentIdTargeted, $playerIdGettingEquipment); // change the owner of the card in the database
				$giverName = $this->getPlayerNameFromPlayerId($playerIdGivingEquipment);
				$receiverName = $this->getPlayerNameFromPlayerId($playerIdGettingEquipment);
				$collectorNumber = $this->getCollectorNumberFromId($equipmentIdTargeted);
				$equipmentName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipmentEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerWeAreNotifyingId = $player['player_id']; // the ID of the player we're notifying

						$playerLetterGiving = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $playerIdGivingEquipment);
						$playerLetterReceiving = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $playerIdGettingEquipment);
//throw new feException( "player_name:$playerName equipment_id:$equipmentCardId collector_number:$collectorNumber player_letter:$playerLetter equipment_card_owner:$equipmentCardHolder" );

						// notify this player
						self::notifyPlayer( $playerWeAreNotifyingId, 'equipmentCardExchanged', clienttranslate( 'The equipment card held by ${player_name} is now held by ${player_name_2}.' ), array(
							'player_name' => $giverName,
							'player_name_2' => $receiverName,
							'equipment_id_moving' => $equipmentIdTargeted,
							'player_letter_giving' => $playerLetterGiving,
							'player_letter_receiving' => $playerLetterReceiving,
							'collector_number' => $collectorNumber,
							'equipment_name' => $equipmentName,
							'equipment_effect' => $equipmentEffect
						) );
				}
		}

		function discardEquipmentCard($equipmentCardId)
		{
			//throw new feException( "equipmentCardId:$equipmentCardId");
				$equipmentCardHolder = $this->getEquipmentCardOwner($equipmentCardId); // get the player ID of the player discarding this
				//$equipmentCardHolder = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.

				$this->equipmentCards->moveCard( $equipmentCardId, 'discard'); // move the card to the discard pile

				//throw new feException( "equimentcardid: $equipmentCardId equipment_card_owner:$equipmentCardHolder" );
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);

				$playerName = $this->getPlayerNameFromPlayerId($equipmentCardHolder); // get the player's name who is dropping the gun

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerWeAreNotifyingId = $player['player_id']; // the ID of the player we're notifying

						$playerLetter = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $equipmentCardHolder); // the letter order from the player who we are sending this to's perspective
//throw new feException( "player_name:$playerName equipment_id:$equipmentCardId collector_number:$collectorNumber player_letter:$playerLetter equipment_card_owner:$equipmentCardHolder" );

						// notify this player
						self::notifyPlayer( $playerWeAreNotifyingId, 'discardEquipmentCard', clienttranslate( '${player_name} has put their equipment card in the discard pile.' ), array(
								'player_name' => $playerName,
						 		'equipment_id' => $equipmentCardId,
						 		'collector_number' => $collectorNumber,
						 		'player_letter' => $playerLetter,
						 		'equipment_card_owner' => $equipmentCardHolder
						) );
				}

				$this->resetEquipmentAfterDiscard($equipmentCardId);
		}

		function discardActivePlayerEquipmentCard($equipmentCardId)
		{
				$equipmentCardHolder = $this->getEquipmentCardOwner($equipmentCardId); // get the player ID of the player discarding this
				//$equipmentCardHolder = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.

				$this->equipmentCards->moveCard( $equipmentCardId, 'discard'); // move the card to the discard pile

				//throw new feException( "equimentcardid: $equipmentCardId equipment_card_owner:$equipmentCardHolder" );
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);

				$playerName = $this->getPlayerNameFromPlayerId($equipmentCardHolder); // get the player's name who is dropping the gun

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes

				foreach( $players as $player )
				{ // go through each player
						$playerWeAreNotifyingId = $player['player_id']; // the ID of the player we're notifying

						$playerLetter = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $equipmentCardHolder); // the letter order from the player who we are sending this to's perspective
//throw new feException( "player_name:$playerName equipment_id:$equipmentCardId collector_number:$collectorNumber player_letter:$playerLetter equipment_card_owner:$equipmentCardHolder" );

						// notify this player
						self::notifyPlayer( $playerWeAreNotifyingId, 'discardActivePlayerEquipmentCard', clienttranslate( '${player_name} has moved their active Equipment to the discard pile.' ), array(
								'player_name' => $playerName,
						 		'equipment_id' => $equipmentCardId,
						 		'collector_number' => $collectorNumber,
						 		'player_letter' => $playerLetter,
						 		'equipment_card_owner' => $equipmentCardHolder
						) );
				}

				$this->resetEquipmentAfterDiscard($equipmentCardId);
		}

		function dropGun($gunId)
		{
				$gunHolderPlayerId = $this->getPlayerIdOfGunHolder($gunId);

				$sqlUpdate = "UPDATE guns SET ";
				$sqlUpdate .= "gun_aimed_at='', gun_held_by='', gun_state='center', gun_acquired_in_state='' WHERE ";
				$sqlUpdate .= "gun_id=$gunId";

				self::DbQuery( $sqlUpdate );

			  $playerName = $this->getPlayerNameFromPlayerId($gunHolderPlayerId); // get the player's name who is dropping the gun

				self::notifyAllPlayers( "dropGun", clienttranslate( '${player_name} has dropped their gun.' ), array(
						'player_name' => $playerName,
						'gunId' => $gunId
				) );
		}



		// SHOOT the target player. This is used both from shooting a gun and from shooting a player with Equipment
		// so don't do anything gun-shooting-specific in here.
		function shootPlayer($targetPlayerId)
		{
				// notify players about the shooting so they can see all that player's cards, update wounded tokens, and drop the gun (maybe notify them of which team that player is on and whether they are a Leader)
				$targetName = $this->getPlayerNameFromPlayerId($targetPlayerId);
				self::notifyAllPlayers( "executeGunShoot", clienttranslate( '${player_name} has been shot.' ), array(
						'player_name' => $targetName
				) );

				$isTargetALeader = $this->isPlayerALeader($targetPlayerId); // see if the player shot was a LEADER
				$isTargetWounded = $this->isPlayerWounded($targetPlayerId); // see if the player is WOUNDED

				// check for game over
				if($isTargetALeader && $isTargetWounded)
				{ // if you're shooting a wounded leader, the game ends
						$this->endGameCleanup($targetPlayerId);

						$this->gamestate->nextState( "endGame" );
				}
				else
				{ // the game is not ending

						if($isTargetALeader)
						{ // a Leader is being shot for the first time

								// give the target a wounded token
								$this->woundPlayer($targetPlayerId); // wound the player
						}
						else
						{ // a non-Leader is being shot

								// set that player to eliminated state
								$this->eliminatePlayer($targetPlayerId); // eliminate this player
						}
				}

				$this->setAllPlayerIntegrityCardsToRevealed($targetPlayerId); // reveal all of the target's cards in the database
		}

		function endGameCleanup($eliminatedLeader)
		{
			$this->awardEndGamePoints('team_win', $eliminatedLeader); // award end game points

			$this->countPlayersOnTeams('end'); // update stats on how many were on each team at the end of the game
		}

		function awardEndGamePoints($endType, $eliminatedLeader)
		{
				if($endType == 'team_win')
				{
						$losingTeam = $this->getPlayerTeam($eliminatedLeader);

						$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes

						foreach( $players as $player )
						{ // go through each player

								$thisPlayerId = $player['player_id'];
								$playerTeam = $this->getPlayerTeam($thisPlayerId); // get this player's team
								if($playerTeam != $losingTeam)
								{
										$sqlAll = "UPDATE player SET player_score='1' WHERE player_id=$thisPlayerId"; // update score in the database
										self::DbQuery( $sqlAll );
								}
						}
				}
		}

		function woundPlayer($playerId)
		{
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "is_wounded=1 WHERE ";
				$sqlUpdate .= "player_id=$playerId";

				self::DbQuery( $sqlUpdate );

				// NOTIFY EACH PLAYER
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				$playerName = $this->getPlayerNameFromPlayerId($playerId);
				foreach( $players as $player )
				{ // go through each player

						$playerWeAreNotifyingId = $player['player_id']; // the ID of the player we're notifying
						$leaderCardPosition = $this->getLeaderCardPositionFromPlayer($playerId); // 1, 2, 3
						$letterOfLeaderHolder = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $playerId); // the letter order from the player who we are sending this to's perspective
						$cardType = $this->getCardTypeFromPlayerIdAndPosition($playerId, $leaderCardPosition);

						// notify this player
						self::notifyPlayer( $playerWeAreNotifyingId, 'woundPlayer', clienttranslate( '${player_name} has been wounded.' ), array(
							'player_name' => $playerName,
						  'player_id' => $playerId,
						  'leader_card_position' => $leaderCardPosition,
						  'letter_of_leader_holder' => $letterOfLeaderHolder,
						  'card_type' => $cardType
						) );
				}
		}

		function eliminatePlayer($playerId)
		{
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "player_eliminated=1 WHERE ";
				$sqlUpdate .= "player_id=$playerId";

				self::DbQuery( $sqlUpdate );

				// NOTIFY EACH PLAYER
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				$playerName = $this->getPlayerNameFromPlayerId($playerId);
				foreach( $players as $player )
				{ // go through each player

						$playerWeAreNotifyingId = $player['player_id']; // the ID of the player we're notifying

						$letterOfPlayerWhoWasEliminated = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $playerId); // the letter order from the player who we are sending this to's perspective

						// notify this player
						self::notifyPlayer( $playerWeAreNotifyingId, 'eliminatePlayer', clienttranslate( '${player_name} has been eliminated.' ), array(
									 'letterOfPlayerWhoWasEliminated' => $letterOfPlayerWhoWasEliminated,
									 'player_name' => $playerName,
									 'eliminatedPlayerId' => $playerId
						) );
				}

				// discard equipment cards they were holding
				$equipmentCards = $this->getEquipmentInPlayerHand($playerId);
				foreach( $equipmentCards as $equipmentCard )
				{ // go through each card (should only be 1)
						$equipmentCardId = $equipmentCard['card_id'];
						$this->discardEquipmentCard($equipmentCardId);
				}

				// discard guns they were holding
				$guns = $this->getGunsHeldByPlayer($playerId);
				foreach( $guns as $gun )
				{ // go through each gun (should only be 1)
						$gunId = $gun['gun_id'];
						$this->dropGun($gunId);
				}
		}

		function revivePlayer($playerId)
		{
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "player_eliminated=0 WHERE ";
				$sqlUpdate .= "player_id=$playerId";

				self::DbQuery( $sqlUpdate );

				// NOTIFY EACH PLAYER
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				$playerName = $this->getPlayerNameFromPlayerId($playerId);
				foreach( $players as $player )
				{ // go through each player

						$playerWeAreNotifyingId = $player['player_id']; // the ID of the player we're notifying

						$letterOfPlayerWhoWasEliminated = $this->getLetterOrderFromPlayerIds($playerWeAreNotifyingId, $playerId); // the letter order from the player who we are sending this to's perspective

						// notify this player
						self::notifyPlayer( $playerWeAreNotifyingId, 'revivePlayer', clienttranslate( '${player_name} has been revived.' ), array(
									 'letterOfPlayerWhoWasEliminated' => $letterOfPlayerWhoWasEliminated,
									 'player_name' => $playerName,
									 'eliminatedPlayerId' => $playerId
						) );
				}
		}

		function getPlayerTurnDiscardToDiscardButtonList($isPlayerTurn)
		{
				$result = array();
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				if(!$isPlayerTurn)
				{ // they are being asked to discard on another player's turn because they were given an equipment card
						$playerWhoseTurnItIs = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				}

				$buttonIdentifier = 0;
				$equipmentCards = $this->getEquipmentInPlayerHand($playerWhoseTurnItIs);
				foreach( $equipmentCards as $equipmentCard )
				{ // go through each card (should only be 1)
						$equipmentId = $equipmentCard['card_id'];
						$equipmentName = $equipmentCard['equipment_name'];

						$buttonLabel = "Discard $equipmentName";
						$isDisabled = false;

						$hoverOverText = ''; // hover over text or '' if we don't want a hover over
						$actionName = 'DiscardEquipment'; // shoot, useEquipment
						$equipmentId = $equipmentId;  // only used for equipment to specify which equipment in case of more than one in hand

						$result[$buttonIdentifier] = array(); // create a new array for this player
						$result[$buttonIdentifier]['buttonLabel'] = $buttonLabel;
						$result[$buttonIdentifier]['hoverOverText'] = $hoverOverText;
						$result[$buttonIdentifier]['actionName'] = $actionName;
						$result[$buttonIdentifier]['equipmentId'] = $equipmentId;
						$result[$buttonIdentifier]['makeRed'] = false;
						$result[$buttonIdentifier]['isDisabled'] = $isDisabled;

						$buttonIdentifier++;
				}

				return $result;
		}

		function getPlayerTurnButtonList()
		{
				$result = array();
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)

				$result[0] = array(); // create a new array for this player
				$result[0]['buttonLabel'] = 'Investigate';
				$result[0]['hoverOverText'] = '';
				$result[0]['actionName'] = 'Investigate';
				$result[0]['equipmentId'] = '';
				$result[0]['makeRed'] = false;
				if($this->canPlayerInvestigate($playerWhoseTurnItIs))
				{ // this player can investigate
						$result[0]['isDisabled'] = false;
				}
				else {
						$result[0]['isDisabled'] = true;
				}

				$result[1] = array(); // create a new array for this player
				$result[1]['buttonLabel'] = 'Equip';
				$result[1]['hoverOverText'] = '';
				$result[1]['actionName'] = 'Equip';
				$result[1]['equipmentId'] = '';
				$result[1]['makeRed'] = false;
				$result[1]['isDisabled'] = false; // this is never disabled

				$result[2] = array(); // create a new array for this player
				$result[2]['buttonLabel'] = 'Arm';
				$result[2]['hoverOverText'] = '';
				$result[2]['actionName'] = 'Arm';
				$result[2]['equipmentId'] = '';
				$result[2]['makeRed'] = false;
				if($this->canPlayerArm($playerWhoseTurnItIs))
				{ // this player can arm
						$result[2]['isDisabled'] = false;
				}
				else {
						$result[2]['isDisabled'] = true;
				}

				$result[3] = array(); // create a new array for this player
				$result[3]['buttonLabel'] = 'Shoot';
				$result[3]['hoverOverText'] = '';
				$result[3]['actionName'] = 'Shoot';
				$result[3]['equipmentId'] = '';
				$result[3]['makeRed'] = false;
				if($this->canPlayerShoot($playerWhoseTurnItIs))
				{ // this player can shoot
						$result[3]['isDisabled'] = false;

						$gunId = $this->getGunIdHeldByPlayer($playerWhoseTurnItIs);
						$gunTargetPlayerId = $this->getPlayerIdOfGunTarget($gunId);
						$gunTargetName = $this->getPlayerNameFromPlayerId($gunTargetPlayerId);
						//throw new feException( "Gun Target Name: $gunTargetName");
						$result[3]['buttonLabel'] = "Shoot $gunTargetName"; // add the name of the player you're shooting
				}
				else {
						$result[3]['isDisabled'] = true;
				}

				$buttonIdentifier = 4;
				$equipmentCards = $this->getEquipmentInPlayerHand($playerWhoseTurnItIs);
				foreach( $equipmentCards as $equipmentCard )
				{ // go through each card (should only be 1)
						$equipmentId = $equipmentCard['card_id'];
						$equipmentName = $equipmentCard['equipment_name'];

						$buttonLabel = "Use $equipmentName";
						if($this->validateEquipmentUsage($equipmentId))
						{ // we CAN use this now
								$isDisabled = false;
						}
						else
						{ // we cannot use this now
								$isDisabled = true;
						}
						$hoverOverText = ''; // hover over text or '' if we don't want a hover over
						$actionName = 'UseEquipment'; // shoot, useEquipment
						$equipmentId = $equipmentId;  // only used for equipment to specify which equipment in case of more than one in hand

						$result[$buttonIdentifier] = array(); // create a new array for this player
						$result[$buttonIdentifier]['buttonLabel'] = $buttonLabel;
						$result[$buttonIdentifier]['hoverOverText'] = $hoverOverText;
						$result[$buttonIdentifier]['actionName'] = $actionName;
						$result[$buttonIdentifier]['equipmentId'] = $equipmentId;
						$result[$buttonIdentifier]['makeRed'] = false;
						$result[$buttonIdentifier]['isDisabled'] = $isDisabled;

						$buttonIdentifier++;
				}

				$result[$buttonIdentifier] = array(); // create a new array for this player
				$result[$buttonIdentifier]['buttonLabel'] = 'Pass';
				$result[$buttonIdentifier]['hoverOverText'] = '';
				$result[$buttonIdentifier]['actionName'] = 'PassOnTurn';
				$result[$buttonIdentifier]['equipmentId'] = '';
				$result[$buttonIdentifier]['makeRed'] = true; // make this one red
				$result[$buttonIdentifier]['isDisabled'] = false; // this is never disabled

				return $result;
		}

		function getOtherPlayers()
		{
				$result = array();

				$allPlayers = self::getObjectListFromDB( "SELECT *
																					 FROM player" );

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.

				// create an array for each player with display information
				foreach( $allPlayers as $player )
				{
						$playerId = $player['player_id'];
						if($activePlayerId != $playerId)
						{ // don't include yourself

							  $result[$playerId] = array(); // create a new array for this player
								$result[$playerId]['player_id'] = $player['player_id']; // put this player ID into the subarray
								$result[$playerId]['player_name'] = $player['player_name']; // put this player name into the subarray
								$result[$playerId]['player_letter'] = $this->getLetterOrderFromPlayerIds($activePlayerId, $playerId); // get the order around the table for this player from the asking player's perspective
						}
				}

				return $result;
		}

		function getAllPlayers()
		{
				$result = array();

				$allPlayers = self::getObjectListFromDB( "SELECT *
																					 FROM player" );

			  $activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.

				// create an array for each player with display information
				foreach( $allPlayers as $player )
				{
						$playerId = $player['player_id'];

						$result[$playerId] = array(); // create a new array for this player
						$result[$playerId]['player_id'] = $player['player_id']; // put this player ID into the subarray
						$result[$playerId]['player_name'] = $player['player_name']; // put this player name into the subarray
						$result[$playerId]['player_letter'] = $this->getLetterOrderFromPlayerIds($activePlayerId, $playerId); // get the order around the table for this player from the asking player's perspective
				}

				return $result;
		}

		function resolveEquipment($equipmentId)
		{
				$collectorNumber = $this->getCollectorNumberFromId($equipmentId); // get the type of equipment card we're using
				$equipmentCardOwner = $this->getEquipmentCardOwner($equipmentId); // get the player ID who is playing the equipment card
//throw new feException( "Resolve $collectorNumber" );
				// switch statement
				switch($collectorNumber)
				{
						case 2: // coffee
							//throw new feException( "Resolve Coffee" );
							$target1 = $this->getPlayerTarget1($cardId);
							if($target1 == '')
							{
									throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
							}

							$this->makePlayerEquipmentActive($equipmentId, $target1); // activate this card

						break;
						case 8: // planted evidence
							//throw new feException( "Resolve Planted Evidence" );
							$target1 = $this->getPlayerTarget1($equipmentId);
							if($target1 == '')
							{
									throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
							}
							$this->makePlayerEquipmentActive($equipmentId, $target1); // activate this card

						break;
						case 12: // smoke grenade
							//throw new feException( "Resolve Smoke Grenade" );

							$this->equipmentCards->moveCard( $equipmentId, 'center'); // move the card to the center

							// make it active
							$this->makeCentralEquipmentActive($equipmentId); // activate this in the middle of the table

						break;
						case 15: // truth serum
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
								$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted

								$this->revealCard($integrityCardOwner, $cardPosition); // set the selected integrity card to revealed
								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
								$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
								$this->setStateAfterTurnAction($activePlayerId); // see which state we go into after completing this turn action
						break;
						case 16: // wiretap
								//throw new feException( "Resolve Wiretap" );

								// investigate card 1
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
								$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted
								$cardId = $this->getCardIdFromPlayerAndPosition($integrityCardOwner, $cardPosition);
								$this->investigateCard($cardId, $equipmentCardOwner); // investigate this card and notify players

								$target2 = $this->getEquipmentTarget2($equipmentId); // get the selected integrity card
								$integrityCardOwner2 = $this->getIntegrityCardOwner($target2); // get the player who owns the integrity card targeted
								$cardPosition2 = $this->getIntegrityCardPosition($target2); // get the position of the integrity card targeted
								$cardId2 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner2, $cardPosition2);
								$this->investigateCard($cardId2, $equipmentCardOwner); // investigate this card and notify players

								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
								$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
								$this->setStateAfterTurnAction($activePlayerId); // see which state we go into after completing this turn action
						break;

						case 44: // riot shield

								$target2 = $this->getPlayerTarget2($equipmentId); // get player target 2
								$this->shootPlayer($target2); // shoot that player

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
//throw new feException( "player $playerWhoseTurnItWas is now the active player." );
								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;

						case 11: // restraining order
								$target2 = $this->getPlayerTarget2($equipmentId); // get player target 2
								$playerShooting = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerShooting ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was when the equipment was played
								//$playerShooting = $this->getEquipmentPlayedOnTurn($equipmentId); // get the turn in which this equipment was played since that is the player shooting
								$this->aimGun($playerShooting, $target2); // update the gun in the database for who it is now aimed at

								$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
								$this->gamestate->nextState( "askShootReaction" ); // go to the state allowing the active player to choose a card to investigate

		//throw new feException( "player $playerWhoseTurnItWas is now the active player." );
								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;

						case 37: // mobile detonator
								$target1 = $this->getPlayerTarget1($equipmentId); // get player target 1
								$this->shootPlayer($target1); // shoot that player //TODO: THIS CANNOT HAPPEN NOW...IT HAS TO HAPPEN AFTER THE SHOT EXECUTES (IF IT STILL EXECUTES AFTER OTHERS HAVE HAD A CHANCE TO PLAY OTHER EQUIPMENT)


								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;

						case 4: // evidence bag

								$equipmentIdTargeted = $this->getEquipmentTarget1($equipmentId); // get the ID of the targeted equipment
								$playerIdGivingEquipment = $this->getEquipmentCardOwner($equipmentIdTargeted); // get the ID of the player giving the equipment
								$playerIdGettingEquipment = $this->getPlayerTarget1($equipmentId); // get the player it is being given to

								$this->giveEquipmentFromOnePlayerToAnother($equipmentIdTargeted, $playerIdGivingEquipment, $playerIdGettingEquipment); // make the equipment owner the new player
						break;

						case 35: // med kit
								$target1 = $this->getPlayerTarget1($equipmentId); // get player target 1
								$this->removeWoundedToken($target1); // discard the wounded token this player has
						break;

						case 14: // taser
								$targetPlayer = $this->getPlayerTarget1($equipmentId); // get the targeted player
								if(is_null($targetPlayer) || $targetPlayer == '')
								{
										$targetGun = $this->getGunTarget1();
										$targetPlayer = $this->getPlayerIdOfGunHolder($targetGun);
								}

								$gunId = $this->getGunIdHeldByPlayer($targetPlayer); // get the gun targeted
								$this->dropGun($gunId); // make the target drop their gun

								$ownerOfEquipment = $this->getEquipmentCardOwner($equipmentId); // get the player who played the equipment
								$previousState = $this->getEquipmentPlayedInState($equipmentId); // get the state this equipment was played in so we can go back to it after the player aims
								$gun = $this->pickUpGun($ownerOfEquipment, $previousState); // allow the equipment card user to pick up the gun
								$this->setGunCanShoot($gunId, 0); // make sure player cannot shoot the gun this turn

								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;

						case 3: // Defibrillator
								$targetPlayer = $this->getPlayerTarget1($equipmentId); // get the targeted player
								$this->revivePlayer($targetPlayer); // bring this player back to life

								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;
						case 1: // Blackmail
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
								$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted
								$cardId = $this->getCardIdFromPlayerAndPosition($integrityCardOwner, $cardPosition);

								$target2 = $this->getEquipmentTarget2($equipmentId); // get the selected integrity card
								$integrityCardOwner2 = $this->getIntegrityCardOwner($target2); // get the player who owns the integrity card targeted
								$cardPosition2 = $this->getIntegrityCardPosition($target2); // get the position of the integrity card targeted
								$cardId2 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner2, $cardPosition2);

								$this->swapIntegrityCards($cardId, $cardId2); // swap the owners of the two integrity cards
								$this->investigateCard($cardId2, $integrityCardOwner); // let hte new owner investigate this card and notify players
								$this->investigateCard($cardId, $integrityCardOwner2); // let the new owner investigate this card and notify players

								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;
						case 30: // Disguise
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}
								$this->makePlayerEquipmentActive($equipmentId, $target1); // activate this card
						break;
						case 45: // Walkie Talkie
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}

								$allGuns = $this->getAllGuns();
								foreach( $allGuns as $gun )
								{ // go through each gun
										$gunId = $gun['gun_id'];
										$gunState = $gun['gun_state'];
										$gunHeldBy = $gun['gun_held_by'];

										if(!is_null($gunHeldBy) && $gunHeldBy != '')
										{ // this gun is held by someone
												$this->aimGun($gunHeldBy, $target1); // aim the gun at the target (this will take care of notifications too)
										}
								}
								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;
						case 9: // Polygraph
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}

								// allow target look at all equipment owner's cards
								$hiddenCardsOfEquipmentOwner = $this->getHiddenCardsFromPlayer($equipmentCardOwner);
								foreach($hiddenCardsOfEquipmentOwner as $card)
								{
										$this->investigateCard($card['card_id'], $target1);
								}

								// allow equipment owner look at all of equipment owner's cards
								$hiddenCardsOfTarget = $this->getHiddenCardsFromPlayer($target1);
								foreach($hiddenCardsOfTarget as $card)
								{
										$this->investigateCard($card['card_id'], $equipmentCardOwner);
								}

								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;
						case 13: // Surveillance Camera
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}
								$this->makePlayerEquipmentActive($equipmentId, $target1); // activate this card
						break;
						case 7: // Metal Detector
								$target1 = $this->getEquipmentTarget1($equipmentId);
								if(!is_null($target1) && $target1 != '')
								{
										$this->investigateCard($target1, $equipmentCardOwner); // investigate this card
								}

								$target2 = $this->getEquipmentTarget2($equipmentId);
								if(!is_null($target2) && $target2 != '')
								{
										$this->investigateCard($target2, $equipmentCardOwner); // investigate this card
								}

								$target3 = $this->getEquipmentTarget3($equipmentId);
								if(!is_null($target3) && $target3 != '')
								{
										$this->investigateCard($target3, $equipmentCardOwner); // investigate this card
								}

								$target4 = $this->getEquipmentTarget4($equipmentId);
								if(!is_null($target4) && $target4 != '')
								{
										$this->investigateCard($target4, $equipmentCardOwner); // investigate this card
								}
								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;
						case 17: // Deliriant
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}
								$this->makePlayerEquipmentActive($equipmentId, $target1); // activate this card

								// save the values of the target's cards

								$this->integrityCards->shuffle( $target1 ); // shuffle the player's cards
								$cardsOfTarget = $this->integrityCards->pickCardsForLocation( 3, $target1, $target1 ); // grab the player's cards now in a random order

								$cardLocationArg = 0;
								foreach( $cardsOfTarget as $card )
								{
										$cardId = $card['id']; // internal id
										$cardType = $card['type']; // honest, crooked, kingpin, agent
										$cardTypeArg = $card['type_arg']; // i don't think we're using this
										$cardLocation = $card['location']; // the player
										$cardLocationArg++; // move onto the next position

										$this->deleteIntegrityCardFromDatabase($cardId); // delete this card from the database
										$this->insertIntegrityCardIntoDatabase($cardType, $cardTypeArg, $cardLocation, $cardLocationArg); // insert this card into the database (so it has a new ID so players cannot track which ID is where)
								}


								// notify the players so they can:
								// destroy the existing ones
								// place the new ones
						break;
						case 6: // K-9 Unit
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}
								$gunId = $this->getGunIdHeldByPlayer($target1); // get the gun targeted
								$this->dropGun($gunId); // make that player drop the gun
								$this->discardEquipmentCard($equipmentId); // discard the equipment card now that it is resolved
						break;

						default:
							throw new feException( "Unknown equipment: $collectorNumber" );
						break;
				}

				// notify players of exactly which card was played now that it has been resolved
				$equipmentCardName = $this->getEquipmentName($equipmentId); // get the name of the equipment card
				self::notifyAllPlayers( "resolvedEquipment", clienttranslate( '${equipment_name} is now resolved.' ), array(
						'equipment_name' => $equipmentCardName
				) );
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

		//
		function clickedInvestigateButton()
		{
				self::checkAction( 'clickInvestigateButton' ); // make sure we can take this action from this state

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.

				$this->gamestate->nextState( "investigateChooseCard" ); // go to the state allowing the active player to choose a card to investigate
		}

		// The active player selected an action but now would like to cancel it and choose a new action.
		function clickedCancelAction()
		{
				self::checkAction( 'clickCancelAction' ); // make sure we can take this action from this state

				$equipmentId = $this->getEquipmentCardIdInUse();

				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "chooseIntegrityCards" ||
					 $stateName == "choosePlayer" )
				{ // we're using an equipment card that requires choosing integrity cards
						$previousState = $this->getEquipmentPlayedInState($equipmentId);	// go to the saved state for this equipment card
						$this->gamestate->nextState( $previousState ); // TODO: THE TRANSITION PROBABLY DOESN'T MATCH THE STATE NAME SO CHANGE $previousState TO GO TO THE CORRECT TRANSITION BASED ON THE NAME
				}
				elseif($stateName == "chooseEquipmentToPlayReactEndOfTurn" ||
							 $stateName == "chooseEquipmentToPlayReactInvestigate" ||
							 $stateName == "chooseEquipmentToPlayReactShoot" )
				{ // cancelling using equipment
						$this->gamestate->nextState( "cancelEquipmentUse" ); // this will send us back to the correct state no matter whether it is from a investigate/shoot/end of turn reaction
				}
				else
				{
						$this->gamestate->nextState( "playerAction" ); // go back to start of turn
				}


		}

		function clickedOpponentIntegrityCard($playerPosition, $cardPosition)
		{
				self::checkAction( 'clickOpponentIntegrityCard' ); // make sure we can take this action from this state

				$playerAsking = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$integrityCardOwner = $this->getPlayerIdFromLetterOrder($playerAsking, $playerPosition); // get the player ID of the player being investigated
				$integrityCardId = $this->getIntegrityCardId($integrityCardOwner, $cardPosition); // get the unique id for this integrity card

				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "chooseCardToInvestigate")
				{ // if we're in chooseCardToInvestigate state, investigate

						$isValid = $this->validateInvestigatePlayer($playerAsking, $integrityCardOwner); // throw an error if this investigation is invalid

						$isSeen = $this->isSeen($playerAsking, $integrityCardOwner, $cardPosition);

						if($isSeen != 0)
						{ // hey... this card has already been seen
								throw new BgaUserException( self::_("You can only investigate hidden cards.") );
						}

						$playerNameInvestigating = $this->getPlayerNameFromPlayerId($playerAsking);
						$playerNameTarget = $this->getPlayerNameFromPlayerId($integrityCardOwner);
						$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
						foreach( $players as $player )
						{ // go through each player
								$playerId = $player['player_id']; // the ID of the player we're notifying
								$cardPositionTargeted = $this->getIntegrityCardPosition($integrityCardId);
								$playerLetterInvestigated = $this->getLetterOrderFromPlayerIds($playerId, $integrityCardOwner);

								self::notifyPlayer( $playerId, "investigationAttempt", clienttranslate( '${player_name} is attempting to investigate ${player_name_2}.' ), array(
									'player_name' => $playerNameInvestigating,
									'player_name_2' => $playerNameTarget,
									'playerLetterInvestigated' => $playerLetterInvestigated,
									'cardPositionTargeted' => $cardPositionTargeted
								) );
						}

						$this->setLastPlayerInvestigated($playerAsking, $integrityCardOwner); // set player.last_player_investigated
						$this->setLastCardPositionInvestigated($playerAsking, $cardPosition); // set player.last_card_position_investigated

						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState( "askInvestigateReaction" ); // go to the state allowing the active player to choose a card to investigate
				}
				elseif($stateName == "chooseIntegrityCards")
				{ // if we're in chooseIntegrityCards for equipment usage
						$equipmentCardId = $this->getEquipmentCardIdInUse();

						$isValid = $this->validateEquipmentIntegrityCardSelection($integrityCardId, $equipmentCardId); // see if this selection is valid for this equipment card

						if($isValid)
						{
								$this->setEquipmentCardTarget($equipmentCardId, $integrityCardId); // set this as a target for the equipment card
						}

						// validate to see if we are ready to execute the equipment
						$this->setStateForEquipment($equipmentCardId);
				}
				else
				{
					throw new feException( "Unexpected state name: ".$stateName );
				}
		}

		function clickedArmButton()
		{
				self::checkAction( 'clickArmButton' ); // make sure we can take this action from this state

				$guns = $this->getNextGunAvailable(); // get the next gun available
				if(!$guns || count($guns) < 1)
				{ // there are no guns available
						throw new BgaUserException( self::_("All guns have been taken. Please choose a different action.") );
				}

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$hiddenCards = $this->getHiddenCardsFromPlayer($activePlayerId); // get all this player's hidden integrity cards
				if(count($hiddenCards) > 0)
				{ // they have at least one hidden card
						$this->gamestate->nextState( "armChooseCard" ); // go to the state allowing the active player to choose a card to reveal for arm
				}
				else
				{
						$this->gamestate->nextState( "executeArm" ); // go to the state where they will pick up their gun
				}
		}

		function clickedMyIntegrityCard($cardPosition)
		{
				self::checkAction( 'clickMyIntegrityCard' ); // make sure we can take this action from this state

				$playerRevealing = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$this->setLastCardPositionRevealed($playerRevealing, $cardPosition); // save which card was revealed until while we wait for players to react with equipment

				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "chooseCardToRevealForEquip")
				{
						$this->gamestate->nextState( "executeEquip" ); // go to the state where they will draw their equipment card
				}
				else
				{ // execute arm
						$this->gamestate->nextState( "executeArm" ); // go to the state where they will pick up their gun
				}
		}

		function clickedPlayer($playerPosition, $playerId)
		{
				self::checkAction( 'clickPlayer' ); // make sure we can take this action from this state
				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "askAim" || $stateName == "askAimOutOfTurn")
				{ // we chose a player to aim at
						$gunHolderPlayer = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
						$aimedAtPlayer = $this->getPlayerIdFromLetterOrder($gunHolderPlayer, $playerPosition); // get the player ID of the player being aimed at


						$this->aimGun($gunHolderPlayer, $aimedAtPlayer); // update the gun in the database for who it is now aimed at

						if($stateName == "askAimOutOfTurn")
						{ // we're choosing our aim potentially out of turn because we just got a gun from an equipment
//throw new feException( "askAimOutOfTurn" );
								$this->gamestate->nextState("afterAimedOutOfTurn"); // possibly change the active player
						}
						else
						{ // the usual case
								$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
								$this->gamestate->nextState( "endTurnReaction" ); // allow for end of turn equipment reactions
						}
				}
				elseif($stateName == "choosePlayer")
				{ // we chose a player to target with equipment
						$equipmentCardId = $this->getEquipmentCardIdInUse();

						$isValid = $this->validateEquipmentPlayerSelection($playerId, $equipmentCardId); // see if this selection is valid for this equipment card

						if($isValid)
						{
							  $this->setEquipmentPlayerTarget($equipmentCardId, $playerId); // set this as a target for the equipment card
						}
						// validate to see if we are ready to execute the equipment
						$this->setStateForEquipment($equipmentCardId);
				}
		}

		function clickedShootButton()
		{
				self::checkAction( 'clickShootButton' ); // make sure we can take this action from this state
				$gunHolderPlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$gunId = $this->getGunIdHeldByPlayer($gunHolderPlayerId); // get the gun ID this player is holding

				if($this->getGunCanShoot($gunId) == 0)
				{
						throw new BgaUserException( self::_("This gun cannot shoot this turn.") );
				}

				$this->setGunState($gunId, 'shooting');
				$targetPlayerId = $this->getPlayerIdOfGunTarget($gunId); // get the player ID
				$targetName = $this->getPlayerNameFromPlayerId($targetPlayerId); // convert the player ID in to a player NAME

				// notify players that player A is attempting to shoot player B
				self::notifyAllPlayers( "shootAttempt", clienttranslate( '${player_name} is attempting to shoot ${target_name}!' ), array(
						'target_name' => $targetName,
						'player_name' => self::getActivePlayerName(),
						'gunId' => $gunId
				) );

				$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
				$this->gamestate->nextState( "askShootReaction" ); // go to the state allowing the active player to choose a card to investigate
		}

		function clickedEquipButton()
		{
				self::checkAction( 'clickEquipButton' ); // make sure we can take this action from this state

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$hiddenCards = $this->getHiddenCardsFromPlayer($activePlayerId); // get all this player's hidden integrity cards
				if($hiddenCards && count($hiddenCards) > 0)
				{ // they have at least one hidden card
						$this->gamestate->nextState( "equipChooseCard" ); // go to the state allowing the active player to choose a card to reveal for equip
				}
				else
				{
						$this->gamestate->nextState( "executeEquip" ); // go to the state allowing the active player to choose a card to investigate
				}
		}

		// During a multiplayer Reaction state, a player said they want to pause the timer so they can consider
		// using equipment.
		function clickedUseEquipmentButton()
		{
				self::checkAction( 'clickUseEquipmentButton' ); // make sure we can take this action from this state



				$stateName = $this->getStateName(); // get the name of the current state

				if($stateName == "playerTurn")
				{ // activeplayer state
						$this->gamestate->nextState( "useEquipment" ); // they are already active so just go to the state where they will use their equipment
				}
				elseif($stateName == "askEndTurnReaction" ||
					 $stateName == "askShootReaction" ||
					 $stateName == "askInvestigateReaction")
				{ // multiactive state
						$currentPlayerId = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
						//throw new feException( "state is $stateName and current player is $currentPlayerId" );
						$this->gamestate->changeActivePlayer( $currentPlayerId ); // set the player using the equipment to the active player (this cannot be done in an activeplayer game state)
//throw new feException( "player $currentPlayerId is now the active player." );
						$this->gamestate->nextState( "useEquipment" ); // they are already active so just go to the state where they will use their equipment
				}
		}

		function clickedEquipmentCard($equipmentId)
		{
				self::checkAction( 'clickEquipmentCard' ); // make sure we can take this action from this state

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$collectorNumber = $this->getCollectorNumberFromId($equipmentId);

//throw new feException( "clickedMyEquipmentCard stateName:$stateName currentPlayerId:$currentPlayerId collectorNumber:$collectorNumber" );
				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "discardEquipment")
				{ // we have clicked on this Equipment to discard it
						$this->discardEquipmentCard($equipmentId);

						$this->setStateAfterTurnAction($activePlayerId); // see which state we go into after completing this turn action
				}
				elseif($stateName == "discardOutOfTurn")
				{ // we are discarding potentially out of turn
						$this->discardEquipmentCard($equipmentId);

						$this->gamestate->nextState( "afterDiscardedOutOfTurn" ); // go to a "game" state where we can figure out who's supposed to go next since this was potentially done out of turn order
				}
				elseif($stateName == "chooseEquipmentCardInAnyHand")
				{ // we have clicked on this Equipment to target it
						$equipmentIdInUse = $this->getEquipmentCardIdInUse(); // get the ID of the equipment card being use
						$this->setEquipmentCardTarget($equipmentIdInUse, $equipmentId); // set the target1 of the equipment card in use to this equipment card chosen

						$this->gamestate->nextState( "choosePlayer" ); // after choosing which equipment to target with Evidence Bag, we want to choose a player (if we use this state for other equipment cards, we may need choose the next state differently)
				}
				else
				{ // we have clicked on this equipment to use it

						if(!$this->validateEquipmentUsage($equipmentId))
						{ // we cannot use this equipment right now
								throw new BgaUserException( self::_("You cannot use this Equipment right now.") );
						}

						// notify that the equipment was used
						self::notifyAllPlayers( "playEquipment", clienttranslate( '${player_name} is playing an Equipment.' ), array(
								'player_name' => self::getActivePlayerName()
						) );

						// save the state name for this equipment so we know where to go back afterwards
						$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
						$this->setEquipmentCardState($playerWhoseTurnItIs, $equipmentId, $stateName); // set this equipment to being PLAYING

						// send us to the state that will ask for input or move forward to playing
						//$equipmentId = $this->getEquipmentCardIdInUse();
						$this->setStateForEquipment($equipmentId); // see which state we should move to next
				}
		}


		// All players have passed on using their equipment. This could be during any of the equipment reaction states.
		function passOnUseEquipment()
		{
				self::checkAction( 'clickPassOnUseEquipmentButton' ); // make sure we can take this action from this state

				$currentPlayerId = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.

				// Make this player unactive now
				// (and tell the machine state to use transtion "directionsChosen" if all players are now unactive
				$this->gamestate->setPlayerNonMultiactive( $currentPlayerId, "allPassedOnReactions" );
		}

		// The active player is asking to end their turn.
		function clickedEndTurnButton()
		{
				self::checkAction( 'clickEndTurnButton' ); // make sure we can take this action from this state

				$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active

				$this->gamestate->nextState( "endTurnReaction" ); // go to state where we ask if anyone wants to play equipment cards at the end of their turn
		}

		// This is called in a "game" state after someone picks up a gun from an Equipment card and then they aim it. We need to figure out which state we need
		// to go back to and which player is active.
		function afterAimedOutOfTurn()
		{
			//throw new feException( "afterAimedOutOfTurn" );
				$activePlayerId = self::getActivePlayerId(); // get the current active player who just aimed... Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$gunId = $this->getGunIdHeldByPlayer($activePlayerId); // get the gun that was just aimed

				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active because someone might be aiming after picking up a gun off-turn)
				$this->gamestate->changeActivePlayer( $playerWhoseTurnItIs ); // change to the player it's turn it's supposed to be

				$gunAcquiredInState = $this->getGunAcquiredInState($gunId); // see which state the gun was acquired in
				if($gunAcquiredInState == "chooseEquipmentToPlayOnYourTurn")
				{
						$this->gamestate->nextState("playerTurn"); // go back to that state
				}
				elseif($gunAcquiredInState == "chooseEquipmentToPlayReactInvestigate")
				{
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("askInvestigateReaction"); // go back to that state
				}
				elseif($gunAcquiredInState == "chooseEquipmentToPlayReactShoot")
				{
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("askShootReaction"); // go back to that state
				}
				elseif($gunAcquiredInState == "chooseEquipmentToPlayEndOfTurn")
				{
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("endTurnReaction"); // go back to that state
				}
				else
				{ // we shouldn't get here
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("endTurnReaction"); // go back to that state
				}
		}

		// This is called in a "game" state after someone draws an Equipment card out of turn and they are over their hand limit. We need to figure out which state we need
		// to go back to and which player is active.
		function afterDiscardedOutOfTurn()
		{
			//throw new feException( "afterDiscardedOutOfTurn" );

				$equipmentId = $this->getEquipmentCardIdInUse(); // find the equipment in use
				$playedInState = $this->getEquipmentPlayedInState($equipmentId); // find the state we need to return to after that equipment is resolve

				// figure out who the next player should be
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active because someone might be aiming after picking up a gun off-turn)
				$this->gamestate->changeActivePlayer( $playerWhoseTurnItIs ); // change to the player it's turn it's supposed to be

				if($playedInState == "chooseEquipmentToPlayOnYourTurn")
				{
						$this->gamestate->nextState("playerTurn"); // go back to that state
				}
				elseif($playedInState == "chooseEquipmentToPlayReactInvestigate")
				{
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("askInvestigateReaction"); // go back to that state
				}
				elseif($playedInState == "chooseEquipmentToPlayReactShoot")
				{
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("askShootReaction"); // go back to that state
				}
				elseif($playedInState == "chooseEquipmentToPlayEndOfTurn")
				{
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("endTurnReaction"); // go back to that state
				}
				else
				{ // we shouldn't get here
						$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
						$this->gamestate->nextState("endTurnReaction"); // default go to end of turn
				}
		}


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
		function argGetOtherPlayerNames()
		{
				return array(
						'validPlayers' => self::getOtherPlayers()
				);
		}

		function argGetAllPlayerNames()
		{
				return array(
						'validPlayers' => self::getAllPlayers()
				);
		}

		function argGetPlayerTurnButtonList()
		{
				return array(
						'buttonList' => self::getPlayerTurnButtonList()
				);
		}

		function argGetPlayerTurnDiscardToDiscardButtonList()
		{
				return array(
						'buttonList' => self::getPlayerTurnDiscardToDiscardButtonList(true)
				);
		}

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

		// This is called in a "game" state after a player turn has ended.
		function endTurnCleanup()
		{
				$guns = $this->getAllGuns(); // get the guns that are currently shooting (should just be 1)
				foreach( $guns as $gun )
				{ // go through each gun
						$gunId = $gun['gun_id'];

						$this->setGunCanShoot($gunId, 1); // make sure it can shoot in case taser was used on it this turn
				}

				if($this->isCoffeeActive())
				{ // coffee is active so we need to go to a specific player's turn
						$coffeeId = $this->getCoffeeId();
						$coffeeOwnerId = $this->getEquipmentCardOwner($coffeeId);
						//throw new feException( "coffeeOwnerId:$coffeeId coffeeOwnerId:$coffeeOwnerId" );
						$this->gamestate->changeActivePlayer( $coffeeOwnerId ); // make coffee owner go next
						//throw new feException( "player $coffeeOwnerId is now the active player." );
						$this->discardActivePlayerEquipmentCard($coffeeId); // discard the player's equipment card
				}
				else
				{ // coffee is NOT active so we can act normally
						if($this->isTurnOrderClockwise())
						{ // the turn order is going clockwise
								$this->activeNextPlayer(); // go to the next player clockwise in turn order
						}
						else
						{ // the turn order is going counter-clockwise
								$this->activePrevPlayer(); // go to the next player counter-clockwise in turn order
						}
				}

				$newActivePlayer = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$this->setGameStateValue("CURRENT_PLAYER", $newActivePlayer);

				if($this->isPlayerEliminated($newActivePlayer))
				{ // skip the player if they are eliminated
							$this->endTurnCleanup(); // recursively call this
				}
				else
				{ // the player is NOT eliminated
						$this->gamestate->nextState( "startNewPlayerTurn" ); // begin a new player's turn
				}

				// notify all players the turn has ended to they can remove highlights
				self::notifyAllPlayers( "endTurn", clienttranslate( '' ), array(
						'player_name' => self::getActivePlayerName()
				) );
		}

		function executeActionInvestigate()
		{
				// update the integrity cards seen table
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				$playerInvestigated = $this->getLastPlayerInvestigated($playerWhoseTurnItIs);
				$positionOfCardInvestigated = $this->getLastCardPositionInvestigated($playerWhoseTurnItIs);

				$cardId = $this->getCardIdFromPlayerAndPosition($playerInvestigated, $positionOfCardInvestigated);

				$this->investigateCard($cardId, $playerWhoseTurnItIs); // investigate this card and notify players

				$this->setStateAfterTurnAction($playerWhoseTurnItIs); // see which state we go into after completing this turn action

		}

		function executeActionEquip()
		{
				$playerWhoseTurnItIs = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.


				// draw an equipment card
				$this->drawEquipmentCard($playerWhoseTurnItIs, 1); // draw 1 equipment card

				// reveal the card of the player who armed
				$integrityCardPositionRevealed = $this->getLastCardPositionRevealed($playerWhoseTurnItIs); // get the card position revealed
				$this->revealCard($playerWhoseTurnItIs, $integrityCardPositionRevealed); // reveal the integrity card from this player's perspective and notify all players

				$this->setStateAfterTurnAction($playerWhoseTurnItIs);
		}


		function executeActionArm()
		{
				// make the active player the owner of the gun by updating the database
				$playerWhoseTurnItIs = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
				$gun = $this->pickUpGun($playerWhoseTurnItIs, $this->getStateName());

				// reveal the card of the player who armed
				$integrityCardPositionRevealed = $this->getLastCardPositionRevealed($playerWhoseTurnItIs); // get the card position revealed
				$this->revealCard($playerWhoseTurnItIs, $integrityCardPositionRevealed); // reveal the integrity card from this player's perspective and notify all players

				$this->gamestate->nextState( "askAim" ); // begin a new player's turn
		}

		// All equipment cards have been resolved in reaction to a SHOOT action so it's time to
		// resolve the SHOOT action.
		function executeActionShoot()
		{
				$guns = $this->getGunsShooting();
				if(count($guns) < 1)
				{ // something happened where the player who was shooting no longer has a gun
						self::notifyAllPlayers( "noGuns", clienttranslate( 'The gun that was shooting was dropped so it does not fire.' ), array(
								'player_name' => self::getActivePlayerName()
						) );
				}

				foreach( $guns as $gun )
				{ // go through each gun that is currently shooting
						$targetPlayerId = $gun['gun_aimed_at']; // get the PLAYER ID of the target of this gun
						$gunId = $gun['gun_id'];
						$heldByPlayerId = $gun['gun_held_by']; // get the player shooting

						$this->shootPlayer($targetPlayerId); // shoot the player
						$this->dropGun($gunId); // drop the gun in the database (do this BEFORE setState so you know whether you should ask them to aim or not)
				}

				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				$this->setStateAfterTurnAction($playerWhoseTurnItIs); // see which state we go into after completing this turn action
		}

		// This is called when we entered the state to play an equipment card, which means all the input required has
		// been set in the database and we can simply resolve the equipment.
		function executeEquipmentPlay()
		{
				$equipmentId = $this->getEquipmentCardIdInUse(); // get the ID of the equipment card that is being played

				$stateName = $this->getEquipmentPlayedInState($equipmentId); // get the state in which this equipment was played (DO THIS BEFORE RESOLVING BECAUSE DISCARDING WILL CLEAR IT)

				$this->resolveEquipment($equipmentId); // take all the input saved in the database and resolve the equipment card

				$unaimedGuns = $this->getHeldUnaimedGuns(); // see if we have an unaimed guns held by a player
				$playersOverEquipmentCardLimit = $this->getPlayersOverEquipmentHandLimit(); // get any players over the equipment card hand limit
				if(count($playersOverEquipmentCardLimit) > 0)
				{ // someone is over the equipment card hand limit
						foreach($playersOverEquipmentCardLimit as $player)
						{ // go through each player over the hand limit
								$playerIdOverLimit = $player['player_id'];

								$this->gamestate->changeActivePlayer($playerIdOverLimit); // make that player active so they can aim it
								$this->gamestate->nextState( "askDiscardOutOfTurn" );
						}
				}
				elseif(count($unaimedGuns) > 0)
				{ // there IS an unaimed gun
							foreach($unaimedGuns as $unaimedGun)
							{ // go through each unaimed gun (there should only be 1)
									$gunId = $unaimedGun['gun_id'];

									$gunHolder = $this->getPlayerIdOfGunHolder($gunId); // get the player holding the unaimed gun
									$this->gamestate->changeActivePlayer($gunHolder); // make that player active so they can aim it
									$this->gamestate->nextState( "askAimOutOfTurn" );
							}
				}
				else
				{ // there are no special situations we need to handle
						if($stateName == "chooseEquipmentToPlayOnYourTurn" || $stateName == "playerTurn")
						{ // this was NOT played in reaction to something

								// do NOT set players to multiactive because we are just going back to their turn
								$this->gamestate->nextState( "playerTurn" ); // go back to player action state
						}
//						elseif($stateName == "chooseEquipmentToPlayEndOfTurn")
						elseif($stateName == "askEndTurnReaction" || $stateName == "chooseEquipmentToPlayReactEndOfTurn")
						{
								$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
								$this->gamestate->nextState( "endTurnReaction" ); // go back to allowing other players to play equipment (which state depends on the state we came from)
						}
						elseif($stateName == "chooseEquipmentToPlayReactInvestigate")
						{
								$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
								$this->gamestate->nextState( "askInvestigateReaction" ); // go back to allowing other players to play equipment (which state depends on the state we came from)
						}
						elseif($stateName == "chooseEquipmentToPlayReactShoot")
						{
								$this->setEquipmentHoldersToActive(); // set anyone holding equipment to active
								$this->gamestate->nextState( "askShootReaction" ); // go back to allowing other players to play equipment (which state depends on the state we came from)
						}
						elseif($stateName == "chooseEquipmentCardInAnyHand")
						{
								$nextState = $this->getEquipmentPlayedInState($equipmentId); // get the state this was played in
								$this->gamestate->nextState( $nextState ); // go back to allowing other players to play equipment (which state depends on the state we came from)
						}
						else
						{ // this was played in reaction to something
								throw new feException( "Unknown equipment usage state: ".$stateName );
						}
				}
		}

		function chooseEquipmentTargetOutOfTurn()
		{
				$equipmentId = $this->getEquipmentCardIdInUse();
				$collectorNumber = $this->getCollectorNumberFromId($equipmentId);
				//throw new feException( "collectorId: $collectorNumber");
				switch($collectorNumber)
				{
						case 44: // riot shield
								$playerIdGettingShot = $this->getPlayerGettingShot();
								if(is_null($playerIdGettingShot))
								{
										throw new feException( "Could not find a player getting shot when Riot Shield was played.");
								}

								$this->setEquipmentPlayerTarget($equipmentId, $playerIdGettingShot); // set the player getting shot to the target 1
								$this->gamestate->changeActivePlayer($playerIdGettingShot); // make the player getting shot the active player so they can choose who gets shot
								//throw new feException( "player $playerIdGettingShot is now the active player." );
								$this->gamestate->nextState( "choosePlayer" );
						break;
						case 11: // restraining order
								$playerIdShooting = $this->getPlayerShooting();
								//throw new feException( "Shooting player: $playerIdShooting");
								if(is_null($playerIdShooting))
								{
										throw new feException( "Could not find a player getting shot when Restraining Order was played.");
								}

								$this->setEquipmentPlayerTarget($equipmentId, $playerIdShooting); // set the player getting shot to the target 1
								$this->gamestate->changeActivePlayer($playerIdShooting); // make the player getting shot the active player so they can choose who gets shot
								//throw new feException( "player $playerIdGettingShot is now the active player." );
								$this->gamestate->nextState( "choosePlayer" );
						break;
				}

		}

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
