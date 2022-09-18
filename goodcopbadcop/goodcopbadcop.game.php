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
						"CURRENT_PLAYER" => 10,
						"ROLLED_INFECTION_DIE_THIS_TURN" => 11,
						"ZOMBIES_EXPANSION" => 100,
						"USE_EXTRA_EQUIPMENT" => 101
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
				$this->equipmentCards->autoreshuffle_trigger = array('obj' => $this, 'method' => 'deckAutoReshuffle'); // add a callback method so we know when the deck has been reshuffled
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

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are using the zombies expansion
						$this->initializeZombieDice();
				}

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();
				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				$this->setGameStateValue("CURRENT_PLAYER", $activePlayerId);
				$this->setGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN", 0); // reset whether we rolled the infection die this turn
				//self::incStat( 1, 'turns_number', $activePlayerId ); // increase end game player stat
				self::incStat( 1, 'turns_number' ); // increase end game table stat

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
				$result['playerLetters'] = $this->getPlayerLetterList($currentPlayerId, $result['players']);
				$result['equipmentDetails'] = $this->getEquipmentDetails();


				$result['zombieExpansion'] = $this->getGameStateValue('ZOMBIES_EXPANSION'); // send whether we have the zombies expansion activated
				$result['currentPlayerTurn'] = $this->getGameStateValue('CURRENT_PLAYER'); // let the client know whose turn it is
				$result['isClockwise'] = $this->isTurnOrderClockwise(); // true if we are currently going clockwise
				$result['currentState'] = $this->getStateName();

				$result['currentPlayerName'] = $this->getCurrPlayerName();
				$result['nextPlayerName'] = $this->getNextPlayerName();

        // TODO: Gather all information about current game situation (visible by player $current_player_id).
				//$result['hand'] = $this->integrityCards->getCardsInLocation( $player_id ); // get this player's integrity cards

				// get integrity cards for this player
				$result['revealedCards'] = $this->getAllRevealedCards($currentPlayerId); // all cards that are revealed for everyone
				$result['hiddenCardsIHaveSeen'] = $this->getHiddenCardsIHaveSeen($currentPlayerId); // all cards I've seen
				$result['hiddenCardsIHaveNotSeen'] = $this->getHiddenCardsIHaveNotSeen($currentPlayerId); // get all the hidden cards I have NOT seen

				// get integrity card tokens
				$result['woundedTokens'] = $this->getWoundedTokensForPlayer($currentPlayerId);
				$result['infectionTokens'] = $this->getInfectionTokensForPlayer($currentPlayerId);

				// get dice
				$result['dice'] = $this->getDice();

				// get gun details
        $result['guns'] = $this->getGunsForPlayer($currentPlayerId);
				$result['gunRotations'] = $this->getGunRotationsForPlayer($currentPlayerId);


				// get equipment cards
				$result['myEquipmentCards'] = $this->getEquipmentCardsForPlayer($currentPlayerId); // get all of the requesting player's equipment cards
				$result['opponentEquipmentCards'] = $this->getEquipmentCardCountsOpponentsOf($currentPlayerId); // get all the equipment cards held by opponents of the requesting player
				$result['sharedActiveEquimentCards'] = $this->getSharedActiveEquipmentCards(); // get all the active equipment cards in the center
				$result['playerActiveEquipmentCards'] = $this->getPlayerActiveEquipmentCards($currentPlayerId); // get all the active equipment cards targeting a specific player
				$result['equipment_effects'] = $this->getEquipmentEffects();
				$result['equipmentList'] = $this->getEquipmentList();

				$result['eliminatedPlayers'] = $this->getEliminatedPlayers($currentPlayerId);
				$result['zombiePlayers'] = $this->getZombiesWithLetterOrder($currentPlayerId);
				$result['player_colors'] = $this->getPlayerColorsByName();

				$result['gun_rotations'] = $this->getGunRotationsForAll();
				$result['is_gun_pointing_left'] = $this->getIsGunPointingLeftForAll();

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
								$numberOfPlayersWhoHaveSeenThis = $this->getNumberOfPlayersWhoHaveSeenCard($card_id) - 1; // subtract 1 because we don't want to incldue the player whose card it is
								if($numberOfPlayersWhoHaveSeenThis < 0)
								{
										$numberOfPlayersWhoHaveSeenThis = 0;
								}

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

		function deckAutoReshuffle()
		{
				$this->resetEquipmentDeckAfterReshuffle();

				self::notifyAllPlayers( "equipmentDeckReshuffled", clienttranslate( 'The equipment deck has been reshuffled.' ), array(
					'allEquipment' => $this->getEquipmentList()
				) );
		}

		function initializeStats()
		{
				// TABLE STATS
				self::initStat( 'table', 'turns_number', 0 );
				self::initStat( 'table', 'winning_team', 0 );
				self::initStat( 'table', 'honest_at_start', 0 );
				self::initStat( 'table', 'crooked_at_start', 0 );
				self::initStat( 'table', 'honest_at_end', 0 );
				self::initStat( 'table', 'crooked_at_end', 0 );


				// PLAYER STATS
				self::initStat( 'player', 'starting_role', 0 );
				self::initStat( 'player', 'ending_role', 0 );
				self::initStat( 'player', 'investigations_completed', 0 );
				self::initStat( 'player', 'equipment_acquired', 0 );
				self::initStat( 'player', 'equipment_used', 0 );
        self::initStat( 'player', 'guns_acquired', 0 );
        self::initStat( 'player', 'guns_aimed_at_me', 0 );
        self::initStat( 'player', 'opponents_shot', 0 );
        self::initStat( 'player', 'teammates_shot', 0 );
        self::initStat( 'player', 'bullets_taken', 0 );

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are using the zombies expansion
						self::initStat( 'table', 'zombies_at_start', 0 );
						self::initStat( 'table', 'zombies_at_end', 0 );

						self::initStat( 'player', 'players_bitten', 0 );
		        self::initStat( 'player', 'bites_taken', 0 );
				}
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
					  //array( 'type' => 'equipment', 'type_arg' => 37, 'card_location' => 'deck','nbr' => 1), // Mobile Detonator
					  //array( 'type' => 'equipment', 'type_arg' => 17, 'card_location' => 'deck','nbr' => 1), // Deliriant

						// 4TH EDITION
						array( 'type' => 'equipment', 'type_arg' => 15, 'card_location' => 'deck','nbr' => 1), // Truth Serum
						array( 'type' => 'equipment', 'type_arg' => 12, 'card_location' => 'deck','nbr' => 1), // Smoke Grenade
						array( 'type' => 'equipment', 'type_arg' => 2, 'card_location' => 'deck','nbr' => 1), // Coffee
						array( 'type' => 'equipment', 'type_arg' => 16, 'card_location' => 'deck','nbr' => 1), // Wiretap
						array( 'type' => 'equipment', 'type_arg' => 8, 'card_location' => 'deck','nbr' => 1), // Planted Evidence
						array( 'type' => 'equipment', 'type_arg' => 44, 'card_location' => 'deck','nbr' => 1), // Riot Shield
						array( 'type' => 'equipment', 'type_arg' => 11, 'card_location' => 'deck','nbr' => 1), // Restraining Order
						array( 'type' => 'equipment', 'type_arg' => 4, 'card_location' => 'deck','nbr' => 1), // Evidence Bag
						array( 'type' => 'equipment', 'type_arg' => 35, 'card_location' => 'deck','nbr' => 1), // Med Kit
						array( 'type' => 'equipment', 'type_arg' => 14, 'card_location' => 'deck','nbr' => 1), // Taser
						array( 'type' => 'equipment', 'type_arg' => 3, 'card_location' => 'deck','nbr' => 2), // Defibrillator
						array( 'type' => 'equipment', 'type_arg' => 1, 'card_location' => 'deck','nbr' => 1), // Blackmail
						array( 'type' => 'equipment', 'type_arg' => 30, 'card_location' => 'deck','nbr' => 1), // Disguise
						array( 'type' => 'equipment', 'type_arg' => 45, 'card_location' => 'deck','nbr' => 1), // Walkie Talkie
						array( 'type' => 'equipment', 'type_arg' => 9, 'card_location' => 'deck','nbr' => 1), // Polygraph
						array( 'type' => 'equipment', 'type_arg' => 13, 'card_location' => 'deck','nbr' => 1), // Surveillance Camera
						array( 'type' => 'equipment', 'type_arg' => 7, 'card_location' => 'deck','nbr' => 1), // Metal Detector
						array( 'type' => 'equipment', 'type_arg' => 6, 'card_location' => 'deck','nbr' => 1) // K-9 Unit
				);

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are using the zombies expansion
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 60, 'card_location' => 'deck','nbr' => 1)); // Crossbow
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 61, 'card_location' => 'deck','nbr' => 1)); // Transfusion Tube
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 62, 'card_location' => 'deck','nbr' => 1)); // Zombie Serum
 						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 63, 'card_location' => 'deck','nbr' => 1)); // Flamethrower
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 64, 'card_location' => 'deck','nbr' => 1)); // Chainsaw
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 65, 'card_location' => 'deck','nbr' => 1)); // Zombie Mask
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 66, 'card_location' => 'deck','nbr' => 1)); // Machete
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 67, 'card_location' => 'deck','nbr' => 1)); // Weapon Crate
						array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 68, 'card_location' => 'deck','nbr' => 1)); // Alarm Clock

						if($this->getGameStateValue('USE_EXTRA_EQUIPMENT') == 2)
						{ // they want to use all extra equipment

								// add all the expansion equipment that is not specific to other expansions

						}
				}
				else
				{ // no expansions are being used

						if($this->getGameStateValue('USE_EXTRA_EQUIPMENT') == 2)
						{ // they want to use all extra equipment

								// add all the expansion equipment that is not specific to that expansion
								array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 64, 'card_location' => 'deck','nbr' => 1)); // Chainsaw
								array_push($equipmentCardsList, array( 'type' => 'equipment', 'type_arg' => 60, 'card_location' => 'deck','nbr' => 1)); // Crossbow
						}
				}

				$this->equipmentCards->createCards( $equipmentCardsList, 'deck' ); // create the deck and override locations to deck
				$this->equipmentCards->shuffle( 'deck' ); // shuffle it

				// setting names of equipment since doing it above in the createCards method doesn't work for an unknown reason
				//$this->setEquipmentName(37, 'Mobile Detonator');
				//$this->setEquipmentName(17, 'Deliriant');

				// 4TH EDITION
				$this->setEquipmentName(2, 'Coffee');
				$this->setEquipmentName(8, 'Planted Evidence');
				$this->setEquipmentName(12, 'Smoke Grenade');
				$this->setEquipmentName(15, 'Truth Serum');
				$this->setEquipmentName(16, 'Wiretap');
				$this->setEquipmentName(44, 'Riot Shield');
				$this->setEquipmentName(11, 'Restraining Order');
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
				$this->setEquipmentName(6, 'K-9 Unit');

				// ZOMBIES
				$this->setEquipmentName(60, 'Crossbow');
				$this->setEquipmentName(61, 'Transfusion Tube');
				$this->setEquipmentName(62, 'Zombie Serum');
				$this->setEquipmentName(63, 'Flamethrower');
				$this->setEquipmentName(64, 'Chainsaw');
				$this->setEquipmentName(65, 'Zombie Mask');
				$this->setEquipmentName(66, 'Machete');
				$this->setEquipmentName(67, 'Weapon Crate');
				$this->setEquipmentName(68, 'Alarm Clock');
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

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are using the zombies expansion
						array_push($integrityCardsList, array( 'type' => 'infector', 'type_arg' => 0, 'card_location' => 'deck','nbr' => 1)); // add the infector
				}

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

		function initializeZombieDice()
		{
				$insertZombieDie1Query = "INSERT INTO dice (die_id,die_type,die_value) VALUES ";
				$insertZombieDie1Query .= "(1,'zombie',0) ";
				self::DbQuery( $insertZombieDie1Query );

				$insertZombieDie2Query = "INSERT INTO dice (die_id,die_type,die_value) VALUES ";
				$insertZombieDie2Query .= "(2,'zombie',0) ";
				self::DbQuery( $insertZombieDie2Query );

				$insertZombieDie3Query = "INSERT INTO dice (die_id,die_type,die_value) VALUES ";
				$insertZombieDie3Query .= "(3,'zombie',0) ";
				self::DbQuery( $insertZombieDie3Query );

				$insertInfectionQuery = "INSERT INTO dice (die_id,die_type,die_value) VALUES ";
				$insertInfectionQuery .= "(4,'infection',0) ";
				self::DbQuery( $insertInfectionQuery );
		}

		function initializeGuns($players)
		{
				$editionUsed = 4; // which edition of GCBC are we using

				if($editionUsed == 3)
				{
						$insertGun1Query = "INSERT INTO guns (gun_type,gun_id,gun_held_by,gun_aimed_at) VALUES ";
						$insertGun1Query .= "('gun',1,'','') ";
						self::DbQuery( $insertGun1Query );

						$insertGun2Query = "INSERT INTO guns (gun_type,gun_id,gun_held_by,gun_aimed_at) VALUES ";
						$insertGun2Query .= "('gun',2,'','') ";
						self::DbQuery( $insertGun2Query );

						if(count($players) > 4)
						{ // 5+ players
								$insertGun3Query = "INSERT INTO guns (gun_type,gun_id,gun_held_by,gun_aimed_at) VALUES ";
								$insertGun3Query .= "('gun',3,'','') ";
								self::DbQuery( $insertGun3Query );
						}

						if(count($players) > 6)
						{ // 7+ players
								$insertGun4Query = "INSERT INTO guns (gun_type,gun_id,gun_held_by,gun_aimed_at) VALUES ";
								$insertGun4Query .= "('gun',4,'','') ";
								self::DbQuery( $insertGun4Query );
						}
				}
				elseif($editionUsed == 4)
				{	// use fourth edition rules where there is one gun for each player
						for ($x = 0; $x < count($players); $x+=1)
						{
								$insertGunsQuery = "INSERT INTO guns (gun_type,gun_held_by,gun_aimed_at) VALUES ";
								$insertGunsQuery .= "('gun','','') ";
								self::DbQuery( $insertGunsQuery );
						}
				}

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are using the zombies expansion
						for ($x = 0; $x < count($players)-2; $x+=1)
						{
								$insertArmsQuery = "INSERT INTO guns (gun_type,gun_held_by,gun_aimed_at) VALUES ";
								$insertArmsQuery .= "('arm','','') ";
								self::DbQuery( $insertArmsQuery );
						}
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
				$sqlUpdate .= "card_type='agent' OR card_type='kingpin' OR card_type='infector'";

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

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $numberOfPlayers % 2 != 0)
				{ // we are using the zombies expansion and there is an odd number of players
						$honestNeeded--; // subtract one to make room for the infector
				}

				return $honestNeeded;
		}

		function dealIntegrityCards($players)
		{
				$this->moveLeadersToInitialDeal(); // put the agent and kingpin in the initial deal

				$numberOfPlayers = count($players); // get number of players
				$numberOfExtraCards = 0;
				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are playing with the Zombies expansion, which includes the Infector
						$numberOfExtraCards = $numberOfPlayers - 3; // subtract 3 to account for Agent and Kingpin and Infector
				}
				else
				{ // we are NOT playing with the zombies expansion
						$numberOfExtraCards = $numberOfPlayers - 2; // subtract 2 to account for Agent and Kingpin
				}

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

		function getSoloWinner()
		{
				$kingpinPlayerId = 0;
				$agentPlayerId = 0;

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of this player
						$playerRole = $this->getPlayerRole($playerId); // crooked_kingpin, honest_cop, etc.

						if($playerRole == 'kingpin_agent' ||
						   $playerRole == 'infector_agent' ||
							 $playerRole == 'infector_kingpin')
						{ // this player has two leader/infector cards
								return $playerId;
						}
				}

				return null;
		}

		function countPlayersOnTeams($startOrEnd)
		{
			$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
			foreach( $players as $player )
			{ // go through each player
				$playerId = $player['player_id']; // the ID of this player
				$playerRole = $this->getPlayerRole($playerId); // crooked_kingpin, honest_cop, etc.
				$playerRoleAsInt = $this->convertPlayerRoleToInt($playerRole); // convert to int for stat purposes
				$playerTeam = $this->getPlayerTeam($playerId); // crooked, honest, zombie
				$playerTeamAsInt = $this->convertPlayerTeamToInt($playerTeam); // convert to int for stat purposes


				if($playerRole == 'honest_agent')
				{ // honest leader
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::incStat( 1, 'honest_at_start' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::incStat( 1, 'honest_at_end' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
					  }
				}
				elseif($playerRole == 'honest_cop')
				{ // honest cop
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::incStat( 1, 'honest_at_start' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::incStat( 1, 'honest_at_end' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}
				elseif($playerRole == 'crooked_kingpin')
				{ // crooked leader
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::incStat( 1, 'crooked_at_start' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::incStat( 1, 'crooked_at_end' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}
				elseif($playerRole == 'crooked_cop')
				{ // crooked cop
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::incStat( 1, 'crooked_at_start' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::incStat( 1, 'crooked_at_end' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}
				elseif($playerRole == 'kingpin_agent')
				{ // crooked cop
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}
				elseif($playerRole == 'zombie_infector')
				{ // infector
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::incStat( 1, 'zombies_at_start' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::incStat( 1, 'zombies_at_end' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}
				elseif($playerRole == 'zombie_minion')
				{ // zombie minion
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::incStat( 1, 'zombies_at_start' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::incStat( 1, 'zombies_at_end' ); // increase end game table stat
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}
				elseif($playerRole == 'infector_kingpin')
				{ // infector kingpin
						//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}
				elseif($playerRole == 'infector_agent')
				{ // infector agent
					//throw new feException( "Player role is $playerRole and playerRoleAsInt is $playerRoleAsInt and playerTeam is $playerTeam and playerTeamAsInt is $playerTeamAsInt");
						if($startOrEnd == 'start')
						{
								self::setStat( $playerRoleAsInt, 'starting_role', $playerId ); // update stat for ending team
						}
						else
						{
								self::setStat( $playerRoleAsInt, 'ending_role', $playerId ); // update stat for ending team
						}
				}



			}
		}

		function sendNewGameMessage($playerId)
		{
				if($this->getPlayerRole($playerId) == 'honest_agent')
				{ // honest leader
						self::notifyPlayer( $playerId, 'newGameMessage', clienttranslate('You are secretly the LEADER of the HONEST Team. Your mission is to find and eliminate the KINGPIN while keeping your identity hidden as long as possible.'), array() );
				}
				elseif($this->getPlayerRole($playerId) == 'honest_cop')
				{ // honest cop
						self::notifyPlayer( $playerId, 'newGameMessage', clienttranslate('You are secretly on the HONEST Team. Your mission is to find and eliminate the KINGPIN while helping your leader (the AGENT) stay hidden and unharmed.'), array() );
				}
				elseif($this->getPlayerRole($playerId) == 'crooked_kingpin')
				{ // crooked leader
						self::notifyPlayer( $playerId, 'newGameMessage', clienttranslate('You are secretly the LEADER of the CROOKED Team. Your mission is to find and eliminate the AGENT while keeping your identity hidden as long as possible.'), array() );
				}
				elseif($this->getPlayerRole($playerId) == 'zombie_infector')
				{ // infector
						self::notifyPlayer( $playerId, 'newGameMessage', clienttranslate('You are secretly on the ZOMBIE team. Your mission is to eliminate non-Leaders so they join your team while staying hidden. Once you are revealed, you want to Bite the Agent or Kingpin.'), array() );
				}
				else
				{ // crooked cop
						self::notifyPlayer( $playerId, 'newGameMessage', clienttranslate('You are secretly on the CROOKED Team. Your mission is to find and eliminate the AGENT while helping your leader (the KINGPIN) stay hidden and unharmed.'), array() );
				}
		}

		function dealEquipmentCards($players)
		{
				foreach( $players as $player_id => $player )
				{
						$this->drawEquipmentCard($player_id, 1); // have each player draw 1 equipment card
				}
		}

		function getEquipmentDetails()
		{
				$equipmentList = array();

				$sql = "SELECT * FROM equipmentCards ORDER BY equipment_name ASC ";
				$equipmentListFromDb = self::getCollectionFromDb( $sql );

				foreach( $equipmentListFromDb as $card )
				{
						$collectorNumber = $card['card_type_arg'];

						$equipName = $this->getTranslatedEquipmentName($collectorNumber);
						$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

						$equipmentList[$collectorNumber] = array( 'equip_name' => $equipName, 'equip_effect' => $equipEffect, 'collector_number' => $collectorNumber);
				}

				return $equipmentList;
		}

		function getPlayerLetterList($askingPlayer, $playerList)
		{
				$playerLetterList = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

				foreach( $playerList as $player_id => $player )
				{
						$playerLetter = $this->getLetterOrderFromPlayerIds($askingPlayer, $player_id); // have each player draw 1 equipment card
						$playerLetterList[$player_id] = array( 'player_id' => $player_id, 'player_letter' => $playerLetter );
				}

				return $playerLetterList;
		}

		function getEquipmentList()
		{
				$equipmentList = array();

				$sql = "SELECT * FROM equipmentCards ORDER BY equipment_name ASC ";
				$equipmentListFromDb = self::getCollectionFromDb( $sql );

				$index = 0;
				foreach( $equipmentListFromDb as $card )
				{
						$cardId = $card['card_id'];
						$collectorNumber = $card['card_type_arg'];
						$location = $card['card_location'];
						$locationArg = $card['card_location_arg'];
						$playedOnTurn = $card['equipment_played_on_turn'];
						$discardedBy = $card['discarded_by'];
						$equipName = $this->getTranslatedEquipmentName($collectorNumber);
						$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

						$equipmentList[$index] = array( 'card_id' => $cardId, 'card_type_arg' => $collectorNumber, 'equip_name' => $equipName, 'equip_effect' => $equipEffect, 'card_location' => $location, 'card_location_arg' => $locationArg, 'equipment_played_on_turn' => $playedOnTurn, 'discarded_by' => $discardedBy);

						$index++;
				}

				return $equipmentList;
		}

		function doesPlayerHaveRevealedLeader($playerId)
		{
				$revealedCards = $this->getRevealedCardsForPlayer($playerId);

				foreach($revealedCards as $card)
				{
							$cardId = $card['card_id'];
							$cardType = $card['card_type'];
							if($cardType == 'kingpin' || $cardType == 'agent')
							{ // leader revealed
									return true; // revealed leader found
							}
				}

				return false; // no revealed leader found
		}

		function getRevealedCardsForPlayer($playerId)
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_type_arg=1 AND ic.card_location=$playerId ";
				return self::getCollectionFromDb( $sql );
		}

		function getAllRevealedCards($playerId)
		{
				if(self::isSpectator())
				{ // this is a spectator
						$playerId = $this->getPlayerIdFromPlayerNo(1);

						$sql = "SELECT ic.card_id, ic.card_type, ic.card_type_arg, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
						$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
						$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
						$sql .= "WHERE card_type_arg=1 ";
						$cardArray = self::getCollectionFromDb( $sql );
				}
				else
				{ // this is NOT a spectator
						$sql = "SELECT ic.card_id, ic.card_type, ic.card_type_arg, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
						$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
						$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
						$sql .= "WHERE card_type_arg=1 ";
						$cardArray = self::getCollectionFromDb( $sql );
				}

				foreach($cardArray as $card)
				{
							$cardId = $card['card_id'];

							// get list of players seen
							$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
							$cardArray[$cardId]['player_list'] = $listOfPlayersSeen; // add the list of players into this array

							// get whether it is affected by planted evidence
							$isAffectedByPlantedEvidence = $this->isAffectedByPlantedEvidence($cardId);
  						$cardArray[$cardId]['affectedByPlantedEvidence'] = $isAffectedByPlantedEvidence;

							$isAffectedByDisguise = $this->isAffectedByDisguise($cardId);
  						$cardArray[$cardId]['affectedByDisguise'] = $isAffectedByDisguise;

							$isAffectedBySurveillanceCamera = $this->isAffectedBySurveillanceCamera($cardId);
  						$cardArray[$cardId]['affectedBySurveillanceCamera'] = $isAffectedBySurveillanceCamera;
				}

				return $cardArray;
		}

		function getAllHiddenCards()
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_type_arg=0 ";

				return self::getCollectionFromDb( $sql );
		}

		function getAllOpponentHiddenCards($playerId)
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location<>$playerId AND ic.card_type_arg=0 ";

				return self::getCollectionFromDb( $sql );
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

							// get list of players seen
							$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
							$cardArray[$cardId]['player_list'] = $listOfPlayersSeen; // add the list of players into this array

							// get whether it is affected by planted evidence
							$isAffectedByPlantedEvidence = $this->isAffectedByPlantedEvidence($cardId);
  						$cardArray[$cardId]['affectedByPlantedEvidence'] = $isAffectedByPlantedEvidence;

							$isAffectedByDisguise = $this->isAffectedByDisguise($cardId);
  						$cardArray[$cardId]['affectedByDisguise'] = $isAffectedByDisguise;

							$isAffectedBySurveillanceCamera = $this->isAffectedBySurveillanceCamera($cardId);
  						$cardArray[$cardId]['affectedBySurveillanceCamera'] = $isAffectedBySurveillanceCamera;
				}

				return $cardArray;
		}

		function getHiddenCardsIHaveNotSeen($playerId)
		{
				if(self::isSpectator())
				{ // this is a spectator
						$playerId = $this->getPlayerIdFromPlayerNo(1);

						$sql = "SELECT ic.card_id, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
						$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
						$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
						$sql .= "WHERE ic.card_type_arg=0 ";

						$cardArray = self::getCollectionFromDb( $sql );
				}
				else
				{ // this is NOT a spectator
						// only give basic information... not the card values (since this player has not seen these yet)
						$sql = "SELECT ic.card_id, ic.card_location, ic.card_location_arg, pp.player_position FROM `integrityCards` ic ";
						$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
						$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerId) ";
						$sql .= "WHERE pcv.player_id=$playerId AND pcv.is_seen=0 and ic.card_type_arg=0 ";

						$cardArray = self::getCollectionFromDb( $sql );
				}

						foreach($cardArray as $card)
						{
									$cardId = $card['card_id'];

									// get list of players seen
									$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
									$cardArray[$cardId]['player_list'] = $listOfPlayersSeen; // add the list of players into this array

									// get whether it is affected by planted evidence
									$isAffectedByPlantedEvidence = $this->isAffectedByPlantedEvidence($cardId);
		  						$cardArray[$cardId]['affectedByPlantedEvidence'] = $isAffectedByPlantedEvidence;

									$isAffectedByDisguise = $this->isAffectedByDisguise($cardId);
		  						$cardArray[$cardId]['affectedByDisguise'] = $isAffectedByDisguise;

									$isAffectedBySurveillanceCamera = $this->isAffectedBySurveillanceCamera($cardId);
		  						$cardArray[$cardId]['affectedBySurveillanceCamera'] = $isAffectedBySurveillanceCamera;
						}


				return $cardArray;
		}

		function getHiddenCardsFromPlayer($playerId)
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerId AND ic.card_type_arg=0 ";

				return self::getCollectionFromDb( $sql );
		}

		function getLeaderCardTypeForPlayer($playerId)
		{
				return self::getUniqueValueFromDb("SELECT ic.card_type FROM `integrityCards` ic WHERE ic.card_location=$playerId AND (ic.card_type='agent' OR ic.card_type='kingpin') LIMIT 1");
		}

		function getLeaderCardIdForPlayer($playerId)
		{
				return self::getUniqueValueFromDb("SELECT ic.card_id FROM `integrityCards` ic WHERE ic.card_location=$playerId AND (ic.card_type='agent' OR ic.card_type='kingpin') LIMIT 1");
		}

		function getPlayerTeam($playerId)
		{
				$role = $this->getPlayerRole($playerId);

				$roleSplit = explode("_", $role);
				$team = $roleSplit[0]; // honest, crooked, zombie

				return $team;
		}

		function convertPlayerTeamToInt($teamString)
		{
				if($teamString == "honest")
				{
						return 0;
				}
				elseif($teamString == "crooked")
				{
						return 1;
				}
				else
				{ // zombie
						return 2;
				}
		}

		function convertPlayerRoleToInt($roleString)
		{
				if($roleString == "honest_agent")
				{
						return 0;
				}
				elseif($roleString == "honest_cop")
				{
						return 1;
				}
				elseif($roleString == "crooked_kingpin")
				{
						return 2;
				}
				elseif($roleString == "crooked_cop")
				{
						return 3;
				}
				elseif($roleString == "zombie_infector")
				{
						return 4;
				}
				elseif($roleString == "zombie_minion")
				{ // zombie minion
						return 5;
				}
				elseif($roleString == "kingpin_agent")
				{ // has both the kingpin and the agent
						return 6;
				}
				elseif($roleString == "infector_agent")
				{ // has both infector and agent
					 return 7;
				}
				elseif($roleString == "infector_kingpin")
				{ // has both infector and kingpin
					 return 8;
				}
		}

		// Return honest_cop, honest_agent, crooked_kinpin, crooked_cop, kingpin_agent
		function getPlayerRole($playerId)
		{
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerId ";

				$myIntegrityCards = self::getCollectionFromDb( $sql );

				$honestCards = 0;
				$crookedCards = 0;
				$agentCards = 0;
				$kingpinCards = 0;
				$infectorCards = 0;
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
								$agentCards++;
						}
						elseif($cardType == 'kingpin')
						{
								$kingpinCards++;
						}
						elseif($cardType == 'infector')
						{
								$infectorCards++;
						}
						else
						{
								throw new feException( "Unknown Team:$cardType");
						}
				}

				if($agentCards > 0 && $kingpinCards > 0)
				{ // both agent and kingpin
						return 'kingpin_agent';
				}
				elseif($agentCards > 0 && $infectorCards > 0)
				{ // both agent and infector
						return 'infector_agent';
				}
				elseif($kingpinCards > 0 && $infectorCards > 0)
				{ // both kingpin and infector
						return 'infector_kingpin';
				}
				elseif($infectorCards > 0)
				{ // infector
						return 'zombie_infector';
				}
				elseif($agentCards > 0)
				{ // agent
						return 'honest_agent';
				}
				elseif($kingpinCards > 0)
				{ // kingpin
						return 'crooked_kingpin';
				}
				elseif($this->isPlayerZombie($playerId))
				{ // this player is a zombie
						return 'zombie_minion';
				}
				elseif($honestCards > $crookedCards)
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

		function countAllInfectionTokens()
		{
				$infectionTokenCount = 0;

				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.has_infection=1 ";

				$integrityCards = self::getCollectionFromDb( $sql );
				$infectionTokenCount = count($integrityCards);

				return $infectionTokenCount;
		}

		function getNumberOfGunsHeldByPlayersWithHiddenOpponentIntegrityCards($askingPlayerId)
		{
				$gunsHeld = 0;
				$guns = $this->getGunsHeld(); // get all guns currently being held by a player
				foreach( $guns as $gun )
				{ // go through each gun that is currently shooting
						$gunHolderPlayerId = $gun['gun_held_by']; // get the PLAYER ID of the player holding this gun

						if($askingPlayerId != $gunHolderPlayerId)
						{ // we don't want to investigate our own integrity cards

								$hiddenCards = $this->getHiddenCardsFromPlayer($gunHolderPlayerId); // get all this player's hidden integrity cards
								//$count = count($hiddenCards);
								//throw new feException( "count of hidden cards by player ($gunHolderPlayerId) is ($count)");

								if(count($hiddenCards) > 0)
								{ // they have at least one hidden card
										$gunsHeld++;
								}
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

				$target5 = $this->getEquipmentTarget5($equipmentCardId);
				if( !is_null($target5) && $target5 != '' )
				{ // this target is set
					//throw new feException( "false");
						$numberOfTargets++;
				}

				$target6 = $this->getEquipmentTarget6($equipmentCardId);
				if( !is_null($target6) && $target6 != '' )
				{ // this target is set
					//throw new feException( "false");
						$numberOfTargets++;
				}

				$target7 = $this->getEquipmentTarget7($equipmentCardId);
				if( !is_null($target7) && $target7 != '' )
				{ // this target is set
					//throw new feException( "false");
						$numberOfTargets++;
				}

				$target8 = $this->getEquipmentTarget8($equipmentCardId);
				if( !is_null($target8) && $target8 != '' )
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

		function getArrayOfPlayersWhoHaveSeenCard($card_id)
		{
				$playerList = array();

				$sql = "SELECT p.player_id, player_name, player_color, is_seen ";
				$sql .= "FROM `player` p ";
				$sql .= "LEFT JOIN `playerCardVisibility` pcv ON p.player_id=pcv.player_id ";
				$sql .= "WHERE pcv.card_id=$card_id ";
				$sql .= "ORDER BY p.player_name ";

				$cardSeenList = self::getCollectionFromDb( $sql ); // get the list of players and whether they have seen this card
				foreach( $cardSeenList as $seen )
				{
						$playerId = $seen['player_id'];
						$playerName = $seen['player_name'];
						$playerColor = $seen['player_color'];
						$isSeen = $seen['is_seen'];

						$playerList[$playerId] = $isSeen;
				}

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

		function getLivingPlayers()
		{
				$sql = "SELECT * FROM `player` ";
				$sql .= "WHERE is_eliminated=0 ";

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

				$cardArray = array(); // this is what we will return

				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=12 ";

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

		function isCoffeeActive()
		{
				// coffee has card_type_arg=2

				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=2 ";

				$coffeeList = self::getObjectListFromDB( $sql );

				if(count($coffeeList) > 0)
				{
						foreach($coffeeList as $card)
						{
								return $card['card_id'];
						}
				}
				else
				{
					return 0;
				}
		}

		function isRestrainingOrderActive()
		{
				// coffee has card_type_arg=2

				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=11 ";

				$coffeeList = self::getObjectListFromDB( $sql );

				if(count($coffeeList) > 0)
				{
					foreach($coffeeList as $card)
					{
							return $card['card_id'];
					}
				}
				else
				{
					return 0;
				}
		}

		function isWeaponCrateActive()
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=67 ";

				$activeList = self::getObjectListFromDB( $sql );

				if(count($activeList) > 0)
				{
						foreach($activeList as $card)
						{
								return $card['card_id'];
						}
				}
				else
				{
					return 0;
				}
		}

		function isZombieSerumActive()
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=62 ";

				$activeList = self::getObjectListFromDB( $sql );

				if(count($activeList) > 0)
				{
						foreach($activeList as $card)
						{
								return $card['card_id'];
						}
				}
				else
				{
					return 0;
				}
		}

		function isRiotShieldActive()
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 AND card_type_arg=44 ";

				$coffeeList = self::getObjectListFromDB( $sql );

				if(count($coffeeList) > 0)
				{
					foreach($coffeeList as $card)
					{
							return $card['card_id'];
					}
				}
				else
				{
					return 0;
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

		function getActiveEquipmentCards()
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 ";

				//var_dump( $sql );
				//die('ok');

				return self::getObjectListFromDB( $sql );
		}

		function getActiveEquipmentCardIdsForPlayer($playerId)
		{
				$cardIdArray = array();

				$sql = "SELECT card_id FROM `equipmentCards` ";
				$sql .= "WHERE equipment_owner=$playerId AND equipment_is_active=1 ";

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

		// Get all equipment cards that are currently active.
		function getAllActiveEquipmentCards()
		{
				$sql = "SELECT card_id FROM `equipmentCards` ";
				$sql .= "WHERE equipment_is_active=1 ";

				return self::getObjectListFromDB( $sql );
		}

		// Get the equipment cards in the player board that are actively targeting a player.
		function getAllPlayerBoardActiveEquipmentCards()
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE card_location='active' ";

				return self::getObjectListFromDB( $sql );
		}

		function getAllEquipmentCardsInHand()
		{
				$sql = "SELECT card_id FROM `equipmentCards` ";
				$sql .= "WHERE card_location='hand' ";

				return self::getObjectListFromDB( $sql );
		}

		function getPlayerActiveEquipmentCards($askingPlayer)
		{
				$playerEquipmentCards = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

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
								$equipmentOwner = $this->getEquipmentCardOwner($playerId);
								$playerEquipmentCards[$id] = array( 'player_id' => $playerId, 'playerLetterOrder' => $playerLetterOrder, 'equipmentCardIds' => $id, 'collectorNumber' => $collectorNumber, 'equipmentName' => $equipmentName, 'equipmentEffect' => $equipmentEffect, 'equipmentOwner' => $equipmentOwner ); // save the count of equipment cards to the 2D array we will be returning
						}
				}

				return $playerEquipmentCards;
		}

		function getEquipmentCardCountsOpponentsOf($askingPlayer)
		{
				$opponentEquipmentCards = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player

						//if($playerId != $askingPlayer)
						//{ // skip the asking player because we have another process for getting their equipment

								$playerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of this player from the player asking's perspective
								$equipmentCardIds = $this->getEquipmentCardIdsForPlayer($playerId);

								//var_dump( $equipmentCardIds );
								//die('ok');
								foreach($equipmentCardIds as $id)
								{
										$collectorNumber = $this->getCollectorNumberFromId($id);
										$equipName = $this->getTranslatedEquipmentName($collectorNumber);
										$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);
										$opponentEquipmentCards[$playerId] = array( 'player_id' => $playerId, 'playerLetterOrder' => $playerLetterOrder, 'equipmentCardIds' => $id, 'collectorNumber' => $collectorNumber, 'equipName' => $equipName, 'equipEffect' => $equipEffect ); // save the count of equipment cards to the 2D array we will be returning
								}
						//}
				}

				return $opponentEquipmentCards;
		}

		function getEffectFromDieValue($dieId, $value)
		{
				if($dieId == 4)
				{ // infection die
						if($value == 0 || $value == 1)
						{
								return clienttranslate( 'Add Infection Token' );
						}
						else
						{
								return clienttranslate( 'No Effect' );
						}
				}
				else
				{ // zombie die
						switch($value)
						{
								case 6: // turn into a zombie
									return clienttranslate( 'Turn Into Zombie' );
								break;
								case 7: // biter re-aims arms
								case 8: // biter re-aims arms
										return clienttranslate( 'Zombie Re-Aims' );
								break;
								case 9: // blank
										return clienttranslate( 'No Effect' );
								break;
								case 10: // add extra infection token
								case 11: // add extra infection token
										return clienttranslate( 'Add Extra Infection Token' );
								break;
						}
				}
		}

		function getInfectionDiceRolled()
		{
				$sql = "SELECT * FROM dice WHERE die_id=4 AND roller_player_id<>'' ";

				return self::getCollectionFromDb( $sql );
		}

		function getZombieDiceRolled()
		{
				$sql = "SELECT * FROM dice WHERE die_id<>4 AND roller_player_id<>'' ORDER BY die_value ASC";

				return self::getCollectionFromDb( $sql );
		}

		function getGunsForPlayer($askingPlayer)
		{
				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);

						$sql = "SELECT gun_id, gun_type, gun_held_by, gun_aimed_at, pp.player_asking, pp.player_id playerIdHeldBy, pp.player_position letterPositionHeldBy, pp2.player_id playerIdAimedAt, pp2.player_position letterPositionAimedAt ";
						$sql .= ", (SELECT player_name FROM player WHERE player_id=gun_held_by) heldByName, (SELECT player_color FROM player WHERE player_id=gun_held_by) heldByColor, (SELECT player_name FROM player WHERE player_id=gun_aimed_at) aimedAtName, (SELECT player_color FROM player WHERE player_id=gun_aimed_at) aimedAtColor ";
						$sql .= "FROM guns g ";
						$sql .= "LEFT JOIN `playerPositioning` pp ON (pp.player_id=g.gun_held_by AND pp.player_asking=$askingPlayer) ";
						$sql .= "LEFT JOIN `playerPositioning` pp2 ON (pp2.player_id=g.gun_aimed_at AND pp2.player_asking=$askingPlayer) ";
						return self::getCollectionFromDb( $sql );
				}
				else
				{ // this is NOT a spectator

						$sql = "SELECT gun_id, gun_type, gun_held_by, gun_aimed_at, pp.player_asking, pp.player_id playerIdHeldBy, pp.player_position letterPositionHeldBy, pp2.player_id playerIdAimedAt, pp2.player_position letterPositionAimedAt ";
						$sql .= ", (SELECT player_name FROM player WHERE player_id=gun_held_by) heldByName, (SELECT player_color FROM player WHERE player_id=gun_held_by) heldByColor, (SELECT player_name FROM player WHERE player_id=gun_aimed_at) aimedAtName, (SELECT player_color FROM player WHERE player_id=gun_aimed_at) aimedAtColor ";
						$sql .= "FROM guns g ";
						$sql .= "LEFT JOIN `playerPositioning` pp ON (pp.player_id=g.gun_held_by AND pp.player_asking=$askingPlayer) ";
						$sql .= "LEFT JOIN `playerPositioning` pp2 ON (pp2.player_id=g.gun_aimed_at AND pp2.player_asking=$askingPlayer) ";

						return self::getCollectionFromDb( $sql );
				}
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

		function getIsGunPointingLeftForAll()
		{
				// because it will be faster than querying a database table, create a 2D array to hold whether a gun is pointed left or right based on where the holder is sitting and where it is aimed
				$isLeftArray = array();
				$isLeftArray['a'] = array( 'a' => 0, 'b' => 1, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 0);
				$isLeftArray['b'] = array( 'a' => 0, 'b' => 1, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0);
				$isLeftArray['c'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 1);
				$isLeftArray['d'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 1);
				$isLeftArray['e'] = array( 'a' => 0, 'b' => 0, 'c' => 1, 'd' => 1, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0);
				$isLeftArray['f'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 1, 'e' => 1, 'f' => 0, 'g' => 1, 'h' => 1);
				$isLeftArray['g'] = array( 'a' => 0, 'b' => 1, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 0);
				$isLeftArray['h'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 1, 'f' => 0, 'g' => 1, 'h' => 1);

				return $isLeftArray;
		}

		function getGunRotationsForAll()
		{
				$rotationArray = array();

				$players = $this->getPlayersDeets();
				$numberOfPlayers = count($players);

				if($numberOfPlayers < 7)
				{ // smaller board layout
					//throw new feException( $numberOfPlayers);
						// because it will be faster than querying a database table, create a 2D array to hold how much a gun should be rotated based on where it is aimed
						$rotationArray['a'] = array( 'a' => 90, 'b' => 30, 'c' => -50, 'd' => -15, 'e' => -90, 'f' => -20, 'g' => -160, 'h' => 25); // 4 plus
						$rotationArray['b'] = array( 'a' => 70, 'b' => 0, 'c' => -30, 'd' => 15, 'e' => -70, 'f' => 0, 'g' => 105, 'h' => 35); // 4 plus
						$rotationArray['c'] = array( 'a' => -60, 'b' => -15, 'c' => 90, 'd' => 65, 'e' => 210, 'f' => 45, 'g' => 160, 'h' => -90); // 4 plus
						$rotationArray['d'] = array( 'a' => -30, 'b' => 15, 'c' => 75, 'd' => 0, 'e' => 230, 'f' => -45, 'g' => 180, 'h' => 105); // 8 plus
						$rotationArray['e'] = array( 'a' => 90, 'b' => 160, 'c' => 150, 'd' => -15, 'e' => -90, 'f' => 25, 'g' => -160, 'h' => 50); // 5 plus
						$rotationArray['f'] = array( 'a' => -30, 'b' => 0, 'c' => 60, 'd' => -115, 'e' => 30, 'f' => 0, 'g' => 180, 'h' => -75); // 4 plus
						$rotationArray['g'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => 0, 'h' => 15); // 7 plus
						$rotationArray['h'] = array( 'a' => -30, 'b' => 20, 'c' => 90, 'd' => -15, 'e' => 50, 'f' => -45, 'g' => -160, 'h' => 15); // 6 plus
				}
				else
				{ // larger board layout
						// because it will be faster than querying a database table, create a 2D array to hold how much a gun should be rotated based on where it is aimed
						$rotationArray['a'] = array( 'a' => 90, 'b' => 60, 'c' => -65, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => 20, 'h' => 25);
						$rotationArray['b'] = array( 'a' => 80, 'b' => 0, 'c' => -30, 'd' => 25, 'e' => -70, 'f' => 0, 'g' => 105, 'h' => 55);
						$rotationArray['c'] = array( 'a' => -65, 'b' => -15, 'c' => 90, 'd' => 65, 'e' => 200, 'f' => 45, 'g' => -45, 'h' => -90);
						$rotationArray['d'] = array( 'a' => -30, 'b' => 25, 'c' => 75, 'd' => 0, 'e' => 230, 'f' => -65, 'g' => 0, 'h' => -70);
						$rotationArray['e'] = array( 'a' => 90, 'b' => 150, 'c' => 150, 'd' => -140, 'e' => -90, 'f' => 20, 'g' => 115, 'h' => 70);
						$rotationArray['f'] = array( 'a' => -50, 'b' => 0, 'c' => 60, 'd' => -115, 'e' => 30, 'f' => 0, 'g' => -20, 'h' => -75);
						$rotationArray['g'] = array( 'a' => 65, 'b' => 70, 'c' => -50, 'd' => 0, 'e' => -70, 'f' => -30, 'g' => 0, 'h' => 30);
						$rotationArray['h'] = array( 'a' => -30, 'b' => 40, 'c' => 90, 'd' => -25, 'e' => 65, 'f' => -60, 'g' => 15, 'h' => -90);
				}

				return $rotationArray;
		}

		function getGunRotationsForPlayer($askingPlayer)
		{
				$rotationArray = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

				$guns = $this->getGunsForPlayer($askingPlayer);
				foreach( $guns as $gun )
				{
						$gunId = $gun['gun_id']; // internal id
						$gunType = $gun['gun_type'];
						$gunHolderLetter = $gun['letterPositionHeldBy'];
						$aimedAtLetter = $gun['letterPositionAimedAt'];
//throw new feException( "gun holder letter $gunHolderLetter and aimed at letter $aimedAtLetter");
						$rotation = $this->getGunRotationFromLetters($gunHolderLetter, $aimedAtLetter); // find the rotation for this gun
						$isPointingLeft = $this->getIsGunPointingLeft($gunHolderLetter, $aimedAtLetter); // decide if we use the gun that points left or right

						$rotationArray[$gunId] = array( 'gun_id' => $gunId, 'gun_type' => $gunType, 'rotation' => $rotation, 'is_pointing_left' => $isPointingLeft); // save this rotation to the array
				}

				return $rotationArray;
		}

		// OBSOLETE
		function getGunRotationFromLetters($gunHolderLetter, $aimedAtLetter)
		{
				if(is_null($gunHolderLetter) || is_null($aimedAtLetter))
				{ // either the gun is not being held by a player or it is not aimed
						return 0;
				}

				$rotationArray = array();

				$players = $this->getPlayersDeets();
				$numberOfPlayers = count($players);


				if($numberOfPlayers < 7)
				{ // smaller board layout
					//throw new feException( $numberOfPlayers);
						// because it will be faster than querying a database table, create a 2D array to hold how much a gun should be rotated based on where it is aimed
						$rotationArray['a'] = array( 'a' => 90, 'b' => 30, 'c' => -50, 'd' => -15, 'e' => -90, 'f' => -20, 'g' => -160, 'h' => 25); // 4 plus
						$rotationArray['b'] = array( 'a' => 70, 'b' => 0, 'c' => -30, 'd' => 15, 'e' => -70, 'f' => 0, 'g' => 105, 'h' => 35); // 4 plus
						$rotationArray['c'] = array( 'a' => -60, 'b' => -15, 'c' => 90, 'd' => 65, 'e' => 210, 'f' => 45, 'g' => 160, 'h' => -90); // 4 plus
						$rotationArray['d'] = array( 'a' => -30, 'b' => 15, 'c' => 75, 'd' => 0, 'e' => 230, 'f' => -45, 'g' => 180, 'h' => 105); // 8 plus
						$rotationArray['e'] = array( 'a' => 90, 'b' => 160, 'c' => 150, 'd' => -15, 'e' => -90, 'f' => 25, 'g' => -160, 'h' => 50); // 5 plus
						$rotationArray['f'] = array( 'a' => -30, 'b' => 0, 'c' => 60, 'd' => -115, 'e' => 30, 'f' => 0, 'g' => 180, 'h' => -75); // 4 plus
						$rotationArray['g'] = array( 'a' => 90, 'b' => -110, 'c' => -60, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => -160, 'h' => 15); // 7 plus
						$rotationArray['h'] = array( 'a' => -30, 'b' => 20, 'c' => 90, 'd' => -15, 'e' => 50, 'f' => -45, 'g' => -160, 'h' => 15); // 6 plus
				}
				else
				{ // larger board layout
						// because it will be faster than querying a database table, create a 2D array to hold how much a gun should be rotated based on where it is aimed
						$rotationArray['a'] = array( 'a' => 90, 'b' => 60, 'c' => -65, 'd' => -15, 'e' => -90, 'f' => -45, 'g' => 20, 'h' => 25);
						$rotationArray['b'] = array( 'a' => 80, 'b' => 0, 'c' => -30, 'd' => 25, 'e' => -70, 'f' => 0, 'g' => 105, 'h' => 55);
						$rotationArray['c'] = array( 'a' => -65, 'b' => -15, 'c' => 90, 'd' => 65, 'e' => 200, 'f' => 45, 'g' => -45, 'h' => -90);
						$rotationArray['d'] = array( 'a' => -30, 'b' => 25, 'c' => 75, 'd' => 0, 'e' => 230, 'f' => -65, 'g' => 0, 'h' => -70);
						$rotationArray['e'] = array( 'a' => 90, 'b' => 150, 'c' => 150, 'd' => -140, 'e' => -90, 'f' => 20, 'g' => 115, 'h' => 70);
						$rotationArray['f'] = array( 'a' => -50, 'b' => 0, 'c' => 60, 'd' => -115, 'e' => 30, 'f' => 0, 'g' => -20, 'h' => -75);
						$rotationArray['g'] = array( 'a' => 65, 'b' => 70, 'c' => -50, 'd' => 0, 'e' => -70, 'f' => -30, 'g' => -160, 'h' => 30);
						$rotationArray['h'] = array( 'a' => -30, 'b' => 40, 'c' => 90, 'd' => -25, 'e' => 65, 'f' => -60, 'g' => 15, 'h' => -90);
				}

				return $rotationArray[$gunHolderLetter][$aimedAtLetter];
		}

		// OBSOLETE
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
				$isLeftArray['c'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 1);
				$isLeftArray['d'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 1);
				$isLeftArray['e'] = array( 'a' => 0, 'b' => 0, 'c' => 1, 'd' => 1, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0);
				$isLeftArray['f'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 1, 'e' => 1, 'f' => 0, 'g' => 1, 'h' => 1);
				$isLeftArray['g'] = array( 'a' => 0, 'b' => 1, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 1, 'h' => 0);
				$isLeftArray['h'] = array( 'a' => 1, 'b' => 1, 'c' => 1, 'd' => 0, 'e' => 1, 'f' => 0, 'g' => 1, 'h' => 1);

				return $isLeftArray[$gunHolderLetter][$aimedAtLetter];
		}

		function getPlayerColorsByName()
		{
				$playerColors = array();

				$players = $this->getPlayersDeets();
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player
						$playerName = $player['player_name'];
						$playerColor = $player['player_color'];

						$playerColors[$playerName] = $playerColor;
				}

				return $playerColors;
		}

		function getEquipmentEffects()
		{
				$equipmentEffects = array();

				$equipmentEffects['Coffee'] = array( 'effect' => $this->getTranslatedEquipmentEffect(2));

				$equipmentEffects['Planted Evidence'] = array( 'effect' => $this->getTranslatedEquipmentEffect(8));

				$equipmentEffects['Smoke Grenade'] = array( 'effect' => $this->getTranslatedEquipmentEffect(12));

				$equipmentEffects['Truth Serum'] = array( 'effect' => $this->getTranslatedEquipmentEffect(15));

				$equipmentEffects['Wiretap'] = array( 'effect' => $this->getTranslatedEquipmentEffect(16));

				$equipmentEffects['Riot Shield'] = array( 'effect' => $this->getTranslatedEquipmentEffect(44));

				$equipmentEffects['Restraining Order'] = array( 'effect' => $this->getTranslatedEquipmentEffect(11));

				$equipmentEffects['Mobile Detonator'] = array( 'effect' => $this->getTranslatedEquipmentEffect(37));

				$equipmentEffects['Evidence Bag'] = array( 'effect' => $this->getTranslatedEquipmentEffect(4));

				$equipmentEffects['Med Kit'] = array( 'effect' => $this->getTranslatedEquipmentEffect(35));

				$equipmentEffects['Taser'] = array( 'effect' => $this->getTranslatedEquipmentEffect(14));

				$equipmentEffects['Defibrillator'] = array( 'effect' => $this->getTranslatedEquipmentEffect(3));

				$equipmentEffects['Blackmail'] = array( 'effect' => $this->getTranslatedEquipmentEffect(1));

				$equipmentEffects['Disguise'] = array( 'effect' => $this->getTranslatedEquipmentEffect(30));

				$equipmentEffects['Walkie Talkie'] = array( 'effect' => $this->getTranslatedEquipmentEffect(45));

				$equipmentEffects['Polygraph'] = array( 'effect' => $this->getTranslatedEquipmentEffect(9));

				$equipmentEffects['Surveillance Camera'] = array( 'effect' => $this->getTranslatedEquipmentEffect(13));

				$equipmentEffects['Metal Detector'] = array( 'effect' => $this->getTranslatedEquipmentEffect(7));

				$equipmentEffects['Deliriant'] = array( 'effect' => $this->getTranslatedEquipmentEffect(17));

				$equipmentEffects['K-9 Unit'] = array( 'effect' => $this->getTranslatedEquipmentEffect(6));



				$equipmentEffects['Crossbow'] = array( 'effect' => $this->getTranslatedEquipmentEffect(60));
				$equipmentEffects['Transfusion Tube'] = array( 'effect' => $this->getTranslatedEquipmentEffect(61));
				$equipmentEffects['Zombie Serum'] = array( 'effect' => $this->getTranslatedEquipmentEffect(62));
				$equipmentEffects['Flamethrower'] = array( 'effect' => $this->getTranslatedEquipmentEffect(63));
				$equipmentEffects['Chainsaw'] = array( 'effect' => $this->getTranslatedEquipmentEffect(64));
				$equipmentEffects['Zombie Mask'] = array( 'effect' => $this->getTranslatedEquipmentEffect(65));
				$equipmentEffects['Machete'] = array( 'effect' => $this->getTranslatedEquipmentEffect(66));
				$equipmentEffects['Weapon Crate'] = array( 'effect' => $this->getTranslatedEquipmentEffect(67));
				$equipmentEffects['Alarm Clock'] = array( 'effect' => $this->getTranslatedEquipmentEffect(68));

				return $equipmentEffects;
		}

		function getEliminatedPlayers($askingPlayer)
		{
				$eliminatedPlayers = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player
						$isEliminated = $player['is_eliminated']; // 1 if player is wounded

						if($isEliminated == 1)
						{ // this player is eliminated

								$eliminatedPlayerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of the wounded player from the player asking's perspective

								$eliminatedPlayers[$playerId] = array( 'playerId' => $playerId, 'playerLetter' => $eliminatedPlayerLetterOrder); // save the eliminated player info to the 2D array we will be returning
						}
				}

				return $eliminatedPlayers;
		}

		function getAllZombies()
		{
				$sql = "SELECT * FROM `player` ";
				$sql .= "WHERE is_zombie=1 ";

				return self::getCollectionFromDb( $sql );
		}

		function getAllNonInfectorZombies()
		{
				$allNonInfectorZombies = array();
				$allZombies = $this->getAllZombies();
				foreach($allZombies as $zombie)
				{
						$zombiePlayerId = $zombie['player_id'];
						if(!$this->isLeaderOrInfectorPlayer($zombiePlayerId))
						{ // not the Infector
								array_push($allNonInfectorZombies, $zombie); // add this zombie to the array
						}
				}

				return $allNonInfectorZombies;
		}

		function getZombiesWithLetterOrder($askingPlayer)
		{
				$zombiePlayers = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player
						$isZombie = $player['is_zombie']; // 1 if player is a zombie

						if($isZombie == 1)
						{ // this player is eliminated

								$zombiePlayerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of the wounded player from the player asking's perspective

								$zombiePlayers[$playerId] = array( 'playerId' => $playerId, 'playerLetter' => $zombiePlayerLetterOrder); // save the eliminated player info to the 2D array we will be returning
						}
				}

				return $zombiePlayers;
		}

		// The ASKING PLAYER wants to know where INFECTION TOKENS should be placed.
		function getInfectionTokensForPlayer($askingPlayer)
		{
				$infectionTokens = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player

						$integrityCards = $this->getIntegrityCardsForPlayer($playerId); // get the player's integrity cards

						foreach( $integrityCards as $integrityCard )
						{
								$card_id = $integrityCard['card_id'];
								$card_position = $integrityCard['card_location_arg'];
								$has_infection = $integrityCard['has_infection'];

								if($has_infection > 0)
								{ // this card has an infection token on it
										$infectedPlayerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of the infected player from the player asking's perspective
										$infectionTokens[$card_id] = array( 'player_id' => $playerId, 'infectedPlayerLetterOrder' => $infectedPlayerLetterOrder, 'cardPosition' => $card_position); // save the wounded token info to the 2D array we will be returning
								}
						}
				}

				return $infectionTokens;
		}

		function getDice()
		{
				return self::getObjectListFromDB( "SELECT * FROM dice");
		}

		// The ASKING PLAYER wants to know where WOUNDED TOKENS should be placed.
		function getWoundedTokensForPlayer($askingPlayer)
		{
				$woundedTokens = array();

				if(self::isSpectator())
				{ // this is a spectator
						$askingPlayer = $this->getPlayerIdFromPlayerNo(1);
				}

				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id']; // the ID of this player

						$isWounded = $this->isPlayerWounded($playerId); // 1 if player is wounded

						if($isWounded)
						{ // this player is wounded

								$woundedPlayerLetterOrder = $this->getLetterOrderFromPlayerIds($askingPlayer, $playerId); // find the letter of the wounded player from the player asking's perspective
								$leaderCardPosition = $this->getLeaderCardPositionFromPlayer($playerId); // the integrity card position of the leader card (1, 2, 3)
								$cardType = $this->getCardTypeFromPlayerIdAndPosition($playerId, $leaderCardPosition); // agent or kingpin

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

		public function skipUnplayableReactions()
		{
				return true;
		}

		public function skipInvestigateReactions()
		{
				return true;
		}

		function getCardIdFromPlayerAndPosition($playerId, $positionId)
		{
				return self::getUniqueValueFromDb("SELECT card_id FROM integrityCards WHERE card_location=$playerId AND card_location_arg=$positionId LIMIT 1");
		}

		function getCardTypeFromCardId($cardId)
		{
				return self::getUniqueValueFromDb("SELECT card_type FROM integrityCards WHERE card_id=$cardId LIMIT 1");
		}

		function getInfectorCardId()
		{
				return self::getUniqueValueFromDb("SELECT card_id FROM integrityCards WHERE card_type='infector' LIMIT 1");
		}

		function getCardTypeFromPlayerIdAndPosition($playerId, $positionId)
		{
				return self::getUniqueValueFromDb("SELECT card_type FROM integrityCards WHERE card_location=$playerId AND card_location_arg=$positionId LIMIT 1");
		}

		function getIntegrityCardOwner($integrityCardId)
		{
				return self::getUniqueValueFromDb("SELECT card_location FROM integrityCards WHERE card_id=$integrityCardId LIMIT 1");
		}

		function getIntegrityCardPosition($integrityCardId)
		{
				return self::getUniqueValueFromDb("SELECT card_location_arg FROM integrityCards WHERE card_id=$integrityCardId LIMIT 1");
		}

		function getIntegrityCardFlippedState($integrityCardId)
		{
				if(is_null($integrityCardId) || $integrityCardId == '')
				{
						return 1;
				}

				return self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_id=$integrityCardId LIMIT 1");
		}

		// If this CARD_ID is REVEALED, return 1, otherwise return 0.
		function getCardRevealedStatus($card_id)
		{
				if(is_null($card_id) || $card_id == '')
				{
					return 1;
				}

				return self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_id=$card_id LIMIT 1");
		}

		// Get the player number (1, 2, 3, 4, 5, 6, 7, 8) from the player ID (1234567).
		function getPlayerNumberFromPlayerId($playerId)
		{
				return self::getUniqueValueFromDb("SELECT player_no FROM player WHERE player_id=$playerId LIMIT 1");
		}

		// Get the player ID (1234567) from the player number (1, 2, 3, 4, 5, 6, 7, 8).
		function getPlayerIdFromPlayerNo($nextTurnOrderPosition)
		{
				return self::getUniqueValueFromDb("SELECT player_id FROM player WHERE player_no=$nextTurnOrderPosition LIMIT 1");
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

		function isAffectedByPlantedEvidence($cardId)
		{
				$integrityCardOwner = $this->getIntegrityCardOwner($cardId);
				$hasPlantedEvidence = $this->hasPlantedEvidence($integrityCardOwner);

				//TODO: if we add Tainted Evidence, we could return false if both Planted and Tainted are on this card

				return $hasPlantedEvidence;
		}

		function isAffectedByDisguise($cardId)
		{
				$integrityCardOwner = $this->getIntegrityCardOwner($cardId);
				$hasDisguise = $this->isPlayerDisguised($integrityCardOwner);
				$isHidden = $this->isIntegrityCardHidden($cardId);
				if($hasDisguise && $isHidden)
				{ // this player is disguised and this card is hidden
						return true;
				}
				return false;
		}

		function isAffectedBySurveillanceCamera($cardId)
		{
				$integrityCardOwner = $this->getIntegrityCardOwner($cardId);
				$hasSurveillanceCamera = $this->hasSurveillanceCamera($integrityCardOwner);

				return $hasSurveillanceCamera;
		}

		function isIntegrityCardInfected($cardId)
		{
				$value = self::getUniqueValueFromDb("SELECT has_infection FROM integrityCards WHERE card_id=$cardId LIMIT 1");
				if($value == 0)
				{ // this card is not infected
						return false;
				}
				else
				{ // this card is infected
						return true;
				}
		}

		function isIntegrityCardHidden($cardId)
		{
				if(is_null($cardId) || $cardId == '')
				{
						return 0;
				}

				$hiddenValue = self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_id=$cardId LIMIT 1");
				if($hiddenValue == 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function isInfectorHidden()
		{
				$hiddenValue = self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_type='infector' LIMIT 1");
				if($hiddenValue == 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function isKingpinHidden()
		{
				$hiddenValue = self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_type='kingpin' LIMIT 1");
				if($hiddenValue == 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function isAgentHidden()
		{
				$hiddenValue = self::getUniqueValueFromDb("SELECT card_type_arg FROM integrityCards WHERE card_type='agent' LIMIT 1");
				if($hiddenValue == 0)
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function getPlayerLetterOrderingArray($numberOfPlayers)
		{
				$orderingArray = array('a','b','c','f'); // 4-players

				switch($numberOfPlayers)
				{
						case 4:
							$orderingArray = array('a','b','c','f'); // 4-players
						break;
						case 5:
							$orderingArray = array('a','b','e','c','f'); // 5-players
						break;
						case 6:
							$orderingArray = array('a','b','e','c','f','h'); // 6-players
						break;
						case 7:
							$orderingArray = array('a','g','b','e','c','f','h'); // 7-players
						break;
						case 8:
							$orderingArray = array('a','g','b','e','c','f','d','h'); // 8-players
						break;
				}

				return $orderingArray;
		}

		function getLetterOrderPosition($askingPlayerOrder, $otherPlayerOrder, $numberOfPlayers)
		{
				$orderingArray = $this->getPlayerLetterOrderingArray($numberOfPlayers);
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
				return self::getUniqueValueFromDb("SELECT player_position FROM playerPositioning WHERE player_asking=$askingPlayer AND player_id=$targetPlayer LIMIT 1");
		}

		function getPlayerIdFromLetterOrder($playerAsking, $letterOrder)
		{
				$sql = "SELECT p.player_id FROM `playerPositioning` pp ";
				$sql .= "JOIN `player` p ON p.player_id=pp.player_id ";
				$sql .= "WHERE pp.player_asking=$playerAsking AND pp.player_position='$letterOrder' LIMIT 1";

				return self::getUniqueValueFromDb($sql);
		}

		function getPlayerNameFromLetterOrder($playerAsking, $letterOrder)
		{
				$sql = "SELECT p.player_name FROM `playerPositioning` pp ";
				$sql .= "JOIN `player` p ON p.player_id=pp.player_id ";
				$sql .= "WHERE pp.player_asking=$playerAsking AND pp.player_position='$letterOrder' LIMIT 1";

				return self::getUniqueValueFromDb($sql);
		}

		// Convert a player ID into a player NAME.
		function getPlayerNameFromPlayerId($playerId)
		{
				if(is_null($playerId) || $playerId == '')
				{
						return '';
				}

				$sql = "SELECT player_name FROM `player` ";
				$sql .= "WHERE player_id=$playerId LIMIT 1";

				if(is_null($playerId) || $playerId == '')
				{
						return 10;
				}

				$name = self::getUniqueValueFromDb($sql);

				return $name;
		}

		function getCurrPlayerName()
		{
				return $this->getPlayerNameFromPlayerId($this->getGameStateValue("CURRENT_PLAYER"));
		}

		function getNextPlayerName()
		{
				$next_player_id = $this->getPlayerAfter($this->getGameStateValue("CURRENT_PLAYER"));

				if(!$this->isTurnOrderClockwise())
				{ // we are going COUNTER-clockwise
						$next_player_id = $this->getPlayerBefore($this->getGameStateValue("CURRENT_PLAYER"));
				}

				return $this->getPlayerNameFromPlayerId($next_player_id);
		}

		function getPlayerColorFromId($playerId)
		{
				$sql = "SELECT player_color FROM `player` ";
				$sql .= "WHERE player_id=$playerId LIMIT 1";

				$color = self::getUniqueValueFromDb($sql);

				return $color;
		}

		function isLeaderOrInfectorCard($integrityCardId)
		{
				if($this->getCardTypeFromCardId($integrityCardId) == 'kingpin' ||
				$this->getCardTypeFromCardId($integrityCardId) == 'agent' ||
				$this->getCardTypeFromCardId($integrityCardId) == 'infector')
				{
						return true;
				}
				else
				{
						return false;
				}
		}

		function isLeaderOrInfectorPlayer($playerId)
		{
				if($this->getInfectorPlayerId() == $playerId)
				{ // this is the infector
						return true;
				}
				else
				{
						return false;
				}
		}

		function isIntegrityCardALeader($integrityCardOwner, $cardPosition)
		{
				$isLeader = false;

				$sql = "SELECT card_type FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$integrityCardOwner AND ic.card_location_arg=$cardPosition LIMIT 1";

				$cardType = self::getUniqueValueFromDb($sql);

				if($cardType == 'kingpin' || $cardType == 'agent')
				{
						$isLeader = true;
				}

				return $isLeader;
		}

		function getKingpinPlayerId()
		{
				$sql = "SELECT card_location FROM `integrityCards` ";
				$sql .= "WHERE card_type='kingpin' LIMIT 1";

				return self::getUniqueValueFromDb($sql);
		}

		function getAgentPlayerId()
		{
				$sql = "SELECT card_location FROM `integrityCards` ";
				$sql .= "WHERE card_type='agent' LIMIT 1";

				return self::getUniqueValueFromDb($sql);
		}

		function getInfectorPlayerId()
		{
				$sql = "SELECT card_location FROM `integrityCards` ";
				$sql .= "WHERE card_type='infector' LIMIT 1";

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
								if(!$this->isPlayerEliminated($gunTarget) && $gunTarget != $playerAsking)
								{ // the gun target is NOT eliminated and they are NOT aimed at themself
										return true;
								}
						}
				}

				return false; // we're not holding a gun or our gun is not aimed or we're aimed at ourself or we're aimed at an eliminated player
		}

		function canPlayerBite($playerAsking)
		{
				if($this->isPlayerHoldingGun($playerAsking))
				{ // they are holding arms
						$gunId = $this->getGunIdHeldByPlayer($playerAsking);
						$gunTarget = $this->getPlayerIdOfGunTarget($gunId);

						if(!is_null($gunTarget) && $gunTarget != '')
						{ // the arms are aimed at someone
								if(!$this->isPlayerEliminated($gunTarget) && $gunTarget != $playerAsking)
								{ // the gun target is NOT eliminated and they are NOT aimed at themself
										return true;
								}
						}
				}

				return false; // we're not holding arms or our arms are not aimed at someone than us or we're aimed at an eliminated player
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

		function countInfectionTokensForPlayer($playerId)
		{
				$infectionTokenCount = 0;

				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerId AND ic.has_infection=1 ";

				$integrityCards = self::getCollectionFromDb( $sql );
				$infectionTokenCount = count($integrityCards);


				return $infectionTokenCount;
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

		function shouldWeSkipEquipmentReactions($playerId)
		{
				$sql = "SELECT skip_equipment_reactions FROM `player` ";
				$sql .= "WHERE player_id=$playerId LIMIT 1";

				$skipInt = self::getUniqueValueFromDb($sql);

				if($skipInt == 1)
				{ // player wants to skip equipment reactions
						return true;
				}
				else
				{ // player does NOT want to skip equipment reactions
						return false;
				}
		}

		// Returns true if the player is ELIMINATED, false otherwise.
		function isPlayerEliminated($playerId)
		{
				$sql = "SELECT is_eliminated FROM `player` ";
				$sql .= "WHERE player_id=$playerId LIMIT 1";

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

		function isPlayerZombie($playerId)
		{
				$sql = "SELECT is_zombie FROM `player` ";
				$sql .= "WHERE player_id=$playerId LIMIT 1";

				$isZombieInt = self::getUniqueValueFromDb($sql);

				if($isZombieInt == 1)
				{ // player is a zombie
						return true;
				}
				else
				{ // player is NOT a zombie
						return false;
				}
		}

		function isPlayerNonInfectorZombie($playerId)
		{
				$nonInfectorZombiePlayers = $this->getAllNonInfectorZombies();
				foreach($nonInfectorZombiePlayers as $zombie)
				{
						$thisZombieId = $zombie['player_id'];
						if($thisZombieId == $playerId)
						{
								return true;
						}
				}

				return false;
		}

		function isPlayerIdToLeftOrRightOfPlayerId($player1, $player2)
		{
				$playerIdToLeft = $this->getPlayerClockwiseFrom($player1);
				$playerIdToRight = $this->getPlayerCounterClockwiseFrom($player1);

				//throw new feException( "Using player $player1 to the left is $playerIdToLeft and to the right is $playerIdToRight and the one asked about is $player2");

				if($playerIdToLeft == $player2 ||
				   $playerIdToRight == $player2)
  			{ // player2 is to the left or right of player1
					 	return true;
				}
				else
				{
						return false;
				}
		}

		function getPlayerClockwiseFrom($playerId)
		{
				$numberOfPlayers = count($this->getPlayersDeets()); // count players in game
				$currentTurnOrderPosition = $this->getPlayerNumberFromPlayerId($playerId);
				$nextTurnOrderPosition = $currentTurnOrderPosition + 1; // position of player to the left (clockwise)

				if($nextTurnOrderPosition > $numberOfPlayers)
				{ // go back to 1
						$nextTurnOrderPosition = 1;
				}

				$playerIdAtThisPosition = $this->getPlayerIdFromPlayerNo($nextTurnOrderPosition); // get the player ID to the left

				if($this->isPlayerEliminated($playerIdAtThisPosition))
				{ // skip eliminated players
						return $this->getPlayerClockwiseFrom($playerIdAtThisPosition); // go to next one
				}
				else
				{ // not eliminated player
						return $playerIdAtThisPosition;
				}
		}

		function getPlayerCounterClockwiseFrom($playerId)
		{
				$numberOfPlayers = count($this->getPlayersDeets()); // count players in game
				$currentTurnOrderPosition = $this->getPlayerNumberFromPlayerId($playerId);
				$nextTurnOrderPosition = $currentTurnOrderPosition - 1; // position of player to the right (counter-clockwise)

				if($nextTurnOrderPosition < 1)
				{ // go back to last player in turn order
						$nextTurnOrderPosition = $numberOfPlayers;
				}

				$playerIdAtThisPosition = $this->getPlayerIdFromPlayerNo($nextTurnOrderPosition); // get the player ID to the right

				if($this->isPlayerEliminated($playerIdAtThisPosition))
				{ // skip eliminated players
						return $this->getPlayerCounterClockwiseFrom($playerIdAtThisPosition); // go to next one
				}
				else
				{ // not eliminated player
						return $playerIdAtThisPosition;
				}
		}

		function getAllArmedZombies()
		{
				$armedZombies = array();
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying
						$isZombie = $player['is_zombie']; // if they are a zombie
						if($isZombie == 1 && $this->isPlayerHoldingGun($playerId))
						{ // they are a zombie and they are armed
								array_push($armedZombies, $player); // add this player to the array we are returning
						}
				}

				return $armedZombies;
		}

		function getArmedInfectedPlayers()
		{
				$armedInfectedPlayers = array();
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying
						if($this->isPlayerHoldingGun($playerId) && $this->countInfectionTokensForPlayer($playerId) > 0)
						{ // player is armed and has at least 1 infection token
								array_push($armedInfectedPlayers, $player); // add this player to the array we are returning
						}
				}

				return $armedInfectedPlayers;
		}

		function getUnarmedInfectedPlayers()
		{
				$unarmedInfectedPlayers = array();
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id']; // the ID of the player we're notifying
						if(!$this->isPlayerHoldingGun($playerId) && $this->countInfectionTokensForPlayer($playerId) > 0)
						{ // player is unarmed and has at least 1 infection token
								array_push($unarmedInfectedPlayers, $player); // add this player to the array we are returning
						}
				}

				return $unarmedInfectedPlayers;
		}

		function didNonZombiePlayerJustShootAZombie($playerId)
		{
			return false;
				// get all guns held by this player
				$sql = "SELECT * FROM `guns` ";
				$sql .= "WHERE gun_held_by=$playerId ";

				$gunsHeldByPlayer = self::getObjectListFromDB( $sql );

				foreach( $gunsHeldByPlayer as $gun )
				{ // go through each gun (should only be 1)

						$gunId = $gun['gun_id']; // get the PLAYER ID of the player holding this gun
						$gunFiredThisTurn = $gun['gun_fired_this_turn'];
						$gunAimedAt = $gun['gun_aimed_at'];
						$isTargetAZombie = $this->isPlayerZombie($gunAimedAt);
						$isShooterAZombie = $this->isPlayerZombie($playerId);

						if($gunFiredThisTurn && $isTargetAZombie && !$isShooterAZombie)
						{ // the non-zombie player just shot a zombie
								return true;
						}
						else
						{ // player did NOT just shoot a zombie
								return false;
						}
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

		function isPlayerHoldingPlayableEquipment($playerId)
		{
				$hasPlayableEquipment = false;
				$equipmentInHand = $this->getEquipmentInPlayerHand($playerId);

				foreach( $equipmentInHand as $equipmentCard )
				{ // go through each card (should only be 1)
						$equipmentId = $equipmentCard['card_id'];
						$equipmentName = $equipmentCard['equipment_name'];

						if($this->validateEquipmentUsage($equipmentId, $playerId, false))
						{ // we CAN use this now
								$hasPlayableEquipment = true;
						}
				}

				return $hasPlayableEquipment;
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

		// called when a player uses an equipment in reaction to a Shoot action. true if we can re-choose a new action
		// after the equipment is played. false is someone played a card that forces you to still shoot like restraining
		// order or riot shield.
		function canWeRechooseAction()
		{
				return false; // this is failing when Restraining Order is used when no player has equipment that can be used so let's just not allow this for now... it might be better this way anyway

				$activeEquipment = $this->getActiveEquipmentCards();

				foreach($activeEquipment as $equipment)
				{
						// 11=Restraining Order
						// 44=Riot Shield
						// 37=Mobile Detonator
						// 62=Zombie Serum
						$collectorNumber = $equipment['card_type_arg'];
						if($collectorNumber == 11 ||
						   $collectorNumber == 44 ||
							 $collectorNumber == 37 ||
							 $collectorNumber == 62)
							 {
								 return false;
							 }
				}

				return true;
		}

		function isInstantEquipment($collectorNumber)
		{
				switch($collectorNumber)
				{
						default:
								return false;
				}
		}

		function doneSelecting($equipmentId)
		{
				$allTargetsSelected = self::getUniqueValueFromDb("SELECT done_selecting FROM equipmentCards WHERE card_id=$equipmentId LIMIT 1");

				if($allTargetsSelected == 0)
				{
						return false;
				}
				else
				{
						return true;
				}
		}

		function isEquipmentActive($equipmentId)
		{
				if(!$equipmentId)
				{ // equipment ID is not there
						return false;
				}

				$activeValue = self::getUniqueValueFromDb("SELECT equipment_is_active FROM equipmentCards WHERE card_id=$equipmentId LIMIT 1");
				$locationValue = self::getUniqueValueFromDb("SELECT card_location FROM equipmentCards WHERE card_id=$equipmentId LIMIT 1");

				if($activeValue == 1 ||
				$locationValue == 'playing' ||
				$locationValue == 'active')
				{ // this is active or being played
						return true;
				}
				else
				{
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

		function getAllEquipmentInHands()
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE card_location='hand' ";

				return self::getObjectListFromDB( $sql );
		}

		function getPlayersActiveEquipment($playerAsking)
		{
				$sql = "SELECT * FROM `equipmentCards` ";
				$sql .= "WHERE card_location='active' AND equipment_owner=$playerAsking";

				return self::getObjectListFromDB( $sql );
		}

		function getLastPlayerInvestigated($playerId)
		{
				return self::getUniqueValueFromDb("SELECT last_player_investigated FROM player WHERE player_id=$playerId LIMIT 1");
		}

		function getLastCardPositionInvestigated($playerId)
		{
				return self::getUniqueValueFromDb("SELECT last_card_position_investigated FROM player WHERE player_id=$playerId LIMIT 1");
		}

		function getLastCardPositionRevealed($playerId)
		{
				return self::getUniqueValueFromDb("SELECT last_card_position_revealed FROM player WHERE player_id=$playerId LIMIT 1");
		}

		function getLeaderCardPositionFromPlayer($playerId)
		{
				return self::getUniqueValueFromDb("SELECT card_location_arg FROM integrityCards WHERE card_location=$playerId AND (card_type='agent' OR card_type='kingpin') LIMIT 1");
		}

		function getIntegrityCardPositionForNextInfectionToken($playerId)
		{
				// get this player's integrity cards without an infection token
				$sql = "SELECT * FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerId AND ic.has_infection=0 ";

				$integrityCards = self::getCollectionFromDb( $sql );

				foreach($integrityCards as $card)
				{ // go through each of this player's integrity cards
						return $card['card_location_arg']; // return the first one we come to
				}

				return 4; // if there are none, return 4, otherwise return the card position
		}

		function getCardListAsText($playerId)
		{
				$cardsAsText = "";

				$cards = $this->getIntegrityCardsForPlayer($playerId);
				$index = 0;
				foreach($cards as $card)
				{
						$cardId = $card['card_id'];
						$cardType = strtoupper($card['card_type']);

						$cardsAsText += $cardType;

						if($index < 2)
						{ // don't add to the last card
								$cardsAsText += " ";
						}

						$index++;
				}

				return $cardsAsText;
		}

		function convertCardPositionToText($cardPosition)
		{
				if($cardPosition == 1 || $cardPosition == '1')
				{
					return clienttranslate("LEFT");
				}
				elseif($cardPosition == 2 || $cardPosition == '2')
				{
					return clienttranslate("MIDDLE");
				}
				else
				{
					return clienttranslate("RIGHT");
				}
		}

		function convertCardTypeToText($cardType)
		{
				switch(strtolower($cardType))
				{
						case 'honest':
							return clienttranslate("HONEST");
						case 'crooked':
							return clienttranslate("CROOKED");
						case 'agent':
							return clienttranslate("AGENT");
						case 'kingpin':
							return clienttranslate("KINGPIN");
						case 'infector':
							return clienttranslate("INFECTOR");
				}

				return clienttranslate("UNKNOWN");
		}

		function getEquipmentCardIdInUse()
		{
				return self::getUniqueValueFromDb("SELECT card_id FROM equipmentCards WHERE card_location='playing' LIMIT 1 ");
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
		function isPlayerDisguised($playerId)
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
				if(is_null($equipmentId) || $equipmentId == '')
				{
						return "unknown";
				}

				return self::getUniqueValueFromDb("SELECT equipment_played_in_state FROM equipmentCards WHERE card_id=$equipmentId LIMIT 1 ");
		}

		function getEvidenceBagPlayedInState()
		{
				return self::getUniqueValueFromDb("SELECT equipment_played_in_state FROM equipmentCards WHERE card_type_arg=4 LIMIT 1 ");
		}

		function getEquipmentPlayedOnTurn($equipmentId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_played_on_turn FROM equipmentCards WHERE card_id=$equipmentId LIMIT 1 ");
		}

		function getCollectorNumberFromId($cardId)
		{
				return self::getUniqueValueFromDb("SELECT card_type_arg FROM equipmentCards WHERE card_id=$cardId LIMIT 1 ");
		}

		// THIS DOES NOT WORK WITH CARDS WITH QUANTITY HIGHER THAN 1 LIKE DEFIBBRILATOR
		function getEquipmentIdFromCollectorNumber($collectorNumber)
		{
				return self::getUniqueValueFromDb("SELECT card_id FROM equipmentCards WHERE card_type_arg=$collectorNumber LIMIT 1 ");
		}

		function getEquipmentCardOwner($cardId)
		{
				$equipmentOwner = self::getUniqueValueFromDb( "SELECT equipment_owner FROM equipmentCards
	                                                         WHERE card_id=$cardId LIMIT 1" );
				if(is_null($equipmentOwner) || $equipmentOwner == '')
				{ // for some reason, this query is returning empty when it shouldn't be and I don't know why
						return self::getUniqueValueFromDb( "SELECT card_location_arg FROM equipmentCards
			                                                         WHERE card_id=$cardId LIMIT 1" );
				}
				else
				{ // not empty so it's doing what it's supposed to be doing
						return $equipmentOwner;
				}
		}

		function getEquipmentCardLocation($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT card_location FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentName($cardId)
		{
				return self::getUniqueValueFromDb( "SELECT equipment_name FROM equipmentCards
	                                                         WHERE card_id=$cardId LIMIT 1" );
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

						case 67: // Weapon Crate
								return clienttranslate( 'Weapon Crate' );

						case 66: // Machete
								return clienttranslate( 'Machete' );

						case 68: // Alarm Clock
								return clienttranslate( 'Alarm Clock' );

						case 65: // Zombie Mask
								return clienttranslate( 'Zombie Mask' );

						case 63: // Flamethrower
								return clienttranslate( 'Flamethrower' );

						case 62: // Zombie Serum
								return clienttranslate( 'Zombie Serum' );

						case 61: // Transfusion Tube
								return clienttranslate( 'Transfusion Tube' );

						case 64: // Chainsaw
								return clienttranslate( 'Chainsaw' );

						case 60: // Crossbow
								return clienttranslate( 'Crossbow' );

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
							return clienttranslate( 'Use when someone shoots. They must still shoot but their target must choose someone to their left or right to be shot instead.' );

						case 11: // restraining order
							return clienttranslate( 'Use when someone shoots. They must still shoot but, before they do, they must aim at a different player.' );

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
								return clienttranslate( 'Choose a player. All armed players aim at them.' );

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

						case 67: // Weapon Crate
								return clienttranslate( 'Each player with at least one Infection Token may Arm and/or change their aim.' );

						case 66: // Machete
								return clienttranslate( 'Choose a zombie. Exchange any number of their Integrity cards for the same number of revealed Honest or Crooked cards.' );

						case 68: // Alarm Clock
								return clienttranslate( 'Choose a player. Give them an Infection Token and all zombies aim at them.' );

						case 65: // Zombie Mask
								return clienttranslate( 'Choose a revealed Leader or Infector card to exchange for another revealed Leader or Infector card.' );

						case 63: // Flamethrower
								return clienttranslate( 'All zombies are shot.' );

						case 62: // Zombie Serum
								return clienttranslate( 'Re-roll any Infection or Zombie Dice result.' );

						case 61: // Transfusion Tube
								return clienttranslate( 'Move up to 3 Infection Tokens to different Integrity cards.' );

						case 64: // Chainsaw
								return clienttranslate( 'Choose another player\'s hidden Integrity card to reveal. If it is a Leader, you are shot. Otherwise, they are shot.' );

						case 60: // Crossbow
								return clienttranslate( 'Choose another player with no revealed Leader card who is armed. That player is shot.' );

						default:
							return clienttranslate( 'Equipment' );

				}
		}

		function getExtraDescriptionTextForEquipment($collectorNumber)
		{
			switch($collectorNumber)
			{
					case 61: // Transfusion Tube
							return clienttranslate( 'Choose an Infection Token you want to move.' );

					case 66: // Machete
							return clienttranslate( 'Choose an Integrity Card of a Zombie.' );

					default:
						return '';

			}
		}

		function swapIntegrityCards($cardId1, $cardId2)
		{
				$oldOwnerOfCardId1 = $this->getIntegrityCardOwner($cardId1);
				$oldOwnerOfCardId2 = $this->getIntegrityCardOwner($cardId2);

				$player1BeforeCardType1 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId1,1));
				$player1BeforeCardType2 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId1,2));
				$player1BeforeCardType3 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId1,3));
				self::notifyPlayer( $oldOwnerOfCardId1, 'swappedCardList', clienttranslate( 'You had ${card_1} ${card_2} ${card_3}.' ), array(
														 'i18n' => array('card_1', 'card_2', 'card_3'),
														 'card_1' => $player1BeforeCardType1,
														 'card_2' => $player1BeforeCardType2,
														 'card_3' => $player1BeforeCardType3
				) );

				$player2BeforeCardType1 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId2,1));
				$player2BeforeCardType2 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId2,2));
				$player2BeforeCardType3 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId2,3));
				self::notifyPlayer( $oldOwnerOfCardId2, 'swappedCardList', clienttranslate( 'You had ${card_1} ${card_2} ${card_3}.' ), array(
															'i18n' => array('card_1', 'card_2', 'card_3'),
															'card_1' => $player2BeforeCardType1,
															'card_2' => $player2BeforeCardType2,
															'card_3' => $player2BeforeCardType3
				) );

				$oldPositionCardId1 = $this->getIntegrityCardPosition($cardId1); // 1, 2, 3
				$oldPositionCardId2 = $this->getIntegrityCardPosition($cardId2); // 1, 2, 3
				$oldHiddenStateCardId1 = $this->getIntegrityCardFlippedState($cardId1); // 1 if revealed
				$oldHiddenStateCardId2 = $this->getIntegrityCardFlippedState($cardId2); // 0 if hidden

				// give ownership to the new player for each card
				$sqlUpdate1 = "UPDATE integrityCards SET ";
				$sqlUpdate1 .= "card_location='$oldOwnerOfCardId2',card_location_arg=$oldPositionCardId2 WHERE ";
				$sqlUpdate1 .= "card_id=$cardId1";
				self::DbQuery( $sqlUpdate1 );

				$sqlUpdate2 = "UPDATE integrityCards SET ";
				$sqlUpdate2 .= "card_location='$oldOwnerOfCardId1',card_location_arg=$oldPositionCardId1 WHERE ";
				$sqlUpdate2 .= "card_id=$cardId2";
				self::DbQuery( $sqlUpdate2 );



				// set it to show that the original owner has seen it
				$sqlUpdate1Vis = "UPDATE playerCardVisibility SET ";
				$sqlUpdate1Vis .= "is_seen=1 WHERE ";
				$sqlUpdate1Vis .= "card_id=$cardId1 AND player_id=$oldOwnerOfCardId2";
				self::DbQuery( $sqlUpdate1Vis );

				$sqlUpdate2Vis = "UPDATE playerCardVisibility SET ";
				$sqlUpdate2Vis .= "is_seen=1 WHERE ";
				$sqlUpdate2Vis .= "card_id=$cardId2 AND player_id=$oldOwnerOfCardId1";
				self::DbQuery( $sqlUpdate2Vis );



				//throw new feException( "Cards Updated: $cardId1 and $cardId2");

				$card1NewOwnerName = $this->getPlayerNameFromPlayerId($oldOwnerOfCardId2);
				$card2NewOwnerName = $this->getPlayerNameFromPlayerId($oldOwnerOfCardId1);

				$card1OriginalPosition = $this->getIntegrityCardPosition($cardId2); // now that the database has been updated, we need to switch the 1 and the 2
				$card2OriginalPosition = $this->getIntegrityCardPosition($cardId1); // now that the database has been updated, we need to switch the 1 and the 2
				$card1IsHidden = $this->isIntegrityCardHidden($cardId2); // true if this card is hidden
				$card2IsHidden = $this->isIntegrityCardHidden($cardId1); // true if this card is hidden
				$card1Type = $this->getCardTypeFromCardId($cardId2);
				$card2Type = $this->getCardTypeFromCardId($cardId1);
				$card1PlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId2); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
				$card2PlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId1); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
				$seenListCard1 = $this->getArrayOfPlayersWhoHaveSeenCard($cardId2);
				$seenListCard2 = $this->getArrayOfPlayersWhoHaveSeenCard($cardId1);
				$card1Wounded = $this->isCardWounded($cardId2); // true if this has a wounded token on it
				$card2Wounded = $this->isCardWounded($cardId1); // true if this has a wounded token on it
				$card1Infected = $this->isCardInfected($cardId2); // true if this has a wounded token on it
				$card2Infected = $this->isCardInfected($cardId1); // true if this has a wounded token on it


				self::notifyAllPlayers( "integrityCardsExchanged", clienttranslate( '${player_name} ${card1PositionText} card has been exchanged with ${player_name_2} ${card2PositionText} card.' ), array(
							'i18n' => array( 'card1PositionText', 'card2PositionText' ),
							'player_name' => $card1NewOwnerName,
						  'player_name_2' => $card2NewOwnerName,
						  'card1OriginalPosition' => $card1OriginalPosition,
							'card2OriginalPosition' => $card2OriginalPosition,
							'playerId1' => $oldOwnerOfCardId1,
							'playerId2' => $oldOwnerOfCardId2,
							'card1IsHidden' => $card1IsHidden,
							'card2IsHidden' => $card2IsHidden,
							'card1Type' => $card1Type,
							'card2Type' => $card2Type,
							'card1PlayersSeen' => $card1PlayersSeen,
							'card2PlayersSeen' => $card2PlayersSeen,
							'card1SeenList' => $seenListCard1,
							'card2SeenList' => $seenListCard2,
							'card1Wounded' => $card1Wounded,
							'card2Wounded' => $card2Wounded,
							'card1Infected' => $card1Infected,
							'card2Infected' => $card2Infected,
							'card1PositionText' => $this->convertCardPositionToText($card2OriginalPosition),
							'card2PositionText' => $this->convertCardPositionToText($card1OriginalPosition),
							'card1affectedByPlantedEvidence' => $this->isAffectedByPlantedEvidence($cardId2),
							'card2affectedByPlantedEvidence' => $this->isAffectedByPlantedEvidence($cardId1),
							'card1affectedByDisguise' => $this->isAffectedByDisguise($cardId2),
							'card2affectedByDisguise' => $this->isAffectedByDisguise($cardId1),
							'card1affectedBySurveillanceCamera' => $this->isAffectedBySurveillanceCamera($cardId2),
							'card2affectedBySurveillanceCamera' => $this->isAffectedBySurveillanceCamera($cardId1)
				) );

				$player1AfterCardType1 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId1,1));
				$player1AfterCardType2 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId1,2));
				$player1AfterCardType3 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId1,3));
				self::notifyPlayer( $oldOwnerOfCardId1, 'swappedCardList', clienttranslate( 'You now have ${card_1} ${card_2} ${card_3}.' ), array(
														 'i18n' => array('card_1', 'card_2', 'card_3'),
														 'card_1' => $player1AfterCardType1,
														 'card_2' => $player1AfterCardType2,
														 'card_3' => $player1AfterCardType3
				) );

				$player2AfterCardType1 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId2,1));
				$player2AfterCardType2 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId2,2));
				$player2AfterCardType3 = $this->convertCardTypeToText($this->getCardTypeFromPlayerIdAndPosition($oldOwnerOfCardId2,3));
				self::notifyPlayer( $oldOwnerOfCardId2, 'swappedCardList', clienttranslate( 'You now have ${card_1} ${card_2} ${card_3}.' ), array(
															'i18n' => array('card_1', 'card_2', 'card_3'),
															'card_1' => $player2AfterCardType1,
															'card_2' => $player2AfterCardType2,
															'card_3' => $player2AfterCardType3
				) );


				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are using the zombies expansion


						// UNZOMBIFY NEW LEADERS
						if($this->isPlayerZombie($oldOwnerOfCardId1) && $this->isPlayerALeader($oldOwnerOfCardId1))
						{ // player is a Leader and they are a zombie
//throw new feException("oldOwnerOfCardId1 is zombie and oldOwnerOfCardId1 is leader");
								$this->revivePlayer($oldOwnerOfCardId1); // unzombify them
						}

						if($this->isPlayerZombie($oldOwnerOfCardId2) && $this->isPlayerALeader($oldOwnerOfCardId2))
						{ // player is a Leader and they are a zombie
//							throw new feException("oldOwnerOfCardId2 is zombie and oldOwnerOfCardId2 is leader");
								$this->revivePlayer($oldOwnerOfCardId2); // unzombify them
						}


						// ZOMBIFY NEW INFECTORS
						$infectorCardId = $this->getInfectorCardId(); // get the card Id of the infector card
						if($cardId1 == $infectorCardId || $cardId2 == $infectorCardId)
						{ // the infector card is swapped

								if(!$this->isPlayerZombie($oldOwnerOfCardId1) && $this->getPlayerRole($oldOwnerOfCardId1) == 'zombie_infector')
								{ // player is an Infector and NOT a zombie
		//throw new feException("oldOwnerOfCardId1 is not zombie and oldOwnerOfCardId1 is infector");
										$this->eliminatePlayer($oldOwnerOfCardId1); // zombify them
								}

								if(!$this->isPlayerZombie($oldOwnerOfCardId2) && $this->getPlayerRole($oldOwnerOfCardId2) == 'zombie_infector')
								{ // player is an Infector and NOT a zombie
		//throw new feException("oldOwnerOfCardId2 is not zombie and oldOwnerOfCardId2 is infector");
										$this->eliminatePlayer($oldOwnerOfCardId2); // zombify them
								}
						}
				}




				$soloWinner = $this->getSoloWinner();
				if($soloWinner)
				{ // someone got both leaders
						//$this->revealLeaderCards($soloWinner); // reveal the leader cards this player has
						$this->endGameCleanup('solo_win', $soloWinner);

						$this->gamestate->nextState( "endGame" );
				}
		}

		function getEquipmentTarget1($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_1 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentTarget2($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_2 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentTarget3($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_3 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentTarget4($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_4 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentTarget5($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_5 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentTarget6($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_6 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentTarget7($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_7 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getEquipmentTarget8($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT equipment_target_8 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getPlayerTarget1($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT player_target_1 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getPlayerTarget2($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT player_target_2 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getGunTarget1($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT gun_target_1 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
		}

		function getGunTarget2($equipmentCardId)
		{
				return self::getUniqueValueFromDb("SELECT gun_target_2 FROM equipmentCards WHERE card_id=$equipmentCardId LIMIT 1");
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

		function setDoneSelecting($equipmentId, $newValue)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "done_selecting=$newValue WHERE ";
				$sqlUpdate .= "card_id=$equipmentId";

				self::DbQuery( $sqlUpdate );
		}

		function resetEquipmentDeckAfterReshuffle()
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "discarded_by='' ";

				self::DbQuery( $sqlUpdate );
		}

		function resetEquipmentCardAfterCancel($equipmentId)
		{
				if($equipmentId != '')
				{
						$sqlUpdate = "UPDATE equipmentCards SET ";
						$sqlUpdate .= "card_location='hand', equipment_played_on_turn='', equipment_target_1='',equipment_target_2='',equipment_target_3='',equipment_target_4='',equipment_target_5='',equipment_target_6='',equipment_target_7='',equipment_target_8='',player_target_1='',player_target_2='',gun_target_1='',gun_target_2='',done_selecting=0 WHERE ";
						$sqlUpdate .= "card_id=$equipmentId";

						self::DbQuery( $sqlUpdate );

						$ownerOfEquipment = $this->getEquipmentCardOwner($equipmentId);

						self::notifyAllPlayers( "cancelEquipmentUse", '', array(
								 						 'equipment_id' => $equipmentId,
														 'player_id' => $ownerOfEquipment
						) );
				}
		}

		function setEquipmentCardOwner($cardId, $playerId)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "equipment_owner=$playerId,card_location_arg=$playerId WHERE ";
				$sqlUpdate .= "card_id=$cardId";

				self::DbQuery( $sqlUpdate );
		}

		function countPlayersWhoCanUseEquipment()
		{
				$activePlayers = 0;
				$checkedPlayers = 0;
				$players = $this->getPlayersDeets(); // get players
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id'];
						$isEliminated = $this->isPlayerEliminated($playerId); // true if this player is eliminated (might not be needed)
						$isHoldingEquipment = $this->isPlayerHoldingEquipment($playerId); // true if the player is holding equipment
						if($this->skipUnplayableReactions())
						{ // only make them active if they have an equipment that is usable now
								$isHoldingEquipment = $this->isPlayerHoldingPlayableEquipment($playerId); // true if the player is holding playable equipment
						}

						if(!$isHoldingEquipment || $isEliminated)
						{ // this player is not holding equipment or they are eliminated

						}
						else
						{ // this player is alive and holding equipment
								$activePlayers++; // just add to the count so we know how many players are active
						}

						$checkedPlayers++;
				}

				return $activePlayers;
		}

		// Set all players who are holding equipment cards to active.
		function setEquipmentHoldersToActive($nextGameState)
		{

				$this->gamestate->setAllPlayersMultiactive(); // set all players to active

				$activePlayers = 0;
				$checkedPlayers = 0;
				$players = $this->getPlayersDeets(); // get players
				foreach( $players as $player )
				{ // go through each player
						$playerId = $player['player_id'];
						$isEliminated = $this->isPlayerEliminated($playerId); // true if this player is eliminated (might not be needed)
						$isHoldingEquipment = $this->isPlayerHoldingEquipment($playerId); // true if the player is holding equipment
						if($this->skipUnplayableReactions())
						{ // only make them active if they have an equipment that is usable now
								$isHoldingEquipment = $this->isPlayerHoldingPlayableEquipment($playerId); // true if the player is holding playable equipment
						}

						if(!$isHoldingEquipment || $isEliminated)
						{ // this player is not holding equipment or they are eliminated
								if($activePlayers == 0 && $checkedPlayers == (count($players) - 1))
								{ // we are checking the last player and no one has been active yet

									// don't set the last player to inactive because then it will skip to an end of turn "allPassedOnReactions" state and that will lead to turns being skipped
								}
								else
								{
										$this->gamestate->setPlayerNonMultiactive( $playerId, "allPassedOnReactions" ); // just make them inactive
								}
						}
						else
						{ // this player is alive and holding equipment
								$activePlayers++; // just add to the count so we know how many players are active
						}

						$checkedPlayers++;
				}

				$this->gamestate->nextState( $nextGameState ); // set to the next game state



				if($activePlayers == 0)
				{ // no players can play equipment
						//$this->gamestate->setAllPlayersMultiactive(); // set all players to active otherwise it puts them in a bad state where no one can do anything

						$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
						$this->gamestate->changeActivePlayer($playerWhoseTurnItIs); // make player whose turn it is the only active player
//throw new feException("setEquipmentHoldersToActive sending to $nextGameState");
						switch($nextGameState)
						{
							case "askInvestigateReaction":
								$this->gamestate->nextState("executeActionInvestigate");
							break;
							case "endTurnReaction":
							//throw new feException("setEquipmentHoldersToActive sending to $nextGameState");
									$this->gamestate->nextState("allPassedOnReactions"); // this will take you to end of turn

							break;
							case "askBiteReaction":
									$this->gamestate->nextState("allPassedOnReactions"); // goes to executeActionBite
							break;
							case "askShootReaction":
//throw new feException("setEquipmentHoldersToActive sending to $nextGameState");
									$this->gamestate->nextState("allPassedOnReactions"); // goes to executeActionShoot

							break;
							case "askInvestigateReaction":
									$this->gamestate->nextState("allPassedOnReactions"); // goes to executeActionInvestigate
							break;
							default:
								$this->gamestate->nextState("allPassedOnReactions"); // where this goes depends on which state you're in
							break;
						}
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


				$this->setEquipmentCardOwner($cardId, $targetPlayer); // change the owner of the card in the database
				$counterActiveEquipmentForPlayer = $this->getActiveEquipmentCardIdsForPlayer($targetPlayer);
				self::notifyAllPlayers( 'activatePlayerEquipment', clienttranslate( '${player_name} has activated ${equipment_name} targeting ${target_player_name}.' ), array(
												 'i18n' => array( 'equipment_name' ),
												 'player_name' => $equipmentOwnerPlayerName,
												 'target_player_name' => $targetPlayerName,
												 'equipment_name' => $equipmentName,
						 						 'equipment_id' => $cardId,
						 						 'collector_number' => $collectorNumber,
												 'player_id_playing' => $equipmentOwner,
						 						 'player_id_receiving' => $targetPlayer,
												 'equipment_name' => $equipName,
												 'equipment_effect' => $equipEffect,
												 'count_active_equipment' => $counterActiveEquipmentForPlayer
				) );

		}

		function resetEquipmentAfterDiscard($cardId)
		{
				$ownerId = $this->getEquipmentCardOwner($cardId);

				$sqlUpdate = "UPDATE equipmentCards SET ";
				//$sqlUpdate .= "equipment_owner=0,done_selecting=0,equipment_target_1='',equipment_target_2='',equipment_target_3='',equipment_target_4='',equipment_target_5='',equipment_target_6='',equipment_target_7='',equipment_target_8='',player_target_1='',player_target_2='',gun_target_1='',gun_target_2='',equipment_is_active=0 WHERE ";
				$sqlUpdate .= "equipment_owner=0,done_selecting=0,equipment_target_1='',equipment_target_2='',equipment_target_3='',equipment_target_4='',equipment_target_5='',equipment_target_6='',equipment_target_7='',equipment_target_8='',player_target_1='',player_target_2='',gun_target_1='',gun_target_2='',equipment_is_active=0,discarded_by=$ownerId WHERE ";
				$sqlUpdate .= "card_id=$cardId";

				self::DbQuery( $sqlUpdate );
		}

		// Return true if it is valid to play this equipment right now. Throw an error otherwise to explain why it can't be used.
		function validateEquipmentUsage($equipmentCardId, $playerIdUsing, $checkStateToo)
		{
				$cardLocation = $this->getEquipmentCardLocation($equipmentCardId);
				if($cardLocation != 'hand')
				{ // this equipment card is NOT in hand
						return false;
				}

				$ownerOfEquipment = $this->getEquipmentCardOwner($equipmentCardId);
				//throw new feException( "Equipment owner is $ownerOfEquipment and player asking is $playerIdUsing");
				if($ownerOfEquipment != $playerIdUsing)
				{ // this equipment card is in someone else's hand
					//throw new feException( "player using: $playerIdUsing and equipment owner: $ownerOfEquipment");
						return false;
				}

				$stateName = $this->getStateName(); // get the name of the current state

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
							$allHiddenCards = $this->getAllHiddenCards();
							if(count($allHiddenCards) > 0)
							{ // there is at least one hidden card out there
									return true;
							}
							else
							{
									return false;
							}

						break;
						case 16: // wiretap
								$allHiddenCards = $this->getAllHiddenCards();
								if(count($allHiddenCards) > 0)
								{ // there is at least one hidden card out there
										return true;
								}
								else
								{
										return false;
								}
						break;

						case 44: // riot shield
							$gunsShooting = $this->getGunsShooting();
							//if(($checkStateToo && ($stateName == "chooseEquipmentToPlayReactShoot" || $stateName == "chooseEquipmentToPlayReactBite" )) ||
							//!$checkStateToo)
							//{ // we're checking the state and we're in the right state OR we're not checking the state
							if(count($gunsShooting) > 0)
							{
									return true;
							}
							else
							{ // we're checking the state but we're not in the correct state
									return false;
							}
						break;

						case 11: // restraining order
							$gunsShooting = $this->getGunsShooting();
							//if(($checkStateToo && ($stateName == "chooseEquipmentToPlayReactShoot" || $stateName == "chooseEquipmentToPlayReactBite" )) ||
							//!$checkStateToo)
							//{ // we're checking the state and we're in the right state OR we're not checking the state
							if(count($gunsShooting) > 0)
							{
									$getLivingPlayers = $this->getLivingPlayers();
									if(count($getLivingPlayers) < 3)
									{ // there are only 2 players left
											return false;
									}

									return true;
							}
							else
							{ // we're checking the state but we're not in the correct state
									return false;
							}
						break;

						case 37: // mobile detonator
							$gunsShooting = $this->getGunsShooting();
							//if(($checkStateToo && ($stateName == "chooseEquipmentToPlayReactShoot" || $stateName == "chooseEquipmentToPlayReactBite" )) ||
							//!$checkStateToo)
							//{ // we're checking the state and we're in the right state OR we're not checking the state
							if(count($gunsShooting) > 0)
							{
									return true;
							}
							else
							{ // we want to check the state but we're not in the correct state
									return false;
							}
						break;

						case 4: // evidence bag
							$allEquipmentCardsInHand = $this->getAllEquipmentCardsInHand();
							$allEquipmentCardsThatAreActive = $this->getAllPlayerBoardActiveEquipmentCards();
							//throw new feException( "cards in hand: $allEquipmentCardsInHand and active cards: $allEquipmentCardsThatAreActive");
							if(count($allEquipmentCardsInHand) < 1 && count($allEquipmentCardsThatAreActive) < 1)
							{ // there are no valid targets
									return false;
							}
							else
							{ // there is at least one valid target
									return true;
							}
						break;

						case 35: // med kit
							$woundedPlayers = $this->getWoundedPlayers();
							if(count($woundedPlayers) > 0 )
							{ // at least one player is wounded
									return true;
							}
							else
							{
								//throw new feException( "0 wounded players");
									return false;
							}
						break;

						case 14: // taser
								$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)

								if($playerWhoseTurnItIs != $ownerOfEquipment)
								{ // it's not this player's turn
										return false;
								}

								$canUse = false;
								$guns = $this->getGunsHeld(); // get all guns currently being held by a player
								foreach( $guns as $gun )
								{ // go through each gun that is currently held
										$gunHolderPlayerId = $gun['gun_held_by']; // get the PLAYER ID of the player holding this gun

										if($gunHolderPlayerId == $ownerOfEquipment)
										{ // the player trying to use Taser is already holding a gun
												return false;
										}

										if(!$this->isPlayerZombie($gunHolderPlayerId))
										{ // this player is not a zombie (taser does not work on zombies)
												$canUse = true;
										}
								}

								if(count($guns) < 1)
								{ // no one is holding a gun
										return false;
								}

								return $canUse;
						break;

						case 3: // Defibrillator
							$eliminatedPlayers = $this->getEliminatedPlayers($ownerOfEquipment);
							$nonInfectorZombiePlayers = $this->getAllNonInfectorZombies();
							if(count($eliminatedPlayers) > 0 || count($nonInfectorZombiePlayers) > 0)
							{ // at least one player is eliminated or a zombie
									return true;
							}
							else
							{
									return false;
							}
						break;
						case 1: // Blackmail
						$getLivingPlayers = $this->getLivingPlayers();
						if(count($getLivingPlayers) < 3)
						{ // there are only 2 players left
								return false;
						}
						else
						{
								return true; // this is always valid
						}
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

						case 60: // crossbow
							$gunsHeld = $this->getGunsHeld(); // get all guns currently being held by a player
							foreach( $gunsHeld as $gun )
							{ // go through each gun that is currently held
									$gunHolderPlayerId = $gun['gun_held_by']; // get the PLAYER ID of the player holding this gun

									if($gunHolderPlayerId != $ownerOfEquipment)
									{ // this gun is held by a different player

											if(!$this->doesPlayerHaveRevealedLeader($gunHolderPlayerId))
											{ // this player does not have a revealed leader card
													return true; // we found a valid target
											}
									}
							}
							return false; // no valid targets were found
							break;

							case 61: // Transfusion Tube
									$infectionTokenCount = $this->countAllInfectionTokens(); // get all infection tokens
									if($infectionTokenCount > 0)
									{ // there is at least one infection token out there
											return true;
									}
									else
									{
											return false;
									}
							break;

						case 62: // Zombie Serum
							$numberInfectionDiceRolled = count($this->getInfectionDiceRolled());
							$numberZombieDiceRolled = count($this->getZombieDiceRolled());

							if($checkStateToo && (($stateName == "chooseEquipmentToPlayReactEndOfTurn" && $numberInfectionDiceRolled > 0) ||
								 ($stateName == "chooseEquipmentToPlayReactShoot" && $numberZombieDiceRolled > 0) ||
								 ($stateName == "chooseEquipmentToPlayReactBite" && $numberZombieDiceRolled > 0) ||
								 ($stateName == "askBiteReaction" && $numberZombieDiceRolled > 0)) ||
								 !$checkStateToo)
							{ // we're checking the state and the infection or zombie dice were just rolled OR we're not checking state
									return true;
							}
							else
							{ // we are checking the state but we are not in the correct state
									return false;
							}

						case 63: // Flamethrower
									$zombieCount = count($this->getAllZombies()); // count how many zombies there are
									if($zombieCount > 0)
									{ // there is at least one zombie out there
											return true;
									}
									else
									{
											return false;
									}
							break;

						case 64: // Chainsaw

							$allHiddenCards = $this->getAllOpponentHiddenCards($ownerOfEquipment);
							if(count($allHiddenCards) > 0)
							{ // there is at least one hidden card held by an opponent out there
									return true;
							}
							else
							{ // no opponents have hidden cards
									return false;
							}

						break;


						case 65: // Zombie Mask
								$revealedLeadersOrInfector = 0;
								if(!$this->isInfectorHidden())
								{
									$revealedLeadersOrInfector++;
								}
								if(!$this->isKingpinHidden())
								{
									$revealedLeadersOrInfector++;
								}
								if(!$this->isAgentHidden())
								{
									$revealedLeadersOrInfector++;
								}

								if($revealedLeadersOrInfector < 2)
								{ // there are only 2 players left
										return false;
								}
								else
								{
										return true;
								}
						break;

						case 66: // Machete
								$zombieCount = count($this->getAllZombies()); // count how many zombies there are
								if($zombieCount > 0)
								{ // there is at least one zombie out there
										return true;
								}
								else
								{
										return false;
								}
						break;

						case 67: // Weapon Crate
							$unarmedInfectedPlayers = $this->getUnarmedInfectedPlayers();
							$armedInfectedPlayers = $this->getArmedInfectedPlayers();
							if(count($unarmedInfectedPlayers) > 0 || count($armedInfectedPlayers) > 0 )
							{ // there is at least one infected player
									return true;
							}
							else
							{
								return false;
							}
						break;

						case 68: // Alarm Clock
							return true; // this is always valid (you can always just use it to give someone an Infection Token)
							break;

						default:
							return false; // return false by default
							break;

				}
		}

		// Called when someone clicks on an Equipment card as an Equipment target. Returns true if it is a valid target. Throw an
		// error if not explaining why.
		function validateEquipmentEquipmentSelection($equipmentIdInUse, $clickedEquipmentId)
		{
				$collectorNumber = $this->getCollectorNumberFromId($equipmentIdInUse); // collector number of equipment in use

				$ownerOfEquipmentInUse = $this->getEquipmentCardOwner($equipmentIdInUse); // get the player ID who is playing the equipment card
				$ownerOfEquipmentTarget = $this->getEquipmentCardOwner($clickedEquipmentId);

				switch($collectorNumber)
				{
						case 4: // evidence bag
							if($ownerOfEquipmentInUse == $ownerOfEquipmentTarget)
							{ // the owner of the targeted equipment card is trying to give their card to themself
									throw new BgaUserException( self::_("Please choose someone other than yourself.") );
							}

							return true;

						break;
						default:
							throw new feException( "Unrecognized equipment selection collector number:$collectorNumber");
							break;
				}

		}

		// Called when someone clicks a player as an Equipment target. Return true if they can use it. Throw an error if not
		// explaining why it can't be used.
		function validateEquipmentPlayerSelection($playerId, $equipmentCardId, $throwErrors)
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
										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
										}
										else
										{
												return false;
										}
								}
								else
								{ // they are targeting a living player
										return true;
								}
						break;
						case 44: // riot shield
						$playerShooting = $this->getEquipmentPlayedOnTurn($equipmentCardId);
						$gunId = $this->getGunIdHeldByPlayer($playerShooting); // get the id of the gun the shoot player holds
						$currentTarget = $this->getPlayerIdOfGunTarget($gunId); // current target of gun

						if($this->isPlayerEliminated($playerId))
						{ // they are trying to target an eliminated player

								if($throwErrors)
								{
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
								}
								else
								{
										return false;
								}
						}
						elseif(!$this->isPlayerIdToLeftOrRightOfPlayerId($currentTarget, $playerId))
						{ // they are trying to keep their gun aimed at the same player

								if($throwErrors)
								{
										throw new BgaUserException( self::_("Please select someone to the left or right of target.") );
								}
								else
								{
										return false;
								}
						}
						else
						{ // they are targeting a living player who is to the left or right of target
								return true;
						}
						break;
						case 11: // restraining order
								$playerShooting = $this->getEquipmentPlayedOnTurn($equipmentCardId);
								$gunId = $this->getGunIdHeldByPlayer($playerShooting); // get the id of the gun the shoot player holds
								$currentTarget = $this->getPlayerIdOfGunTarget($gunId); // current target of gun
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
										}
										else
										{
												return false;
										}
								}
								elseif($currentTarget == $playerId)
								{ // they are trying to keep their gun aimed at the same player

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please select a new target.") );
										}
										else
										{
												return false;
										}
								}
								elseif($playerShooting == $playerId)
								{ // they are trying to target themself

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target someone other than yourself.") );
										}
										else
										{
												return false;
										}
								}
								else
								{ // they are targeting a living player
										return true;
								}

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

								if($throwErrors)
								{
										throw new BgaUserException( self::_("Please choose a wounded player.") );
								}
								else
								{
										return false;
								}
						}
						return true;
						break;
						case 14: // taser
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
										}
										else
										{
												return false;
										}
								}
								elseif(!$this->isPlayerHoldingGun($playerId))
								{ // player isn't holding a gun

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player holding a gun.") );
										}
										else
										{
												return false;
										}
								}
								elseif($this->isPlayerZombie($playerId))
								{ // player is a zombie

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Tasers do not work on zombies.") );
										}
										else
										{
												return false;
										}
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

										if($throwErrors)
										{
												throw new BgaUserException( self::_("You must choose a player other than yourself.") );
										}
										else
										{
												return false;
										}
								}

								$equipmentTargeted = $this->getEquipmentTarget1($equipmentCardId);
								$ownerOfEquipmentTarget = $this->getEquipmentCardOwner($equipmentTargeted);

								if($playerId == $ownerOfEquipmentTarget)
								{ // they are trying to give an equipment card to the player who already owns it

										if($throwErrors)
										{
												throw new BgaUserException( self::_("You must choose a player other than the who already has this Equipment Card.") );
										}
										else
										{
												return false;
										}
								}
						return true;
						break;
						case 3: // Defibrillator
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player
										return true;
								}
								elseif($this->isPlayerNonInfectorZombie($playerId))
								{ // they are targeting a non-infector Zombie
										return true;
								}
								else
								{ // they are targeting a living player

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target an eliminated player.") );
										}
										else
										{
												return false;
										}
								}
						break;
						case 30: // Disguise
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
										}
										else
										{
												return false;
										}
								}
								else
								{ // they are targeting a living player
										return true;
								}
						break;
						case 45: // Walkie Talkie
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
										}
										else
										{
												return false;
										}
								}
								else
								{ // they are targeting a living player
										return true;
								}
						break;
						case 9: // Polygraph
								if($this->isPlayerDisguised($playerId))
								{ // they are using this on a disguised player
										if($throwErrors)
										{
												throw new BgaUserException( self::_("This player is disguised and cannot be investigated.") );
										}
										else
										{
												return false;
										}
								}

								if($playerId == $equipmentCardOwner)
								{ // they are trying to target themselves

										if($throwErrors)
										{
												throw new BgaUserException( self::_("You must choose a player other than yourself.") );
										}
										else
										{
												return false;
										}
								}

								return true; // there are no other restrictions on the player chosen (you can even choose yourself if you want)
						break;
						case 13: // Surveillance Camera
								if($this->isPlayerEliminated($playerId))
								{ // they are trying to target an eliminated player

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
										}
										else
										{
												return false;
										}
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

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
										}
										else
										{
												return false;
										}
								}
								elseif(!$this->isPlayerHoldingGun($playerId))
								{ // player isn't holding a gun

										if($throwErrors)
										{
												throw new BgaUserException( self::_("Please target a player holding a gun.") );
										}
										else
										{
												return false;
										}
								}
								else
								{ // they are targeting a living player holding a gun
										return true;
								}
								return false; // won't get here
						break;

						case 60: // Crossbow
						if($this->isPlayerEliminated($playerId))
						{ // they are trying to target an eliminated player

								if($throwErrors)
								{
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
								}
								else
								{
										return false;
								}
						}
						elseif($this->doesPlayerHaveRevealedLeader($playerId))
						{ // player has a face-up Leader card
								if($throwErrors)
								{
										throw new BgaUserException( self::_("The player cannot have a revealed Leader card.") );
								}
								else
								{
										return false;
								}
						}
						elseif(!$this->isPlayerHoldingGun($playerId))
						{ // the player is not armed
								if($throwErrors)
								{
										throw new BgaUserException( self::_("The player must be armed.") );
								}
								else
								{
										return false;
								}
						}
						else
						{ // the target is valid
								return true;
						}
						break;

						case 68: // Alarm Clock
						if($this->isPlayerEliminated($playerId))
						{ // they are trying to target an eliminated player

								if($throwErrors)
								{
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
								}
								else
								{
										return false;
								}
						}
						else
						{ // the target is valid
								return true;
						}
						break;
						default:

							if($throwErrors)
							{
									throw new feException( "Unrecognized player selection collector number:$collectorNumber");
							}
							else
							{
									return false;
							}
							break;

				}
		}

		function validateInvestigatePlayer($playerInvestigating, $playerBeingInvestigated)
		{
			if($this->isPlayerEliminated($playerBeingInvestigated))
			{ // this player is not in the game
					throw new BgaUserException( self::_("This player is eliminated. Please choose a living player.") );
			}
			elseif($this->isPlayerDisguised($playerBeingInvestigated))
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
				$cardPositionTargeted = $this->getIntegrityCardPosition($integrityCardId); // 1, 2, 3
				$target1 = $this->getEquipmentTarget1($equipmentCardId);
				$target2 = $this->getEquipmentTarget2($equipmentCardId);
				$target3 = $this->getEquipmentTarget3($equipmentCardId);
				$target4 = $this->getEquipmentTarget4($equipmentCardId);
				$target5 = $this->getEquipmentTarget5($equipmentCardId);
				$target6 = $this->getEquipmentTarget6($equipmentCardId);
				$target7 = $this->getEquipmentTarget7($equipmentCardId);
				$target8 = $this->getEquipmentTarget8($equipmentCardId);
				switch($collectorNumber)
				{
						case 15: // truth serum
								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
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
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
								}

								if($this->isPlayerDisguised($ownerOfNewIntegrityCardTarget))
								{ // they are using this on a disguised player
										throw new BgaUserException( self::_("This player is disguised and cannot be investigated.") );
								}

								if(is_null($target1) || $target1 == '')
								{ // this is the first card we're selecting
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
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
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

								if($this->isPlayerDisguised($ownerOfNewIntegrityCardTarget))
								{ // they are using this on a disguised player
										throw new BgaUserException( self::_("This player is disguised and cannot be investigated.") );
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

								if(!is_null($target4) && $target4 != '')
								{ // this is the fifth card we're targeting
										$ownerOfFourth = $this->getIntegrityCardOwner($target4);
										if($ownerOfNewIntegrityCardTarget == $ownerOfFourth)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								if(!is_null($target5) && $target5 != '')
								{ // this is the sixth card we're targeting
										$ownerOfFifth = $this->getIntegrityCardOwner($target5);
										if($ownerOfNewIntegrityCardTarget == $ownerOfFifth)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								if(!is_null($target6) && $target6 != '')
								{ // this is the seventh card we're targeting
										$ownerOfSixth = $this->getIntegrityCardOwner($target5);
										if($ownerOfNewIntegrityCardTarget == $ownerOfSixth)
										{
												throw new BgaUserException( self::_("Please target a different player than one you've already targeted.") );
										}
								}

								return true;
						break;

						case 61: // Transfusion Tube
								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please investigate a living player.") );
								}

								if(is_null($target1) || $target1 == '')
								{ // this is the first card we're selcting
										if(!$this->isIntegrityCardInfected($integrityCardId))
										{ // they are trying to target an Integrity Card that doesn't have an infection token
												throw new BgaUserException( self::_("Please target an infected card.") );
										}

										// notify player: "Now choose where the Infection Token will move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now choose where the Infection Token should move.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Now choose where the Infection Token should move.' )
										) );

										return true;
								}

								if(is_null($target2) || $target2 == '')
								{ // this is the second card we're selcting
										if($this->isIntegrityCardInfected($integrityCardId))
										{ // they are trying to target an Integrity Card that doesn't have an infection token
												throw new BgaUserException( self::_("Please target a card that is NOT infected where your previous selection will move.") );
										}

										// notify player: "Please select the next Infection Token you want to move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Please select the next Infection Token you want to move.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Please select the next Infection Token you want to move.' )
										) );

										return true;
								}

								if(is_null($target3) || $target3 == '')
								{ // this is the third card we're selcting
										if(!$this->isIntegrityCardInfected($integrityCardId))
										{ // they are trying to target an Integrity Card that doesn't have an infection token
												throw new BgaUserException( self::_("Please target an infected card.") );
										}

										// notify player: "Now choose where the Infection Token will move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now choose where the Infection Token should move.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Now choose where the Infection Token should move.' )
										) );

										return true;
								}

								if(is_null($target4) || $target4 == '')
								{ // this is the fourth card we're selcting
										if($this->isIntegrityCardInfected($integrityCardId))
										{ // they are trying to target an Integrity Card that doesn't have an infection token
												throw new BgaUserException( self::_("Please target a card that is NOT infected where your previous selection will move.") );
										}

										// notify player: "Please select the next Infection Token you want to move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Please select the next Infection Token you want to move.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Please select the next Infection Token you want to move.' )
										) );

										return true;
								}

								if(is_null($target5) || $target5 == '')
								{ // this is the fifth card we're selcting
										if(!$this->isIntegrityCardInfected($integrityCardId))
										{ // they are trying to target an Integrity Card that doesn't have an infection token
												throw new BgaUserException( self::_("Please target an infected card.") );
										}

										// notify player: "Now choose where the Infection Token will move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now choose where the Infection Token should move.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Now choose where the Infection Token should move.' )
										) );

										return true;
								}

								if(is_null($target6) || $target6 == '')
								{ // this is the sixth card we're selcting
										if($this->isIntegrityCardInfected($integrityCardId))
										{ // they are trying to target an Integrity Card that doesn't have an infection token
												throw new BgaUserException( self::_("Please target a card that is NOT infected where your previous selection will move.") );
										}

										return true;
								}

								return true;
						break;

						case 64: // Chainsaw
								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
								}

								if(!$this->isIntegrityCardHidden($integrityCardId))
								{ // this card is already revealed
										throw new BgaUserException( self::_("Please target a hidden card.") );
								}

								if($equipmentCardOwner == $ownerOfNewIntegrityCardTarget)
								{ // they are trying to target their own integrity card
										throw new BgaUserException( self::_("Please target a player other than yourself.") );
								}

								return true;

						break;

						case 65: // Zombie Mask
								if($this->isPlayerEliminated($ownerOfNewIntegrityCardTarget))
								{ // they are trying to target an eliminated player's integrity card
										throw new BgaUserException( self::_("Please target a player who has not been eliminated.") );
								}


								if($this->isLeaderOrInfectorCard($integrityCardId))
								{ // this is a Leader or Infector card
												return true;
								}

								return false;
						break;

						case 66: // Machete
								if(is_null($target1) || $target1 == '')
								{ // this is the first card we're selcting
										if(!$this->isPlayerZombie($ownerOfNewIntegrityCardTarget))
										{ // they are trying to target an Integrity Card of a non-zombie
												throw new BgaUserException( self::_("Please target a zombie's Integrity Card first.") );
										}

										// notify player: "Now choose where the Infection Token will move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now choose an Honest or Crooked card of a non-Zombie.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Now choose an Honest or Crooked card of a non-Zombie.' )
										) );

										return true;
								}

								if((is_null($target2) || $target2 == '') &&
									$target2 != $target1)
								{ // this is the second card we're selcting
										if($this->isPlayerZombie($ownerOfNewIntegrityCardTarget))
										{ // they are trying to target an Integrity Card of a zombie
												throw new BgaUserException( self::_("Please target a non-zombie's Integrity Card.") );
										}

										if($this->isIntegrityCardHidden($integrityCardId))
										{ // they are trying to target a hidden card
												throw new BgaUserException( self::_("Please target a revealed Integrity Card.") );
										}

										if($this->getCardTypeFromCardId($integrityCardId) != 'honest' &&
										$this->getCardTypeFromCardId($integrityCardId) != 'crooked')
										{ // they are trying to target a Leader or Infector card
												throw new BgaUserException( self::_("Please target an Honest or Crooked card.") );
										}

										// notify player: "Please select the next Infection Token you want to move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now you may choose another HONEST or CROOKED card of a Zombie.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Now you may choose another HONEST or CROOKED card of a Zombie.' )
										) );

										return true;
								}

								if((is_null($target3) || $target3 == '') &&
									$target3 != $target1 &&
										$target3 != $target2)
								{ // this is the third card we're selcting
										if(!$this->isPlayerZombie($ownerOfNewIntegrityCardTarget))
										{ // we're targeting an integrity card of a different zombie than when we started targeting
												throw new BgaUserException( self::_("Please target a zombie's Integrity Card first.") );
										}

										if($ownerOfNewIntegrityCardTarget != $this->getIntegrityCardOwner($target1))
										{ // make sure we're targeting an integrity card of the same zombie we started targeting
												$nameOfOriginalZombie = $this->getPlayerNameFromPlayerId($this->getIntegrityCardOwner($target1));
												throw new BgaUserException( self::_("Please target the same zombie as you did with your first card ($nameOfOriginalZombie).") );
										}

										// notify player: "Now choose where the Infection Token will move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now choose an HONEST or CROOKED card of a non-Zombie.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Now choose an HONEST or CROOKED card of a non-Zombie.' )
										) );

										return true;
								}

								if((is_null($target4) || $target4 == '') &&
									$target4 != $target1 &&
										$target4 != $target2 &&
											$target4 != $target3)
								{ // this is the fourth card we're selcting
									if($this->isPlayerZombie($ownerOfNewIntegrityCardTarget))
									{ // they are trying to target an Integrity Card of a zombie
											throw new BgaUserException( self::_("Please target a non-zombie's Integrity Card.") );
									}

									if($this->isIntegrityCardHidden($integrityCardId))
									{ // they are trying to target a hidden card
											throw new BgaUserException( self::_("Please target a revealed Integrity Card.") );
									}

									if($this->getCardTypeFromCardId($integrityCardId) != 'honest' &&
									$this->getCardTypeFromCardId($integrityCardId) != 'crooked')
									{ // they are trying to target a Leader or Infector card
											throw new BgaUserException( self::_("Please target an HONEST or CROOKED card.") );
									}

									// notify player: "Please select the next Infection Token you want to move." Also highlight the selection.
									self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now you may choose another HONEST or CROOKED card of a Zombie.' ), array(
																			 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																			 'cardPositionTargeted' => $cardPositionTargeted,
																			 'cardIdTargeted' => $integrityCardId,
																			 'descriptionText' => clienttranslate( 'Now you may choose another HONEST or CROOKED card of a Zombie.' )
									) );

									return true;
								}

								if((is_null($target5) || $target5 == '') &&
									$target5 != $target1 &&
										$target5 != $target2 &&
											$target5 != $target3 &&
												$target5 != $target4)
								{ // this is the fifth card we're selcting
										if(!$this->isPlayerZombie($ownerOfNewIntegrityCardTarget))
										{ // they are trying to target an Integrity Card of a non-zombie
												throw new BgaUserException( self::_("Please target a zombie's Integrity Card first.") );
										}

										if($ownerOfNewIntegrityCardTarget != $this->getIntegrityCardOwner($target1))
										{ // we're targeting an integrity card of a different zombie than when we started targeting
												$nameOfOriginalZombie = $this->getPlayerNameFromPlayerId($this->getIntegrityCardOwner($target1));
												throw new BgaUserException( self::_("Please target the same zombie as you did with your first card ($nameOfOriginalZombie).") );
										}

										// notify player: "Now choose where the Infection Token will move." Also highlight the selection.
										self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now choose an HONEST or CROOKED card of a non-Zombie.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																				 'cardPositionTargeted' => $cardPositionTargeted,
																				 'cardIdTargeted' => $integrityCardId,
																				 'descriptionText' => clienttranslate( 'Now choose an HONEST or CROOKED card of a non-Zombie.' )
										) );

										return true;
								}

								if((is_null($target6) || $target6 == '') &&
									$target6 != $target1 &&
										$target6 != $target2 &&
											$target6 != $target3 &&
												$target6 != $target4 &&
													$target6 != $target5)
								{ // this is the sixth card we're selcting
									if($this->isPlayerZombie($ownerOfNewIntegrityCardTarget))
									{ // they are trying to target an Integrity Card of a zombie
											throw new BgaUserException( self::_("Please target a non-zombie's Integrity Card.") );
									}

									if($this->isIntegrityCardHidden($integrityCardId))
									{ // they are trying to target a hidden card
											throw new BgaUserException( self::_("Please target a revealed Integrity Card.") );
									}

									if($this->getCardTypeFromCardId($integrityCardId) != 'honest' &&
									$this->getCardTypeFromCardId($integrityCardId) != 'crooked')
									{ // they are trying to target a Leader or Infector card
											throw new BgaUserException( self::_("Please target an HONEST or CROOKED card.") );
									}

									// notify player: "Please select the next Infection Token you want to move." Also highlight the selection.
									self::notifyPlayer( $equipmentCardOwner, 'targetIntegrityCard', clienttranslate( 'Now you may choose another HONEST or CROOKED card of a Zombie.' ), array(
																			 'playerIdWhoIsTargetingCard' => $equipmentCardOwner,
																			 'cardPositionTargeted' => $cardPositionTargeted,
																			 'cardIdTargeted' => $integrityCardId,
																			 'descriptionText' => clienttranslate( 'Now you may choose another HONEST or CROOKED card of a Zombie.' )
									) );

									return true;
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
				$playerUsingEquipment = $this->getEquipmentCardOwner($equipmentCardId);
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
							if($this->doneSelecting($equipmentCardId))
							{
									return true; // they say they're done selecting
							}

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
								if($this->doneSelecting($equipmentCardId))
								{
										return true; // they say they're done selecting
								}

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
								if($this->doneSelecting($equipmentCardId))
								{
										return true; // they say they're done selecting
								}

								$numberOfGunsHeldWithHiddenCards = $this->getNumberOfGunsHeldByPlayersWithHiddenOpponentIntegrityCards($playerUsingEquipment); // get guns held by players with at least one hidden integrity card
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

						case 60: // Crossbow
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

						case 61: // Transfusion Tube
							$numberOfEquipmentTargets = $this->getNumberOfEquipmentTargets(); // get number of integrity cards targeted
							if($this->doneSelecting($equipmentCardId) && $numberOfEquipmentTargets % 2 == 0)
							{ // the player has indicated they are done selecting and we have an even number of targets
									return true;
							}
							else
							{
									$this->setDoneSelecting($equipmentCardId, 0); // they can't be done selecting yet
							}

							$playerUsingEquipment = $this->getEquipmentCardOwner($equipmentCardId);
							$numberOfInfectionTokens = $this->countAllInfectionTokens(); // count how many infection tokens there are
							if($numberOfInfectionTokens > 3)
							{
									$numberOfInfectionTokens = 3; // we can only move up to 3 tokens
							}

	//throw new feException( "metal detector guns held with hidden($numberOfGunsHeldWithHiddenCards) and equipment card targets($numberOfEquipmentTargets)");
							if($numberOfEquipmentTargets < (2 * $numberOfInfectionTokens))
							{
									return false; // we have not selected enough targets
							}
							else
							{
									return true; // all targets are selected
							}
						break;

						case 62: // Zombie Serum
							return true; // we don't need any input

  					case 63: // Flamethrower
							return true; // we don't need any input

						case 64: // Chainsaw
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

						case 65: // Zombie Mask
									$target1 = $this->getEquipmentTarget1($equipmentCardId);
									$target2 = $this->getEquipmentTarget2($equipmentCardId);
									//throw new feException( "target1:$target1 target2:$target2");
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

						case 66: // Machete
								$numberOfEquipmentTargets = $this->getNumberOfEquipmentTargets(); // get number of integrity cards targeted
								//throw new feException( "metal detector guns held with hidden($numberOfGunsHeldWithHiddenCards) and equipment card targets($numberOfEquipmentTargets)");
								if($numberOfEquipmentTargets < 6 && !$this->doneSelecting($equipmentCardId))
								{
										return false; // we have not selected enough targets
								}
								else
								{
										return true; // all targets are selected
								}
						break;

						case 67: // Weapon Crate
							return true; // we don't need any input
						break;

						case 68: // Alarm Clock
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
			$state = $this->getStateName();
		//throw new feException( "setStateAfterTurnAction state:$state");
			$playersOverEquipmentCardLimit = $this->getPlayersOverEquipmentHandLimit(); // get any players over the equipment card hand limit
			if ($this->doesPlayerNeedToDiscard($playerWhoseTurnItIs))
			{ // too many cards in hand
						$this->gamestate->nextState( "discardEquipment" );
			}
			elseif(count($playersOverEquipmentCardLimit) > 0)
			{ // someone else is over the equipment card hand limit
					//throw new feException( "over hand limit" );
					$firstPlayerNeedingToDiscard = array_values($playersOverEquipmentCardLimit)[0]; // get the first one
					$playerIdOverLimit = $firstPlayerNeedingToDiscard['player_id'];

					$this->gamestate->changeActivePlayer($playerIdOverLimit); // make that player active so they can aim it
					$this->gamestate->nextState( "askDiscardOutOfTurn" );
			}
			elseif($this->isPlayerHoldingGun($playerWhoseTurnItIs))
			{ // this player IS holding a gun

					if($this->didNonZombiePlayerJustShootAZombie($playerWhoseTurnItIs))
					{ // this player just shot a zombie
							$this->gamestate->nextState( "askAimMustReaim" ); // ask the player to aim their arms
					}
					else
					{ // this player did NOT just shoot a zombie
							$this->gamestate->nextState( "askAim" ); // ask the player to aim their gun
					}
			}
			else
			{ // this player is NOT holding a gun
						if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
						{ // we are using the zombies expansion and the Infector is hidden
								$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
						}
						else
						{
								$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
						}
			}
		}

		// The player has just played an equipment card and we need to put them into the state that will allow
		// them to give us the input required for that equipment card like which cards or players they are targeting.
		function setStateForEquipment($equipmentId)
		{

//throw new feException( "setStateForEquipment equipmentId:$equipmentId");

				$collectorNumber = $this->getCollectorNumberFromId($equipmentId);
				$equipmentOwner = $this->getEquipmentCardOwner($equipmentId);
//throw new feException( "setStateForEquipment collectorNumber:$collectorNumber");
				switch($collectorNumber)
				{
						case 2: // coffee
								//throw new feException( "setState Coffee");

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
												//throw new feException( "allinputacquired after");
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
												$this->gamestate->nextState( "chooseActiveOrHandEquipmentCard" );
										}
										else
										{ // we do have the equipment card but we don't have the player
												$this->gamestate->nextState( "chooseAnotherPlayer" ); // ask them to target a player
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

						case 60: // Crossbow
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // the player has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "choosePlayer" ); // ask them to target a player
								}
						break;

						case 61: // Transfusion Tube
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // all targets acquired
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // ask them to target a player

										self::notifyPlayer( $equipmentOwner, 'targetIntegrityCard', clienttranslate( 'Please select an Infection Token you want to move.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentOwner,
																				 'cardPositionTargeted' => null,
																				 'cardIdTargeted' => null,
																				 'descriptionText' => clienttranslate( 'Please select an Infection Token you want to move.' )
										) );
								}
						break;

						case 62: // Zombie Serum
								//throw new feException( "setState Zombie Serum");
								$this->gamestate->nextState( "executeEquipment" ); // use the equipment
						break;

						case 63: // Flamethrower
								$this->gamestate->nextState( "executeEquipment" ); // use the equipment
						break;

						case 64: // Chainsaw
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // everything required has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // an integrity card is not yet targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // choose the integrity card you will reveal
								}
						break;
						case 65: // Zombie Mask
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // everything required has been targeted
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // an integrity card is not yet targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // choose the integrity card you will reveal
								}
						break;
						case 66: // Machete
								if($this->isAllInputAcquiredForEquipment($equipmentId))
								{ // all targets acquired
										$this->gamestate->nextState( "executeEquipment" ); // use the equipment
								}
								else
								{ // the player has not yet been targeted
										$this->gamestate->nextState( "chooseIntegrityCards" ); // ask them to target a player

										self::notifyPlayer( $equipmentOwner, 'targetIntegrityCard', clienttranslate( 'Please select an Integrity Card from a Zombie.' ), array(
																				 'playerIdWhoIsTargetingCard' => $equipmentOwner,
																				 'cardPositionTargeted' => null,
																				 'cardIdTargeted' => null,
																				 'descriptionText' => clienttranslate( 'Please select an Integrity Card from a Zombie.' )
										) );
								}
						break;
						case 67: // Weapon Crate
//throw new feException( "Weapon Crate");
								$this->setEquipmentPlayerTarget($equipmentId, $equipmentOwner);
								$this->gamestate->nextState( "executeEquipment" ); // use the equipment
						break;

						case 68: // Alarm Clock
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
								throw new feException( "Unrecognized equipment card: ".$collectorNumber );
								break;
				}

		}

		function isEquipmentTargetsChosenByOtherPlayer($collectorNumber)
		{
				switch($collectorNumber)
				{
						case 2: // coffee
								return false; // no equipment targets
						break;
						case 8: // planted evidence
								return false; // chosen by equipment player
						break;
						case 12: // smoke grenade
								return false; // no equipment targets
						break;
						case 15: // truth serum
								return false; // chosen by equipment player
						break;
						case 16: // wiretap
								return false; // chosen by equipment player
						break;

						case 44: // riot shield
								return true; // chosen by player being shot
						break;

						case 11: // restraining order
								return true; // chosen by shooter
						break;

						case 37: // mobile detonator
								return true; // not sure but this is no longer used
						break;

						case 4: // evidence bag
								return false; // chosen by equipment player
						break;

						case 35: // med kit
								return false; // chosen by equipment player
						break;

						case 14: // taser
								return false; // chosen by equipment player
						break;

						case 3: // Defibrillator
								return false; // chosen by equipment player
						break;
						case 1: // Blackmail
								return false; // chosen by equipment player
						break;
						case 30: // Disguise
								return false; // chosen by equipment player
						break;
						case 45: // Walkie Talkie
								return false; // chosen by equipment player
						break;
						case 9: // Polygraph
								return false; // chosen by equipment player
						break;
						case 13: // Surveillance Camera
							return false; // chosen by equipment player
						break;
						case 7: // Metal Detector
								return false; // chosen by equipment player
						break;
						case 17: // Deliriant
								return false; // chosen by equipment player
						break;
						case 6: // K-9 Unit
								return false; // chosen by equipment player
						break;

						case 60: // Crossbow
								return false; // chosen by equipment player
						break;

						case 61: // Transfusion Tube
								return false; // chosen by equipment player
						break;

						case 62: // Zombie Serum
								return false; // nothing to choose
						break;

						case 63: // Flamethrower
								return false; // nothign to choose
						break;

						case 64: // Chainsaw
								return false; // chosen by equipment player
						break;
						case 65: // Zombie Mask
								return false; // chosen by equipment player
						break;
						case 66: // Machete
								return false; // chosen by equipment player
						break;
						case 67: // Weapon Crate
								return true; // players should know why they just got a gun and are being asked to aim
						break;

						case 68: // Alarm Clock
								return false; // chosen by equipment player
						break;

						default:
								return false;
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
				$target5 = $this->getEquipmentTarget5($equipmentCardId);
				$target6 = $this->getEquipmentTarget6($equipmentCardId);
				$target7 = $this->getEquipmentTarget7($equipmentCardId);
				$target8 = $this->getEquipmentTarget8($equipmentCardId);
				if(is_null($target1) || $target1 == '')
				{ // we don't yet have a first target
						$sqlUpdate .= "equipment_target_1='$target' WHERE ";
				}
				elseif(is_null($target2) || $target2 == '')
				{ // we don't yet have a second target
						$sqlUpdate .= "equipment_target_2='$target' WHERE ";
				}
				elseif(is_null($target3) || $target3 == '')
				{ // we don't yet have a third target
						$sqlUpdate .= "equipment_target_3='$target' WHERE ";
				}
				elseif(is_null($target4) || $target4 == '')
				{ // we don't yet have a fourth target
						$sqlUpdate .= "equipment_target_4='$target' WHERE ";
				}
				elseif(is_null($target5) || $target5 == '')
				{ // we don't yet have a fifth target
						$sqlUpdate .= "equipment_target_5='$target' WHERE ";
				}
				elseif(is_null($target6) || $target6 == '')
				{ // we don't yet have a sixth target
						$sqlUpdate .= "equipment_target_6='$target' WHERE ";
				}
				elseif(is_null($target7) || $target7 == '')
				{ // we don't yet have a seventh target
						$sqlUpdate .= "equipment_target_7='$target' WHERE ";
				}
				else
				{ // we don't yet have an eighth target
						$sqlUpdate .= "equipment_target_8='$target' WHERE ";
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

		// Overwrites whatever is in player_target_1 with this value. (normally you want to use
		// setEquipmentPlayerTarget() instead)
		function setEquipmentPlayerTarget1($equipmentCardId, $target)
		{
				$sqlUpdate = "UPDATE equipmentCards SET ";
				$sqlUpdate .= "player_target_1='$target' WHERE ";
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
				if(self::isSpectator())
				{ // this is a spectator
						$playerAsking = $this->getPlayerIdFromPlayerNo(1);
				}

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
				if(self::isSpectator())
				{ // this is a spectator
						return 0;
				}

				$sql = "SELECT pcv.is_seen FROM `integrityCards` ic ";
				$sql .= "JOIN `playerCardVisibility` pcv ON ic.card_id=pcv.card_id ";
				$sql .= "JOIN `playerPositioning` pp ON (ic.card_location=pp.player_id AND pp.player_asking=$playerAsking) ";
				$sql .= "WHERE pcv.player_id=$playerAsking AND ic.card_location=$playerTargeting AND ic.card_location_arg=$cardPosition LIMIT 1";

				//var_dump( $sql );
				//die('ok');

				return self::getUniqueValueFromDb($sql);
		}

		function getIntegrityCardId($playerTargeting, $cardPosition)
		{
				$sql = "SELECT ic.card_id FROM `integrityCards` ic ";
				$sql .= "WHERE ic.card_location=$playerTargeting AND ic.card_location_arg=$cardPosition LIMIT 1";

				//var_dump( $sql );
				//die('ok');

				return self::getUniqueValueFromDb($sql);
		}

		function clearDieValues()
		{
				$sqlUpdate = "UPDATE dice SET ";
				$sqlUpdate .= "die_value=0,roller_player_id='',target_player_id='',card_position_infected=0";

				self::DbQuery( $sqlUpdate );
		}

		function setDieValue($dieId, $dieValue, $playerId, $targetPlayerId, $infectedCardPosition)
		{
				$sqlUpdate = "UPDATE dice SET ";
				$sqlUpdate .= "die_value=$dieValue,roller_player_id='$playerId',target_player_id='$targetPlayerId',card_position_infected=$infectedCardPosition WHERE ";
				$sqlUpdate .= "die_id=$dieId";

				self::DbQuery( $sqlUpdate );
		}

		// Get a gun that is not being held by a player.
		function getNextGunAvailable()
		{
				$sql = "SELECT * FROM `guns` ";
				$sql .= "WHERE gun_type='gun' AND (gun_held_by='' OR gun_held_by IS NULL) ";
				$sql .= "ORDER BY gun_id asc ";
				$sql .= "LIMIT 1 ";

				//var_dump( $sqlUpdate );
				//die('ok');

				return self::getObjectListFromDB( $sql );
		}

		function getNextArmsAvailable()
		{
			$sql = "SELECT * FROM `guns` ";
			$sql .= "WHERE gun_type='arm' AND (gun_held_by='' OR gun_held_by IS NULL) ";
			$sql .= "ORDER BY gun_id asc ";
			$sql .= "LIMIT 1 ";

				//var_dump( $sqlUpdate );
				//die('ok');

				return self::getObjectListFromDB( $sql );
		}

		// Get the PLAYER ID who is being TARGETED by a GUN.
		function getPlayerIdOfGunTarget($gunId)
		{
				if($gunId == null || $gunId == '')
				{
						return null;
				}

				return self::getUniqueValueFromDb("SELECT gun_aimed_at FROM guns WHERE gun_id=$gunId LIMIT 1");
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
				return self::getUniqueValueFromDb("SELECT gun_state FROM guns WHERE gun_id=$gunId LIMIT 1");
		}

		function setGunShotThisTurn($gunId, $value)
		{
			return;

				$sqlUpdate = "UPDATE guns SET ";
				$sqlUpdate .= "gun_fired_this_turn=$value WHERE ";
				$sqlUpdate .= "gun_id=$gunId";

				self::DbQuery( $sqlUpdate );
		}

		function getGunShotThisTurn($gunId)
		{
				return self::getUniqueValueFromDb("SELECT gun_fired_this_turn FROM guns WHERE gun_id=$gunId LIMIT 1");
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
				return self::getUniqueValueFromDb("SELECT gun_can_shoot FROM guns WHERE gun_id=$gunId LIMIT 1");
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
				return self::getUniqueValueFromDb("SELECT gun_acquired_in_state FROM guns WHERE gun_id=$gunId LIMIT 1");
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

		function getWoundedPlayers()
		{
				$sql = "SELECT * FROM player p LEFT JOIN integrityCards ic on p.player_id=ic.card_location WHERE ic.has_wound=1";

				return self::getObjectListFromDB( $sql );
		}

		// returns TRUE if the player is wounded and FALSE otherwise
		function isPlayerWounded($playerId)
		{
				if($this->isPlayerALeader($playerId))
				{ // this player is a leader
						$leaderCardId = $this->getLeaderCardIdForPlayer($playerId); // get the card id of the leader card

						$hasWound = self::getUniqueValueFromDb("SELECT has_wound FROM integrityCards WHERE card_id=$leaderCardId LIMIT 1");

						if($hasWound == 1)
						{
								return true;
						}
				}

				return false;
		}

		function isCardWounded($cardId)
		{
						$hasWound = self::getUniqueValueFromDb("SELECT has_wound FROM integrityCards WHERE card_id=$cardId LIMIT 1");

						if($hasWound == 1)
						{
								return true;
						}
						else
						{
							return false;
						}
		}

		function isCardInfected($cardId)
		{
						$hasInfection = self::getUniqueValueFromDb("SELECT has_infection FROM integrityCards WHERE card_id=$cardId LIMIT 1");

						if($hasInfection == 1)
						{
								return true;
						}
						else
						{
							return false;
						}
		}

		// Get the PLAYER ID of the player HOLDING the GUN.
		function getPlayerIdOfGunHolder($gunId)
		{
				return self::getUniqueValueFromDb("SELECT gun_held_by FROM guns WHERE gun_id=$gunId LIMIT 1");
		}

		// Get the GUN held by a specific PLAYER ID.
		function getGunIdHeldByPlayer($playerId)
		{
				$sql = "SELECT gun_id FROM guns WHERE gun_held_by='$playerId' LIMIT 1";
				$value = self::getUniqueValueFromDb($sql);
				//	throw new feException( "value:$value" );
				return $value;
		}

		function getGunTypeHeldByPlayer($playerId)
		{
				return self::getUniqueValueFromDb("SELECT gun_type FROM guns WHERE gun_held_by=$playerId LIMIT 1");
		}

		function pickUpGun($playerWhoArmed, $previousState)
		{
				$guns = $this->getNextGunAvailable(); // get the next gun available
				$isZombie = $this->isPlayerZombie($playerWhoArmed); // true if this player is a zombie
				if($isZombie)
				{ // this player is a zombie
						$guns = $this->getNextArmsAvailable(); // give them the next arms instead
				}

				if(count($guns) < 1)
				{ // no guns available
						throw new feException( "There are none available." );
				}

				self::incStat( 1, 'guns_acquired', $playerWhoArmed ); // increase end game player stat



				foreach( $guns as $gun )
				{ // go through each gun (should only be 1)

						// UPDATE THE DATABASE
						$gun_id = $gun['gun_id']; // 1, 2, 3, 4
						$gun_type = $gun['gun_type'];

						$sqlUpdate = "UPDATE guns SET ";
						$sqlUpdate .= "gun_held_by=$playerWhoArmed, gun_aimed_at='', gun_state='aimed', gun_acquired_in_state='$previousState' WHERE ";
						$sqlUpdate .= "gun_id=$gun_id";

						self::DbQuery( $sqlUpdate );

						$playerName = $this->getPlayerNameFromPlayerId($playerWhoArmed); // get name of player who armed

						self::notifyAllPlayers( 'gunPickedUp', clienttranslate( '${player_name} has armed.' ), array(
								 'player_arming' => $playerWhoArmed,
								 'gun_id' => $gun_id,
								 'gun_type' => $gun_type,
								 'player_name' => $playerName
						) );

						return $gun; // return the gun in case we need it and return just in case we have two next available guns for some strange reason
				}
		}

		function aimGun($gunHolderPlayer, $aimedAtPlayer)
		{
				$gunId = $this->getGunIdHeldByPlayer($gunHolderPlayer); // get the GUN ID this player is holding

				if(!$gunId)
				{ // they are not holding a gun
						return; // do nothing
				}

				$sqlUpdate = "UPDATE guns SET ";
				$sqlUpdate .= "gun_aimed_at=$aimedAtPlayer WHERE ";
				$sqlUpdate .= "gun_held_by=$gunHolderPlayer";

				self::DbQuery( $sqlUpdate );

				self::incStat( 1, 'guns_aimed_at_me', $aimedAtPlayer ); // increase end game player stat


				$gunType = $this->getGunTypeHeldByPlayer($gunHolderPlayer); // gun or arms

				$nameOfGunHolder = $this->getPlayerNameFromPlayerId($gunHolderPlayer);
				$nameOfGunTarget = $this->getPlayerNameFromPlayerId($aimedAtPlayer);

				// notify players individually of which gun is aimed at which player (a aimed at b)
				self::notifyAllPlayers( 'gunAimed', clienttranslate( '${player_name} has aimed at ${player_name_2}.' ), array(
												 'gunId' => $gunId,
												 'gun_type' => $gunType,
												 'player_name' => $nameOfGunHolder,
												 'player_name_2' => $nameOfGunTarget,
												 'gun_holder_id' => $gunHolderPlayer,
												 'aimed_at_id' => $aimedAtPlayer
				) );

		}

		function investigateCard($cardId, $playerInvestigating, $viewOnly)
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

						// notify the player who investigated of their new card
						$isHidden = $this->isIntegrityCardHidden($cardId); // true if this card is hidden
						$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
						$cardTypePublic = clienttranslate('Unknown');
						if(!$isHidden)
						{	// this card is not hidden
								$cardTypePublic = $cardType;
						}

						if($this->isPlayerDisguised($investigatedPlayerId))
						{ // this player is disguised
								self::notifyAllPlayers( 'disguisedInvestigation', clienttranslate( '${player_name_2} is diguised so they could not be investigated.' ), array(
									'player_name' => $investigatingPlayerName,
									'player_name_2' => $investigateePlayerName
								) );
						}
						else
						{ // target is NOT disguised

								$cardPositionText = $this->convertCardPositionToText($cardPosition);

								if($viewOnly == false)
								{ // this is a real investigation

										self::incStat( 1, 'investigations_completed', $playerInvestigating ); // increase end game player stat

										// send notification with public information that this card has been investigated
										self::notifyAllPlayers( 'investigationComplete', clienttranslate( '${player_name} investigated the ${position_text} card of ${player_name_2}.' ), array(
											'i18n' => array('position_text'),
											'player_name' => $investigatingPlayerName,
											'player_name_2' => $investigateePlayerName,
											'investigated_player_id' => $investigatedPlayerId,
											'cardPosition' => $cardPosition,
											'cardType' => $cardTypePublic,
											'playersSeen' => $listOfPlayersSeen,
											'isHidden' => $isHidden,
											'position_text' => $cardPositionText,
											'affectedByPlantedEvidence' => $this->isAffectedByPlantedEvidence($cardId),
											'affectedByDisguise' => $this->isAffectedByDisguise($cardId),
											'affectedBySurveillanceCamera' => $this->isAffectedBySurveillanceCamera($cardId)
										) );
								}

										// send notification with private information to the player who investigated
										self::notifyPlayer( $playerInvestigating, 'viewCard', clienttranslate( 'You saw their ${position_text} ${cardTypeTranslated} card.' ), array(
																				 'i18n' => array('position_text', 'cardTypeTranslated'),
																				 'investigated_player_id' => $investigatedPlayerId,
																				 'cardPosition' => $cardPosition,
																				 'cardTypeTranslated' => strtoupper($cardType),
																				 'cardType' => strtoupper($cardType),
																				 'player_name' => $investigateePlayerName,
																				 'isHidden' => $isHidden,
																				 'playersSeen' => $listOfPlayersSeen,
																				 'position_text' => $cardPositionText,
																				 'affectedByPlantedEvidence' => $this->isAffectedByPlantedEvidence($cardId),
																				 'affectedByDisguise' => $this->isAffectedByDisguise($cardId),
																				 'affectedBySurveillanceCamera' => $this->isAffectedBySurveillanceCamera($cardId)
										) );

										// notify the player being investigated exactly what they saw
										self::notifyPlayer( $investigatedPlayerId, 'iWasInvestigated', clienttranslate( '${player_name} saw your ${position_text} ${cardType} card.' ), array(
																				 'i18n' => array('position_text', 'cardType'),
																				 'cardType' => strtoupper($cardType),
																				 'player_name' => $investigatingPlayerName,
																				 'position_text' => $cardPositionText
										) );


								// if the investigated player has Surveillance camera active, reveal the card
								if($viewOnly == false && $this->hasSurveillanceCamera($investigatedPlayerId))
								{ // this is a real investigation (not just a card view) and the player investigated has survillance camera active in front of them
										$this->revealCard($investigatedPlayerId, $cardPosition);
								}

								$infectorCardId = $this->getInfectorCardId();
								if($viewOnly == false && $infectorCardId == $cardId)
								{ // the infector was investigated
//throw new feException( "infectorCardId: $infectorCardId cardId:$cardId" );
										$this->infectorFound($investigatedPlayerId, $cardPosition, $playerInvestigating);
								}
						}
				}
		}

		function infectorFound($infectorPlayerId, $infectorCardPosition, $finderOfInfectorPlayerId)
		{
				$infectorPlayerName = $this->getPlayerNameFromPlayerId($infectorPlayerId);
				$infectorCardId = $this->getCardIdFromPlayerAndPosition($infectorPlayerId, $infectorCardPosition);
				if($this->isIntegrityCardHidden($infectorCardId))
				{ // the infector card was hidden
						$this->revealCard($infectorPlayerId, $infectorCardPosition); // reveal the infector card

						self::notifyAllPlayers( 'infectorFound', clienttranslate( '${player_name} is the Infector!' ), array(
						 'player_name' => $infectorPlayerName
						) );

						if($finderOfInfectorPlayerId != $infectorPlayerId)
						{ // the infector did not reveal themself
								$this->drawEquipmentCard($finderOfInfectorPlayerId, 1); // the player investigating draws an equipment card as a reward
						}

						if($this->isPlayerZombie($infectorPlayerId))
						{ // if already a zombie

						}
						else
						{ // not yet a zombie

								$this->eliminatePlayer($infectorPlayerId); // turn into a zombie, notify everyone, reveal all cards, drop any guns

						}
				}
		}

		// This happens when someone draws an equipment card (not during a refresh).
		function drawEquipmentCard($playerDrawingId, $numberToDraw)
		{
				$drawingPlayerName = $this->getPlayerNameFromPlayerId($playerDrawingId); // name of the player drawing
				$cardId = 0;
				$cards = $this->equipmentCards->pickCards( $numberToDraw, 'deck', $playerDrawingId ); // draw a card
				foreach($cards as $card)
				{ // go through each card (should only be 1)
						$cardId = $card['id'];
						$this->setEquipmentCardOwner($card['id'], $playerDrawingId);

						$collectorNumber = $this->getCollectorNumberFromId($cardId);
						$equipName = $this->getTranslatedEquipmentName($collectorNumber);
						$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

						// send public information to everyone
						self::notifyAllPlayers( 'playerDrawsEquipmentCard', clienttranslate( '${player_name} draws Equipment.' ), array(
								 'player_name' => $drawingPlayerName,
								 'equipment_id' => $cardId,
								 'drawing_player_id' => $playerDrawingId
						) );

						// send private information only to the player who drew
						self::notifyPlayer( $playerDrawingId, 'iDrawEquipmentCard', '', array(
								 'player_name' => $drawingPlayerName,
								 'equipment_id' => $cardId,
								 'drawing_player_id' => $playerDrawingId,
								 'collector_number' => $collectorNumber,
								 'equip_name' => $equipName,
								 'equip_effect' => $equipEffect
						) );
				}

				self::incStat( 1, 'equipment_acquired', $playerDrawingId ); // increase end game player stat
		}

		// Reveal all Integrity Cards at end of game.
		function revealAllIntegrityCards()
		{
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerId = $player['player_id'];
						$hiddenCards = $this->getHiddenCardsFromPlayer($playerId);

						foreach( $hiddenCards as $integrityCard )
						{
								$card_id = $integrityCard['card_id'];
								$cardPosition = $integrityCard['card_location_arg']; // 1, 2, 3

								$this->revealCard($playerId, $cardPosition);
						}
				}
		}

		// Reveal all Equipment Cards at end of game.
		function revealAllEquipmentCards()
		{
				$equipmentCards = $this->getAllEquipmentCardsInHand();
				foreach( $equipmentCards as $equipmentCard )
				{ // go through each card in all player's hands (the face-down cards on the player board)
						$equipmentCardId = $equipmentCard['card_id'];
						$this->revealEquipmentCard($equipmentCardId);
				}
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
					$sqlUpdate .= "card_location='$playerRevealingId' AND card_location_arg=$cardPosition";

					self::DbQuery( $sqlUpdate );

					$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes

					$playerName = $this->getPlayerNameFromPlayerId($playerRevealingId); // get the player's name who is revealing the card
					$cardId = $this->getCardIdFromPlayerAndPosition($playerRevealingId, $cardPosition);
					$cardType = $this->getCardTypeFromCardId($cardId);

					// notify all players (mainly any spectators)
					self::notifyAllPlayers( 'revealIntegrityCard', clienttranslate( 'A ${card_type} card of ${player_name} has been revealed.' ), array(
									 'player_name' => $playerName,
									 'card_type' => strtoupper($cardType),
									 'card_position' => $cardPosition,
									 'revealer_player_id' => $playerRevealingId
					) );

					$this->rePlaceIntegrityCard($cardId);


					$this->setLastCardPositionRevealed($playerRevealingId, 0); // set the last card position back to default
		}

		function addInfectionTokenToCard($playerId, $integrityCardPosition)
		{
				$sqlUpdate = "UPDATE integrityCards SET ";
				$sqlUpdate .= "has_infection=1 WHERE ";
				$sqlUpdate .= "card_location='$playerId' AND card_location_arg=$integrityCardPosition";

				self::DbQuery( $sqlUpdate );
		}

		function removeWoundedToken($woundedPlayerId)
		{
				$woundedCardType = $this->getLeaderCardTypeForPlayer($woundedPlayerId); // get the card type (agent or kingpin) so we can pass it to the notification so the client knows which one to remove
				$woundedCardId = $this->getLeaderCardIdForPlayer($woundedPlayerId); // get the card ID so we can update the database
//throw new feException( "woundedCardId: $woundedCardId" );
				// reset the wounded token in the database
				$sqlUpdate = "UPDATE integrityCards SET ";
				$sqlUpdate .= "has_wound=0 WHERE ";
				$sqlUpdate .= "card_id=$woundedCardId";

				self::DbQuery( $sqlUpdate );

				$playerName = $this->getPlayerNameFromPlayerId($woundedPlayerId); // get the player's name

				// notify all players that the wounded token has been removed
				self::notifyAllPlayers( "removeWoundedToken", clienttranslate( 'The Wounded Token has been removed from ${player_name}.' ), array(
						'player_name' => $playerName,
						'woundedCardType' => $woundedCardType
				) );
		}

		/* NOT USED ANYMORE */
		function addGlowToIntegrityCard($cardId)
		{
				$playerId = $this->getIntegrityCardOwner($cardId);
				$cardPosition = $this->getIntegrityCardPosition($cardId);
				self::notifyAllPlayers( "addCardGlow", '', array(
						'player_id' => $playerId,
						'card_position' => $cardPosition
				) );
		}

		// Reset a player's hidden cards to update glow and tooltips.
		function rePlacePlayerHiddenCards($playerId)
		{
				$playerCards = $this->getIntegrityCardsForPlayer($playerId);
				foreach($playerCards as $card)
				{ // go through each of this player's cards
						$cardId = $card['card_id'];
						$isRevealed = $card['card_type_arg'];

						if($isRevealed == 0)
						{ // this card is hidden
								$this->rePlaceIntegrityCard($cardId);
						}
				}
		}

		// Reset an integrity card to update glow and tooltips.
		function rePlaceIntegrityCard($cardId)
		{
//				if(!self::isSpectator())
//				{ // this is not a spectator

						$playerId = $this->getIntegrityCardOwner($cardId); // integrity card owner
						$cardPosition = $this->getIntegrityCardPosition($cardId);
						$cardType = $this->getCardTypeFromCardId($cardId);
						$listOfPlayersSeenArray = $this->getArrayOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
						$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it
						$isHidden = $this->isIntegrityCardHidden($cardId); // true if this card is hidden

						$hasInfection = $this->isCardInfected($cardId);
						$hasWound = $this->isCardWounded($cardId); // true if this card has a wound token on it
						$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes

						foreach( $players as $player )
						{ // go through each player

								$playerAsking = $player['player_id'];
								$cardTypeMasked = $cardType; // we may need to mask the card type for this player so we don't send it to the client
								$isSeen = $this->isSeen($playerAsking, $playerId, $cardPosition); //1 if this player has seen it

								if($isSeen != 1 && ($isHidden == 1 || $isHidden == true))
								{ // the card is not seen by this player and it is hidden
										$cardTypeMasked = clienttranslate("Unknown");

								}

								self::notifyPlayer( $playerAsking, 'rePlaceIntegrityCard', '', array(
										'player_id' => $playerId,
										'card_position' => $cardPosition,
										'cardType' => $cardTypeMasked,
										'playersSeenArray' => $listOfPlayersSeenArray,
										'playersSeenList' => $listOfPlayersSeen,
										'hasInfection' => $hasInfection,
										'hasWound' => $hasWound,
										'affectedByPlantedEvidence' => $this->isAffectedByPlantedEvidence($cardId),
										'affectedByDisguise' => $this->isAffectedByDisguise($cardId),
										'affectedBySurveillanceCamera' => $this->isAffectedBySurveillanceCamera($cardId),
										'cardIsHidden' => $isHidden
								) );
						}
		}

		/* NOT USED ANYMORE */
		function reverseHonestCrooked($cardId)
		{
				$playerId = $this->getIntegrityCardOwner($cardId);
				$cardPosition = $this->getIntegrityCardPosition($cardId);

				$cardType = $this->getCardTypeFromCardId($cardId);
				if($cardType == 'honest')
				{
						$cardType = 'crooked'; // flip to crooked
				}
				else if($cardType == 'crooked' )
				{
						$cardType = 'honest'; // flip to honest
				}


				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach( $players as $player )
				{ // go through each player

						$playerAsking = $player['player_id'];

						$isSeen = $this->isSeen($playerAsking, $playerId, $cardPosition); //1 if this is seen
						$isRevealed = $this->getCardRevealedStatus($cardId); //1 if it is revealed

						if($isSeen == 0 && $isRevealed == 0)
						{ // this player has not seen this

								$cardType = ''; // do not send the card type to the client at all
						}

						//throw new feException("isSeen:$isSeen isRevealed:$isRevealed");

						self::notifyPlayer( $playerAsking, 'reverseHonestCrooked', '', array(
							'player_id' => $playerId,
						 	'card_position' => $cardPosition,
							'new_card_type' => $cardType,
							'is_seen' => $isSeen,
							'is_revealed' => $isRevealed
						) );
				}
		}

		function giveEquipmentFromOnePlayerToAnother($equipmentIdTargeted, $playerIdGivingEquipment, $playerIdGettingEquipment)
		{
				$giverName = $this->getPlayerNameFromPlayerId($playerIdGivingEquipment);
				$receiverName = $this->getPlayerNameFromPlayerId($playerIdGettingEquipment);
				$collectorNumber = $this->getCollectorNumberFromId($equipmentIdTargeted);
				$equipmentName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipmentEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				if($this->isEquipmentActive($equipmentIdTargeted))
				{ // giving ACTIVE equipment
								$this->setEquipmentCardOwner($equipmentIdTargeted, $playerIdGettingEquipment); // change the owner of the card in the database
								$this->setEquipmentPlayerTarget1($equipmentIdTargeted, $playerIdGettingEquipment); // set the target of the equipment to this player as well (I'm honestly not sure if we ever use this as the owner for active equipment)
								$numberOfActiveEquipmentReceiverHas = count($this->getActiveEquipmentCardIdsForPlayer($playerIdGettingEquipment));

								// notify this player
								self::notifyAllPlayers( 'activeEquipmentCardExchanged', clienttranslate( '${equipment_name} was held by ${player_name} is now held by ${player_name_2}.' ), array(
									'player_name' => $giverName,
									'player_name_2' => $receiverName,
									'equipment_id_moving' => $equipmentIdTargeted,
									'player_id_giving' => $playerIdGivingEquipment,
									'player_id_receiving' => $playerIdGettingEquipment,
									'collector_number' => $collectorNumber,
									'equipment_name' => $equipmentName,
									'equipment_effect' => $equipmentEffect,
									'count_active_equipment' => $numberOfActiveEquipmentReceiverHas
								) );

								// update each card to make sure things look right for things like Disguise and Surveillance Camera
								$giverIntegrityCards = $this->getIntegrityCardsForPlayer($playerIdGivingEquipment);
								foreach($giverIntegrityCards as $card)
								{ // go through each of this player's cards
										$cardId = $card['card_id'];
										//$this->reverseHonestCrooked($cardId);
										$this->rePlaceIntegrityCard($cardId);
								}

								// update each card to make sure things look right for things like Disguise and Surveillance Camera
								$receiverIntegrityCards = $this->getIntegrityCardsForPlayer($playerIdGettingEquipment);
								foreach($receiverIntegrityCards as $card)
								{ // go through each of this player's cards
										$cardId = $card['card_id'];
										//$this->reverseHonestCrooked($cardId);
										$this->rePlaceIntegrityCard($cardId);
								}
				}
				else
				{ // giving HAND equipment

								$this->setEquipmentCardOwner($equipmentIdTargeted, $playerIdGettingEquipment); // change the owner of the card in the database

								// notify this player
								self::notifyAllPlayers( 'handEquipmentCardExchanged', clienttranslate( 'The Equipment Card held by ${player_name} is now held by ${player_name_2}.' ), array(
									'player_name' => $giverName,
									'player_name_2' => $receiverName,
									'equipment_id_moving' => $equipmentIdTargeted,
									'player_id_giving' => $playerIdGivingEquipment,
									'player_id_receiving' => $playerIdGettingEquipment,
									'collector_number' => $collectorNumber,
									'equipment_name' => $equipmentName,
									'equipment_effect' => $equipmentEffect
								) );
				}
		}

		function discardEquipmentCard($equipmentCardId)
		{
				$this->equipmentCards->moveCard( $equipmentCardId, 'discard'); // move the card to the discard pile

				$equipmentCardHolderId = $this->getEquipmentCardOwner($equipmentCardId); // get the player ID of the player discarding this
				$equipmentCardName = $this->getEquipmentName($equipmentCardId); // get the name of the equipment card
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);
				$playerName = $this->getPlayerNameFromPlayerId($equipmentCardHolderId); // get the player's name who is discarding

				self::notifyAllPlayers( "discardEquipmentCard", clienttranslate( '${player_name} has put an Equipment Card in the discard pile.' ), array(
						'player_name' => $playerName,
						'equipment_id' => $equipmentCardId,
						'collector_number' => $collectorNumber,
						'player_id_discarding' => $equipmentCardHolderId
				) );

				$this->resetEquipmentAfterDiscard($equipmentCardId); // set this equipment back to defaults in the database
		}

		// Make a face-down equipment card in a player's hand (on the player board) face-up.
		function revealEquipmentCard($equipmentCardId)
		{
				$equipmentCardHolderId = $this->getEquipmentCardOwner($equipmentCardId); // get the player ID of the player discarding this
				$equipmentCardName = $this->getEquipmentName($equipmentCardId); // get the name of the equipment card
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);
				$playerName = $this->getPlayerNameFromPlayerId($equipmentCardHolderId); // get the player's name who is discarding

				$equipName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				self::notifyAllPlayers( "revealEquipmentCard", '', array(
						'player_name' => $playerName,
						'equipment_id' => $equipmentCardId,
						'collector_number' => $collectorNumber,
						'player_id' => $equipmentCardHolderId,
						'equipment_name' => $equipName,
						'equipment_effect' => $equipEffect
				) );
		}

		// Play an Equipment that is NOT active and discard it.
		function playEquipmentOnTable($cardId)
		{
				$this->equipmentCards->moveCard( $cardId, 'discard'); // move the card to the discard pile

				// notify everyone that an equipment card is now active
				$equipmentOwner = $this->getEquipmentCardOwner($cardId); // get the player ID of the player who played the equipment
				$equipmentName = $this->getEquipmentName($cardId); // get the name of this equipment
				$equipmentOwnerPlayerName = $this->getPlayerNameFromPlayerId($equipmentOwner); // name of the player being investigated
				$collectorNumber = $this->getCollectorNumberFromId($cardId);

				$equipName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				self::notifyAllPlayers( "playEquipmentOnTable", clienttranslate( '${player_name} has played ${equipment_name}.' ), array(
												 'i18n' => array( 'equipment_name', 'equipment_effect' ),
												 'player_name' => $equipmentOwnerPlayerName,
												 'equipment_name_untranslated' => $equipmentName,
						 						 'equipment_id' => $cardId,
						 						 'collector_number' => $collectorNumber,
						 						 'player_id_equipment_owner' => $equipmentOwner,
												 'equipment_name' => $equipName,
												 'equipment_effect' => $equipEffect
				) );

				$this->resetEquipmentAfterDiscard($cardId); // set this equipment back to defaults in the database
		}

		function discardActivePlayerEquipmentCard($equipmentCardId)
		{
			//throw new feException("cardId:$equipmentCardId");
				$equipmentCardHolder = $this->getEquipmentCardOwner($equipmentCardId); // get the player ID of the player discarding this
				//$equipmentCardHolder = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				$this->equipmentCards->moveCard( $equipmentCardId, 'discard'); // move the card to the discard pile

				//throw new feException( "equimentcardid: $equipmentCardId equipment_card_owner:$equipmentCardHolder" );
				$collectorNumber = $this->getCollectorNumberFromId($equipmentCardId);

				$equipName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				$playerName = $this->getPlayerNameFromPlayerId($equipmentCardHolder); // get the player's name who is dropping the gun

				// notify this player
				self::notifyAllPlayers( 'discardActivePlayerEquipmentCard', clienttranslate( '${player_name} has moved their active Equipment to the discard pile.' ), array(
								'i18n' => array( 'equipment_name', 'equipment_effect' ),
								'player_name' => $playerName,
						 		'equipment_id' => $equipmentCardId,
						 		'collector_number' => $collectorNumber,
						 		'equipment_card_owner' => $equipmentCardHolder,
								'equipment_name' => $equipName,
								'equipment_effect' => $equipEffect
				) );

				$this->resetEquipmentAfterDiscard($equipmentCardId);
		}

		function dropGun($gunId)
		{
				if(is_null($gunId) || $gunId == '')
				{ // invalid gun id
						return;
				}

				$gunHolderPlayerId = $this->getPlayerIdOfGunHolder($gunId);
				$playerName = $this->getPlayerNameFromPlayerId($gunHolderPlayerId); // get the player's name who is dropping the gun

				if($playerName == '')
				{ // could not find player ID
						return;
				}

				$gunType = 'gun'; // default to gun
				if(!is_null($gunHolderPlayerId) && $gunHolderPlayerId != '')
				{
						$gunType = $this->getGunTypeHeldByPlayer($gunHolderPlayerId); // see if this is an arm or gun
				}

				if($this->isPlayerZombie($gunHolderPlayerId))
				{ // player is a zombie

						// aim arms at self
						$this->aimGun($gunHolderPlayerId, $gunHolderPlayerId); // update the gun in the database for who it is now aimed at

				}
				else
				{ // NOT a zombie

						$sqlUpdate = "UPDATE guns SET ";
						$sqlUpdate .= "gun_aimed_at='', gun_held_by='', gun_state='center', gun_acquired_in_state='' WHERE ";
						$sqlUpdate .= "gun_id=$gunId";

						self::DbQuery( $sqlUpdate );

						if($playerName != '')
						{
								self::notifyAllPlayers( "dropGun", clienttranslate( '${player_name} is no longer armed.' ), array(
										'player_name' => $playerName,
										'gunId' => $gunId,
										'gunType' => $gunType
								) );
						}
				}
		}



		// SHOOT the target player. This is used both from shooting a gun and from shooting a player with Equipment
		// so don't do anything gun-shooting-specific in here.
		function shootPlayer($shooterPlayerId, $targetPlayerId, $gunType)
		{
				// notify players about the shooting so they can see all that player's cards, update wounded tokens, and drop the gun (maybe notify them of which team that player is on and whether they are a Leader)
				$targetName = $this->getPlayerNameFromPlayerId($targetPlayerId);

				$teamOfShooter = $this->getPlayerTeam($shooterPlayerId);
				$teamOfTarget = $this->getPlayerTeam($targetPlayerId);

				$gunId = $this->getGunIdHeldByPlayer($shooterPlayerId);
				$this->setGunShotThisTurn($gunId, 1); // update the database so we know this gun was shot this turn (to know if a zombie was shot and we must reaim)

				if($gunType == 'arm')
				{ // BITE
						self::notifyAllPlayers( "executeGunShoot", clienttranslate( '${player_name} has been bitten.' ), array(
								'player_name' => $targetName
						) );

						// STATS
						self::incStat( 1, 'players_bitten', $shooterPlayerId ); // increase end game player stat
						self::incStat( 1, 'bites_taken', $targetPlayerId ); // increase end game player stat


						$this->addInfectionToken($targetPlayerId, true); // add a infection token (if they don't already have 3)
						$this->rollZombieDice($shooterPlayerId, $targetPlayerId); // roll a zombie die for each infection token they have and take action for each zombie die face
				}
				else
				{ // SHOT WITH GUN OR EQUIPMENT
						self::notifyAllPlayers( "executeGunShoot", clienttranslate( '${player_name} has been shot.' ), array(
								'player_name' => $targetName
						) );

						// STATS
						if($teamOfShooter == $teamOfTarget)
						{
								self::incStat( 1, 'teammates_shot', $shooterPlayerId ); // increase end game player stat
						}
						else
						{
								self::incStat( 1, 'opponents_shot', $shooterPlayerId ); // increase end game player stat
						}
						self::incStat( 1, 'bullets_taken', $targetPlayerId ); // increase end game player stat


						$isTargetALeader = $this->isPlayerALeader($targetPlayerId); // see if the player shot was a LEADER
						$isTargetWounded = $this->isPlayerWounded($targetPlayerId); // see if the player is WOUNDED

						// check for game over
						if($isTargetALeader && $isTargetWounded)
						{ // if you're shooting a wounded leader, the game ends
								$playerTeam = $this->getPlayerTeam($targetPlayerId); // get the eliminated leader's team
								$winningTeam = 'crooked'; // default assuming agent was eliminated
								if($playerTeam == 'crooked')
								{ // the kingpin was eliminated
										$winningTeam = 'honest'; // honest wins
								}

								$this->endGameCleanup('team_win', $winningTeam);

								$this->gamestate->nextState( "endGame" );
						}
						else
						{ // the game is not ending

								if($isTargetALeader)
								{ // a Leader is being shot for the first time

										// give the target a wounded token
										$this->woundPlayer($targetPlayerId); // wound the player

										$this->setAllPlayerIntegrityCardsToRevealed($targetPlayerId); // reveal all of the target's cards in the database

										$this->drawEquipmentCard($targetPlayerId, 1); // getting wounded gives you a free equipment card
								}
								else
								{ // a non-Leader is being shot

										if($this->getPlayerRole($targetPlayerId) == 'zombie_infector')
										{ // the infector was revealed
												$infectorCardId = $this->getInfectorCardId();
												$infectorIntegrityCardPosition = $this->getIntegrityCardPosition($infectorCardId);
												$this->infectorFound($targetPlayerId, $infectorIntegrityCardPosition, $shooterPlayerId);
										}
										else
										{

										}

										$this->eliminatePlayer($targetPlayerId); // eliminate this player
								}
						}
				}
		}

		// $winType = 'team_win', 'solo_win'
		// $leader = the ELIMINATED leader in a team win and the WINNING leader in a solo win.
		function endGameCleanup($winType, $winningTeam)
		{
			$this->revealAllIntegrityCards();
			$this->revealAllEquipmentCards();

			$this->awardEndGamePoints($winType, $winningTeam); // award end game points
			$this->countPlayersOnTeams('end'); // update stats on how many were on each team at the end of the game
		}

		// $winningTeam = either the team who wins (honest, crooked, zombie) or, for solo, the player ID who won
		function awardEndGamePoints($endType, $winningTeam)
		{
				if($endType == 'solo_win')
				{
						$sqlAll = "UPDATE player SET player_score='1' WHERE player_id=$winningTeam"; // update score in the database
						self::DbQuery( $sqlAll );
				}
				elseif($endType == 'team_win')
				{
							$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes

							foreach( $players as $player )
							{ // go through each player

									$thisPlayerId = $player['player_id'];
									$playerTeam = $this->getPlayerTeam($thisPlayerId); // get this player's team

									if($playerTeam == $winningTeam)
									{ // this player WON
											$sqlAll = "UPDATE player SET player_score='1' WHERE player_id=$thisPlayerId"; // update score in the database
											self::DbQuery( $sqlAll );
									}
									else
									{ // this player LOST

									}
							}
				}
		}

		function woundPlayer($playerId)
		{
				$woundedCardId = $this->getLeaderCardIdForPlayer($playerId); // get the card ID so we can pass it to the notification so the client knows which one to remove

				$sqlUpdate = "UPDATE integrityCards SET ";
				$sqlUpdate .= "has_wound=1 WHERE ";
				$sqlUpdate .= "card_id=$woundedCardId";

				self::DbQuery( $sqlUpdate );

				// NOTIFY EACH PLAYER
				$playerName = $this->getPlayerNameFromPlayerId($playerId);

				$leaderCardPosition = $this->getLeaderCardPositionFromPlayer($playerId); // 1, 2, 3
				$cardType = $this->getCardTypeFromPlayerIdAndPosition($playerId, $leaderCardPosition);

				// notify everyone
				self::notifyAllPlayers( 'woundPlayer', clienttranslate( '${player_name} has been wounded.' ), array(
							'player_name' => $playerName,
						  'player_id' => $playerId,
						  'leader_card_position' => $leaderCardPosition,
						  'player_id_of_leader_holder' => $playerId,
						  'card_type' => $cardType
				) );
		}

		// Turn into a zombie and notify all players (but do not drop guns or reveal cards).
		function zombifyPlayer($playerId)
		{
				$sqlUpdate = "UPDATE player SET ";
				$sqlUpdate .= "is_zombie=1 WHERE ";
				$sqlUpdate .= "player_id=$playerId";

				self::DbQuery( $sqlUpdate );

				// NOTIFY ALL PLAYERS
				$playerName = $this->getPlayerNameFromPlayerId($playerId);
				self::notifyAllPlayers( 'zombifyPlayer', clienttranslate( '${player_name} has turned into a zombie.' ), array(
									 'player_name' => $playerName,
									 'zombie_player_id' => $playerId
				) );
		}

		function toggleSkipEquipmentReactions($playerId, $isChecked)
		{
				$newValue = "0"; // stop skipping them
				$message = clienttranslate("When opponents play Equipment, you WILL now be asked if you want to react with your Equipment, as long as your Equipment can legally be played.");
				if($isChecked)
				{ // we are currently skipping them
						$newValue = "1"; // default to start skipping
						$message = clienttranslate("When opponents play Equipment, you will NOT be asked if you want to react with Equipment.");
				}

				if(!self::isSpectator())
				{ // this is not a spectator

						// update the database
						$sqlUpdate = "UPDATE player SET ";
						$sqlUpdate .= "skip_equipment_reactions=$newValue WHERE ";
						$sqlUpdate .= "player_id=$playerId";
						self::DbQuery( $sqlUpdate );

						self::notifyPlayer( $playerId, 'toggleChanged', $message, array(

						) );
				}
		}

		function eliminatePlayer($playerId)
		{
				$targetName = $this->getPlayerNameFromPlayerId($playerId);
				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
				{ // we are using the zombies expansion

						if($this->isPlayerZombie($playerId))
						{ // if already a zombie

								// notify everyone
								self::notifyAllPlayers( "executeGunShootsZombie", clienttranslate( '${player_name} was already a zombie.' ), array(
										'player_name' => $targetName
								) );

								// aim arms at self if they have any
								$this->aimGun($playerId, $playerId); // update the gun in the database for who it is now aimed at
						}
						else
						{ // not yet a zombie

								// discard guns they were holding
								$guns = $this->getGunsHeldByPlayer($playerId);
								foreach( $guns as $gun )
								{ // go through each gun (should only be 1)
										$gunId = $gun['gun_id'];
										$this->dropGun($gunId);
										//throw new feException( "dropped gun $gunId");
								}

								$this->zombifyPlayer($playerId); // update DB that they are a zombie and notify everyone (but do not drop guns or reveal cards)

								//$countguns = count($guns);
								//throw new feException( "count guns:$countguns");

								$this->pickUpGun($playerId, $this->getStateName()); // pick up arms

								// aim arms at self if they have any
								$this->aimGun($playerId, $playerId); // update the gun in the database for who it is now aimed at
						}
				}
				else
				{ // we are NOT using the zombies expansion
						$sqlUpdate = "UPDATE player SET ";
						$sqlUpdate .= "is_eliminated=1 WHERE ";
						$sqlUpdate .= "player_id=$playerId";

						self::DbQuery( $sqlUpdate );

						// NOTIFY ALL PLAYERS
						$playerName = $this->getPlayerNameFromPlayerId($playerId);
						self::notifyAllPlayers( 'eliminatePlayer', clienttranslate( '${player_name} has been eliminated.' ), array(
											 'player_name' => $playerName,
											 'eliminated_player_id' => $playerId
						) );

						self::notifyPlayer( $playerId, 'youAreEliminatedMessage', clienttranslate( 'You are eliminated and will not take turns until someone brings you back to life but you will still win if your team wins.' ), array(
						) );


						// discard equipment cards they were holding
						$equipmentCards = $this->getEquipmentInPlayerHand($playerId);
						foreach( $equipmentCards as $equipmentCard )
						{ // go through each card (should only be 1)
								$equipmentCardId = $equipmentCard['card_id'];
								$this->discardEquipmentCard($equipmentCardId);
						}

						// discard any active equipment in front of them
						/*
						$playersActiveEquipmentCards = $this->getPlayersActiveEquipment($playerId);
						foreach( $playersActiveEquipmentCards as $activeEquipmentCard )
						{ // go through each card
								$activeEquipmentCardId = $activeEquipmentCard['card_id'];
								$this->discardEquipmentCard($activeEquipmentCardId);
						}
						*/

						// discard guns they were holding
						$guns = $this->getGunsHeldByPlayer($playerId);
						foreach( $guns as $gun )
						{ // go through each gun (should only be 1)
								$gunId = $gun['gun_id'];
								$this->dropGun($gunId);
						}
				}

				$this->setAllPlayerIntegrityCardsToRevealed($playerId); // reveal all of the target's cards in the database

				// re-place integrity cards at the end to correctly show their status, including Planted Evidence and Surveillance Camera and Disguise
				$playerCards = $this->getIntegrityCardsForPlayer($playerId);
				foreach($playerCards as $card)
				{ // go through each of this player's cards
						$cardId = $card['card_id'];
						//$this->reverseHonestCrooked($cardId);
						$this->rePlaceIntegrityCard($cardId);
				}
		}

		function revivePlayer($playerId)
		{
			if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2)
			{ // we are using the zombies expansion and the Infector is hidden

						$zombieSqlUpdate = "UPDATE player SET ";
						$zombieSqlUpdate .= "is_zombie=0 WHERE ";
						$zombieSqlUpdate .= "player_id=$playerId";

						self::DbQuery( $zombieSqlUpdate );

						// NOTIFY EACH PLAYER
						$playerName = $this->getPlayerNameFromPlayerId($playerId);

						self::notifyAllPlayers( 'revivePlayer', clienttranslate( '${player_name} is no longer a zombie.' ), array(
											 'player_name' => $playerName,
											 'eliminated_player_id' => $playerId
						) );

						// discard guns they were holding
						$guns = $this->getGunsHeldByPlayer($playerId);
						foreach( $guns as $gun )
						{ // go through each gun (should only be 1)
								$gunId = $gun['gun_id'];
								$this->dropGun($gunId);
						}
				}
				else
				{
						$sqlUpdate = "UPDATE player SET ";
						$sqlUpdate .= "is_eliminated=0 WHERE ";
						$sqlUpdate .= "player_id=$playerId";

						self::DbQuery( $sqlUpdate );

						// NOTIFY EACH PLAYER
						$playerName = $this->getPlayerNameFromPlayerId($playerId);

						self::notifyAllPlayers( 'revivePlayer', clienttranslate( '${player_name} has been revived.' ), array(
											 'player_name' => $playerName,
											 'eliminated_player_id' => $playerId
						) );
				}
		}

		function getPlayerBoardEquipmentList()
		{
				$result = array();

				$arrayIndex = 0;
				$equipmentInHands = $this->getAllEquipmentInHands(); // get all the equipment in player hands
				foreach( $equipmentInHands as $cardInHand )
				{ // go through each card
						$equipmentId = $cardInHand['card_id'];
						$ownerId = $cardInHand['equipment_owner'];

						$result[$arrayIndex] = array(); // create an array for this equipment
						$result[$arrayIndex]['equipmentOrCollectorId'] = $equipmentId;
						$result[$arrayIndex]['ownerId'] = $ownerId;
						$result[$arrayIndex]['isActive'] = false;

						$arrayIndex++;
				}

				$activeEquipment = $this->getAllPlayerBoardActiveEquipmentCards(); // get all the equipment players have active targeting them
				foreach( $activeEquipment as $activeCard)
				{ // go through each card
						$collectorNumber = $activeCard['card_type_arg'];
						$ownerId = $activeCard['equipment_owner'];

						$result[$arrayIndex] = array(); // create an array for this equipment
						$result[$arrayIndex]['equipmentOrCollectorId'] = $collectorNumber;
						$result[$arrayIndex]['ownerId'] = $ownerId;
						$result[$arrayIndex]['isActive'] = true;

						$arrayIndex++;
				}


				return $result;
		}

		function getPlayerTurnDiscardToDiscardButtonList($isPlayerTurn)
		{
				$result = array();
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				if(!$isPlayerTurn)
				{ // they are being asked to discard on another player's turn because they were given an equipment card
						$playerWhoseTurnItIs = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				}

				$buttonIdentifier = 0;
				$equipmentCards = $this->getEquipmentInPlayerHand($playerWhoseTurnItIs);
				foreach( $equipmentCards as $equipmentCard )
				{ // go through each card (should only be 1)
						$equipmentId = $equipmentCard['card_id'];
						$equipmentName = $equipmentCard['equipment_name'];

						//$buttonLabel = "Discard $equipmentName";
						$buttonLabel = sprintf( self::_("Discard %s"), $equipmentName );
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
				$buttonIdentifier = 0;

				$result[$buttonIdentifier] = array(); // create a new array for this player
				$result[$buttonIdentifier]['buttonLabel'] = clienttranslate('Investigate');
				$result[$buttonIdentifier]['hoverOverText'] = '';
				$result[$buttonIdentifier]['actionName'] = 'Investigate';
				$result[$buttonIdentifier]['equipmentId'] = '';
				$result[$buttonIdentifier]['makeRed'] = false;
				if($this->canPlayerInvestigate($playerWhoseTurnItIs) && !$this->isPlayerZombie($playerWhoseTurnItIs))
				{ // this player can investigate
						$result[$buttonIdentifier]['isDisabled'] = false;
				}
				else
				{ // this player is a zombie or cannot investigate
						$result[$buttonIdentifier]['isDisabled'] = true;
				}

				$buttonIdentifier++;

				$result[$buttonIdentifier] = array(); // create a new array for this player
				$result[$buttonIdentifier]['buttonLabel'] = clienttranslate('Equip');
				$result[$buttonIdentifier]['hoverOverText'] = '';
				$result[$buttonIdentifier]['actionName'] = 'Equip';
				$result[$buttonIdentifier]['equipmentId'] = '';
				$result[$buttonIdentifier]['makeRed'] = false;
				$result[$buttonIdentifier]['isDisabled'] = false; // this is never disabled

				$buttonIdentifier++;

				if($this->isPlayerZombie($playerWhoseTurnItIs))
				{ // this player is a zombie
						$result[$buttonIdentifier] = array(); // create a new array for this player
						$result[$buttonIdentifier]['buttonLabel'] = clienttranslate('Bite');
						$result[$buttonIdentifier]['hoverOverText'] = '';
						$result[$buttonIdentifier]['actionName'] = 'Shoot';
						$result[$buttonIdentifier]['equipmentId'] = '';
						$result[$buttonIdentifier]['makeRed'] = false;
						if($this->canPlayerBite($playerWhoseTurnItIs))
						{ // this player can bite
								$result[$buttonIdentifier]['isDisabled'] = false;

								$gunId = $this->getGunIdHeldByPlayer($playerWhoseTurnItIs);
								$gunTargetPlayerId = $this->getPlayerIdOfGunTarget($gunId);
								$gunTargetName = $this->getPlayerNameFromPlayerId($gunTargetPlayerId);
								//throw new feException( "Gun Target Name: $gunTargetName");
								//$result[3]['buttonLabel'] = "Shoot $gunTargetName"; // add the name of the player you're shooting
								$result[$buttonIdentifier]['buttonLabel'] = sprintf( self::_("Bite %s"), $gunTargetName ); // add the name of the player you're shooting
						}
						else {
								$result[$buttonIdentifier]['isDisabled'] = true;
						}
						$buttonIdentifier++;
				}
				else
				{ // they are NOT a zombie

						$result[$buttonIdentifier] = array(); // create a new array for this player
						$result[$buttonIdentifier]['buttonLabel'] = clienttranslate('Arm');
						$result[$buttonIdentifier]['hoverOverText'] = '';
						$result[$buttonIdentifier]['actionName'] = 'Arm';
						$result[$buttonIdentifier]['equipmentId'] = '';
						$result[$buttonIdentifier]['makeRed'] = false;
						if($this->canPlayerArm($playerWhoseTurnItIs))
						{ // this player can arm
								$result[$buttonIdentifier]['isDisabled'] = false;
						}
						else {
								$result[$buttonIdentifier]['isDisabled'] = true;
						}
						$buttonIdentifier++;

						$result[$buttonIdentifier] = array(); // create a new array for this player
						$result[$buttonIdentifier]['buttonLabel'] = clienttranslate('Shoot');
						$result[$buttonIdentifier]['hoverOverText'] = '';
						$result[$buttonIdentifier]['actionName'] = 'Shoot';
						$result[$buttonIdentifier]['equipmentId'] = '';
						$result[$buttonIdentifier]['makeRed'] = false;
						if($this->canPlayerShoot($playerWhoseTurnItIs))
						{ // this player can shoot
								$result[$buttonIdentifier]['isDisabled'] = false;

								$gunId = $this->getGunIdHeldByPlayer($playerWhoseTurnItIs);
								$gunTargetPlayerId = $this->getPlayerIdOfGunTarget($gunId);
								$gunTargetName = $this->getPlayerNameFromPlayerId($gunTargetPlayerId);
								//throw new feException( "Gun Target Name: $gunTargetName");
								//$result[3]['buttonLabel'] = "Shoot $gunTargetName"; // add the name of the player you're shooting
								$result[$buttonIdentifier]['buttonLabel'] = sprintf( self::_("Shoot %s"), $gunTargetName ); // add the name of the player you're shooting
						}
						else {
								$result[$buttonIdentifier]['isDisabled'] = true;
						}
						$buttonIdentifier++;
				}


				$equipmentCards = $this->getEquipmentInPlayerHand($playerWhoseTurnItIs);
				foreach( $equipmentCards as $equipmentCard )
				{ // go through each card (should only be 1)
						$equipmentId = $equipmentCard['card_id'];
						$equipmentName = $equipmentCard['equipment_name'];

						//$buttonLabel = "Use $equipmentName";
						$buttonLabel = sprintf( self::_("Use %s"), $equipmentName );
						if($this->validateEquipmentUsage($equipmentId, $playerWhoseTurnItIs, true))
						{ // we CAN use this now
								$isDisabled = false;
						}
						else
						{ // we cannot use this now
								$isDisabled = true;
						}
						$hoverOverText = ''; // hover over text or '' if we don't want a hover over
						$actionName = 'PauseToUseEquipment'; // shoot, useEquipment
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
				$result[$buttonIdentifier]['buttonLabel'] = clienttranslate('Skip My Turn');
				$result[$buttonIdentifier]['hoverOverText'] = '';
				$result[$buttonIdentifier]['actionName'] = 'SkipMyTurn';
				$result[$buttonIdentifier]['equipmentId'] = '';
				$result[$buttonIdentifier]['makeRed'] = true; // make this one red
				$result[$buttonIdentifier]['isDisabled'] = false; // this is never disabled

				return $result;
		}

		function getGunTargets($mustReaim)
		{
				$result = array();

				$allPlayers = self::getObjectListFromDB( "SELECT *
																					 FROM player" );

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				// 2022/07/24: switched to getActivePlayerId because otherwise using Weapon Crate to get a gun doesn't allow you to target the player whose turn it is
				//$activePlayerId = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)

//throw new feException( "activePlayerId:$activePlayerId" );
				$gunId = $this->getGunIdHeldByPlayer($activePlayerId);
				//$sql = "SELECT gun_id FROM guns WHERE gun_held_by='$activePlayerId'";
				//$value = self::getUniqueValueFromDb($sql);
//throw new feException( "gunId:$gunId" );
				$currentTarget = $this->getPlayerIdOfGunTarget($gunId);

				// create an array for each player with display information
				foreach( $allPlayers as $player )
				{
						$playerId = $player['player_id'];
						if($mustReaim && $currentTarget == $playerId)
						{ // do not allow them to keep current aim

						}
						else
						{ // they can keep their aim at this player

								if($activePlayerId != $playerId && !$this->isPlayerEliminated($playerId))
								{ // don't include yourself or dead players

									  $result[$playerId] = array(); // create a new array for this player
										$result[$playerId]['player_id'] = $player['player_id']; // put this player ID into the subarray
										$result[$playerId]['player_name'] = $player['player_name']; // put this player name into the subarray
										$result[$playerId]['player_letter'] = $this->getLetterOrderFromPlayerIds($activePlayerId, $playerId); // get the order around the table for this player from the asking player's perspective

								}
						}
				}
//$countTargets = count($result);
//throw new feException( "Count:$countTargets" );
				return $result;
		}

		function getPlayerButtonTargets()
		{
				$result = array();

				$allPlayers = self::getObjectListFromDB( "SELECT *
																					 FROM player" );

				// 2022/07/24: switched to getGameStateValue("CURRENT_PLAYER") because otherwise using Taser on your turn doesn't let you target the last active player
				$activePlayerId = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
			  //$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				if(!$activePlayerId)
				{ // we didn't get a valid activePlayerId
						$activePlayerId = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				}
				//throw new feException( "activePlayerId:$activePlayerId" );

				$equipmentId = $this->getEquipmentCardIdInUse();
				if($equipmentId == null || $equipmentId == '')
				{ // no equipment card in use
						foreach( $allPlayers as $player )
						{
								$playerId = $player['player_id'];

								$result[$playerId] = array(); // create a new array for this player
								$result[$playerId]['player_id'] = $player['player_id']; // put this player ID into the subarray
								$result[$playerId]['player_name'] = $player['player_name']; // put this player name into the subarray
								$result[$playerId]['player_letter'] = $this->getLetterOrderFromPlayerIds($activePlayerId, $playerId); // get the order around the table for this player from the asking player's perspective
						}
				}
				else
				{ // there is an equipment card in use
									// create an array for each player with display information
									foreach( $allPlayers as $player )
									{
											$playerId = $player['player_id'];
											if($this->validateEquipmentPlayerSelection($playerId, $equipmentId, false))
											{
													$result[$playerId] = array(); // create a new array for this player
													$result[$playerId]['player_id'] = $player['player_id']; // put this player ID into the subarray
													$result[$playerId]['player_name'] = $player['player_name']; // put this player name into the subarray
													$result[$playerId]['player_letter'] = $this->getLetterOrderFromPlayerIds($activePlayerId, $playerId); // get the order around the table for this player from the asking player's perspective
											}
									}

				}


				return $result;
		}

		function resolveEquipment($equipmentId)
		{
				$collectorNumber = $this->getCollectorNumberFromId($equipmentId); // get the type of equipment card we're using
				$equipmentCardOwner = $this->getEquipmentCardOwner($equipmentId); // get the player ID who is playing the equipment card

				$equipmentCardName = $this->getEquipmentName($equipmentId); // get the name of the equipment card
				$equipName = $this->getTranslatedEquipmentName($collectorNumber);
				$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumber);

				if(!$this->isEquipmentTargetsChosenByOtherPlayer($collectorNumber))
				{ // the target(s) are NOT chosen by another player because we already told them this card was being played and we don't want to tell them twice
						self::notifyAllPlayers( "resolveEquipment", clienttranslate( '${player_name} is playing ${equipment_name}: ${equip_effect}' ), array(
										'i18n' => array( 'equipment_name', 'equip_effect' ),
										'equipment_name' => $equipmentCardName,
										'equip_name' => $equipName,
										'equip_effect' => $equipEffect,
										'player_name' => self::getActivePlayerName()
						) );
				}

//throw new feException( "Resolve $collectorNumber" );
				// switch statement
				switch($collectorNumber)
				{
						case 2: // coffee
							//throw new feException( "Resolve Coffee" );
							$target1 = $this->getPlayerTarget1($equipmentId);
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

							// send client notification that each card has been reversed
							$playerCards = $this->getIntegrityCardsForPlayer($target1);
							foreach($playerCards as $card)
							{ // go through each of this player's cards
									$cardId = $card['card_id'];
									//$this->reverseHonestCrooked($cardId);
									$this->rePlaceIntegrityCard($cardId);
							}

							self::notifyPlayer( $target1, 'plantedEvidenceMessage', clienttranslate( 'Your HONEST cards are now CROOKED and your CROOKED cards are HONEST.' ), array(
							) );

							$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
							$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was

						break;
						case 12: // smoke grenade
							//throw new feException( "Resolve Smoke Grenade" );

							$this->equipmentCards->moveCard( $equipmentId, 'center'); // move the card to the center
							$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)

							// make it active
							$this->makePlayerEquipmentActive($equipmentId, $playerWhoseTurnItWas); // activate this in the middle of the table

							$isClockwise = $this->isTurnOrderClockwise();

							// update the turn marker arrow to go in the correct direction
							self::notifyAllPlayers( "updateTurnMarker", clienttranslate( 'THE TURN ORDER IS NOW REVERSED.' ), array(
									'current_player_id' => $this->getGameStateValue("CURRENT_PLAYER"),
									'is_clockwise' => $isClockwise,
									'current_player_name' => $this->getCurrPlayerName(),
									'next_player_name' => $this->getNextPlayerName()
							) );

							$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was

						break;
						case 15: // truth serum
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								if($target1 != '')
								{ // a card was selected
										$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
										$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted

										$infectorCardId = $this->getInfectorCardId();
										if($infectorCardId == $target1)
										{ // they found the infector
												$this->infectorFound($integrityCardOwner, $cardPosition, $equipmentCardOwner);
										}

										$this->revealCard($integrityCardOwner, $cardPosition); // set the selected integrity card to revealed

										//$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
										//$this->setStateAfterTurnAction($activePlayerId); // see which state we go into after completing this turn action
								}

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;
						case 16: // wiretap
								//throw new feException( "Resolve Wiretap" );

								// investigate card 1
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								if($target1 != '')
								{
										$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
										$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted
										$cardId = $this->getCardIdFromPlayerAndPosition($integrityCardOwner, $cardPosition);
										$this->investigateCard($cardId, $equipmentCardOwner, false); // investigate this card and notify players
								}


								// investigate card 2
								$target2 = $this->getEquipmentTarget2($equipmentId); // get the selected integrity card
								if($target2 != '')
								{
									  $integrityCardOwner2 = $this->getIntegrityCardOwner($target2); // get the player who owns the integrity card targeted
										$cardPosition2 = $this->getIntegrityCardPosition($target2); // get the position of the integrity card targeted
										$cardId2 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner2, $cardPosition2);
										$this->investigateCard($cardId2, $equipmentCardOwner, false); // investigate this card and notify players
								}


								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved
						break;

						case 44: // riot shield
								$target2 = $this->getPlayerTarget2($equipmentId); // get player target 2
								$playerShooting = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->aimGun($playerShooting, $target2); // update the gun in the database for who it is now aimed at

								$this->makePlayerEquipmentActive($equipmentId, $target2); // activate this card

								if($this->countPlayersWhoCanUseEquipment() > 0)
								{ // if there are any players who can use equipment (it will double-shoot in cases where no one has active equipment)
										$this->setEquipmentHoldersToActive("askShootReaction"); // set anyone holding equipment to active so they can react after this equipment was used
								}
						break;

						case 11: // restraining order
								$target2 = $this->getPlayerTarget2($equipmentId); // get player target 2
								$playerShooting = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerShooting ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was when the equipment was played

								$this->aimGun($playerShooting, $target2); // update the gun in the database for who it is now aimed at

								$this->makePlayerEquipmentActive($equipmentId, $target2); // activate this card

								if($this->countPlayersWhoCanUseEquipment() > 0)
								{ // if there are any players who can use equipment (it will double-shoot in cases where no one has active equipment)
										$this->setEquipmentHoldersToActive("askShootReaction"); // set anyone holding equipment to active so they can react after this equipment was used
								}
						break;

						case 37: // mobile detonator
								$target1 = $this->getPlayerTarget1($equipmentId); // get player target 1
								$this->shootPlayer($target1, $target1, 'equipment'); // shoot that player //TODO: THIS CANNOT HAPPEN NOW...IT HAS TO HAPPEN AFTER THE SHOT EXECUTES (IF IT STILL EXECUTES AFTER OTHERS HAVE HAD A CHANCE TO PLAY OTHER EQUIPMENT)


								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;

						case 4: // evidence bag

								$equipmentIdTargeted = $this->getEquipmentTarget1($equipmentId); // get the ID of the targeted equipment
								$playerIdGivingEquipment = $this->getEquipmentCardOwner($equipmentIdTargeted); // get the ID of the player giving the equipment
								$playerIdGettingEquipment = $this->getPlayerTarget1($equipmentId); // get the player it is being given to

								$this->giveEquipmentFromOnePlayerToAnother($equipmentIdTargeted, $playerIdGivingEquipment, $playerIdGettingEquipment); // make the equipment owner the new player

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;

						case 35: // med kit
								$target1 = $this->getPlayerTarget1($equipmentId); // get player target 1
								$this->removeWoundedToken($target1); // discard the wounded token this player has

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
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

								$previousState = $this->getEquipmentPlayedInState($equipmentId); // get the state this equipment was played in so we can go back to it after the player aims
								$gun = $this->pickUpGun($equipmentCardOwner, $previousState); // allow the equipment card user to pick up the gun (we can't just take the existing gun held by the other player because it might be zombie arms when we're not a zombie)

								$newGunId = $gun['gun_id'];
								$this->setGunCanShoot($newGunId, 0); // make sure player cannot shoot the gun this turn

						break;

						case 3: // Defibrillator
								$targetPlayer = $this->getPlayerTarget1($equipmentId); // get the targeted player
								$this->revivePlayer($targetPlayer); // bring this player back to life

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved
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
								$this->investigateCard($cardId2, $integrityCardOwner2, true); // let the new owner investigate this card and notify players
								$this->investigateCard($cardId, $integrityCardOwner, true); // let the new owner investigate this card and notify players

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;
						case 30: // Disguise
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}
								$this->makePlayerEquipmentActive($equipmentId, $target1); // activate this card
								$this->rePlacePlayerHiddenCards($target1); // make this player's hidden cards glow

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
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
								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved
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
												$this->investigateCard($card['card_id'], $target1, false);
								}

								// allow equipment owner look at all of target's cards
								$hiddenCardsOfTarget = $this->getHiddenCardsFromPlayer($target1);
								foreach($hiddenCardsOfTarget as $card)
								{
												$this->investigateCard($card['card_id'], $equipmentCardOwner, false);
								}

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;
						case 13: // Surveillance Camera
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}
								$this->makePlayerEquipmentActive($equipmentId, $target1); // activate this card
								$this->rePlacePlayerHiddenCards($target1); // make this player's hidden cards glow

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;
						case 7: // Metal Detector
								$target1 = $this->getEquipmentTarget1($equipmentId);
								if(!is_null($target1) && $target1 != '')
								{
										$this->investigateCard($target1, $equipmentCardOwner, false); // investigate this card
								}

								$target2 = $this->getEquipmentTarget2($equipmentId);
								if(!is_null($target2) && $target2 != '')
								{
										$this->investigateCard($target2, $equipmentCardOwner, false); // investigate this card
								}

								$target3 = $this->getEquipmentTarget3($equipmentId);
								if(!is_null($target3) && $target3 != '')
								{
										$this->investigateCard($target3, $equipmentCardOwner, false); // investigate this card
								}

								$target4 = $this->getEquipmentTarget4($equipmentId);
								if(!is_null($target4) && $target4 != '')
								{
										$this->investigateCard($target4, $equipmentCardOwner, false); // investigate this card
								}

								$target5 = $this->getEquipmentTarget5($equipmentId);
								if(!is_null($target5) && $target5 != '')
								{
										$this->investigateCard($target5, $equipmentCardOwner, false); // investigate this card
								}

								$target6 = $this->getEquipmentTarget6($equipmentId);
								if(!is_null($target6) && $target6 != '')
								{
										$this->investigateCard($target6, $equipmentCardOwner, false); // investigate this card
								}

								$target7 = $this->getEquipmentTarget7($equipmentId);
								if(!is_null($target7) && $target7 != '')
								{
										$this->investigateCard($target7, $equipmentCardOwner, false); // investigate this card
								}


								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved
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
								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved
						break;

						case 60: // Crossbow
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}

								$this->shootPlayer($equipmentCardOwner, $target1, 'equipment'); // shoot the player
								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

						break;

						case 61: // Transfusion Tube
								$target1 = $this->getEquipmentTarget1($equipmentId);
								$target2 = $this->getEquipmentTarget2($equipmentId);
								if(!is_null($target1) && $target1 != '' && !is_null($target2) && $target2 != '')
								{
										$this->moveInfectionToken($target1, $target2); // move this infection token
								}

								$target3 = $this->getEquipmentTarget3($equipmentId);
								$target4 = $this->getEquipmentTarget4($equipmentId);
								if(!is_null($target3) && $target3 != '' && !is_null($target4) && $target4 != '')
								{
										$this->moveInfectionToken($target3, $target4); // move this infection token
								}

								$target5 = $this->getEquipmentTarget5($equipmentId);
								$target6 = $this->getEquipmentTarget6($equipmentId);
								if(!is_null($target5) && $target5 != '' && !is_null($target6) && $target6 != '')
								{
										$this->moveInfectionToken($target5, $target6); // move this infection token
								}

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved
						break;

						case 62: // Zombie Serum
							$infectionDiceRolled = $this->getInfectionDiceRolled();
							$numberInfectionDiceRolled = count($infectionDiceRolled);

							$zombieDiceRolled = $this->getZombieDiceRolled();
							$numberZombieDiceRolled = count($zombieDiceRolled);

							$playerRolling = 'unknown'; // this will be set later

							if($numberZombieDiceRolled > 0)
							{ // we're re-rolling ZOMBIE dice

									$rollerPlayerId = '';
									$targetPlayerId = '';
									foreach($zombieDiceRolled as $die)
									{ // go through each zombie die
											$playerRolling = $die['roller_player_id'];
											$targetPlayerId = $die['target_player_id'];
									}

									//throw new feException( "rollingPlayerId: $rollerPlayerId" );

									$playerName = $this->getPlayerNameFromPlayerId($playerRolling);
									self::notifyAllPlayers( 'reRollingDice', clienttranslate( '${player_name} must re-roll.' ), array(
														 'player_name' => $playerName
									) );

									$this->rollZombieDice($playerRolling, $targetPlayerId); // call rollZombieDice
							}
							else
							{ // we're re-rolling INFECTION die

									$infectionDice = $this->getInfectionDiceRolled();
									foreach($infectionDice as $die)
									{ // go through each die (should just be 1)
											$cardPositionRemoving = $die['card_position_infected'];
											$playerRolling = $die['roller_player_id'];

											$playerName = $this->getPlayerNameFromPlayerId($playerRolling);
											self::notifyAllPlayers( 'reRollingDice', clienttranslate( '${player_name} must re-roll.' ), array(
																 'player_name' => $playerName
											) );

											if($cardPositionRemoving != 0)
											{ // a token was added for this roll
													$this->removeInfectionToken($playerRolling, $cardPositionRemoving, true); // remove the infection token (we don't need to do this for zombie dice)
											}
									}

									$this->setGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN", 0); // reset whether we rolled the infection die this turn
							}

							$this->makePlayerEquipmentActive($equipmentId, $playerRolling); // activate this card

						break;
						case 63: // Flamethrower
								$allZombies = $this->getAllZombies();
								foreach($allZombies as $zombie)
								{
										$playerId = $zombie['player_id'];
										$this->shootPlayer($equipmentCardOwner, $playerId, 'equipment');
								}

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved
						break;

						case 64: // Chainsaw
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
								$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted

								$infectorCardId = $this->getInfectorCardId();
								if($infectorCardId == $target1)
								{ // they found the infector
										$infectorIntegrityCardPosition = $this->getIntegrityCardPosition($infectorCardId);
										$this->infectorFound($integrityCardOwner, $infectorIntegrityCardPosition, $equipmentCardOwner);
								}

								$this->revealCard($integrityCardOwner, $cardPosition); // set the selected integrity card to revealed
								$cardType = $this->getCardTypeFromPlayerIdAndPosition($integrityCardOwner, $cardPosition); // agent, kingpin, honest, crooked

								if($this->isIntegrityCardALeader($integrityCardOwner, $cardPosition))
								{ // a leader card was revealed
										$this->shootPlayer($equipmentCardOwner, $equipmentCardOwner, 'equipment'); // equipment owner is shot
								}
								else
								{ // a NON-leader card was revealed
										$this->shootPlayer($equipmentCardOwner, $integrityCardOwner, 'equipment'); // integrity card owner is shot
								}

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

						break;

						case 65: // Zombie Mask
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
								$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted
								$cardId = $this->getCardIdFromPlayerAndPosition($integrityCardOwner, $cardPosition);

								$target2 = $this->getEquipmentTarget2($equipmentId); // get the selected integrity card
								$integrityCardOwner2 = $this->getIntegrityCardOwner($target2); // get the player who owns the integrity card targeted
								$cardPosition2 = $this->getIntegrityCardPosition($target2); // get the position of the integrity card targeted
								$cardId2 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner2, $cardPosition2);

								$this->swapIntegrityCards($cardId, $cardId2); // swap the owners of the two integrity cards
								$this->investigateCard($cardId2, $integrityCardOwner, true); // let the new owner investigate this card and notify players
								$this->investigateCard($cardId, $integrityCardOwner2, true); // let the new owner investigate this card and notify players

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

								$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
								$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						break;

						case 66: // Machete
								// swap 1 for 2
								$target1 = $this->getEquipmentTarget1($equipmentId); // get the selected integrity card
								$target2 = $this->getEquipmentTarget2($equipmentId); // get the selected integrity card

								if($target1 && $target2)
								{ // at least 2 cards were selected

										$integrityCardOwner = $this->getIntegrityCardOwner($target1); // get the player who owns the integrity card targeted
										$cardPosition = $this->getIntegrityCardPosition($target1); // get the position of the integrity card targeted
										$cardId = $this->getCardIdFromPlayerAndPosition($integrityCardOwner, $cardPosition);

										$integrityCardOwner2 = $this->getIntegrityCardOwner($target2); // get the player who owns the integrity card targeted
										$cardPosition2 = $this->getIntegrityCardPosition($target2); // get the position of the integrity card targeted
										$cardId2 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner2, $cardPosition2);

										$this->swapIntegrityCards($cardId, $cardId2); // swap the owners of the two integrity cards
										$this->investigateCard($cardId2, $integrityCardOwner, true); // let the new owner investigate this card and notify players
										$this->investigateCard($cardId, $integrityCardOwner2, true); // let the new owner investigate this card and notify players

								}


								// swap 3 for 4
								$target3 = $this->getEquipmentTarget3($equipmentId); // get the selected integrity card
								$target4 = $this->getEquipmentTarget4($equipmentId); // get the selected integrity card

								if($target3 && $target4)
								{ // at least 4 cards were selected

										$integrityCardOwner3 = $this->getIntegrityCardOwner($target3); // get the player who owns the integrity card targeted
										$cardPosition3 = $this->getIntegrityCardPosition($target3); // get the position of the integrity card targeted
										$cardId3 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner3, $cardPosition3);

										$integrityCardOwner4 = $this->getIntegrityCardOwner($target4); // get the player who owns the integrity card targeted
										$cardPosition4 = $this->getIntegrityCardPosition($target4); // get the position of the integrity card targeted
										$cardId4 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner4, $cardPosition4);

										$this->swapIntegrityCards($cardId3, $cardId4); // swap the owners of the two integrity cards
										$this->investigateCard($cardId4, $integrityCardOwner3, true); // let the new owner investigate this card and notify players
										$this->investigateCard($cardId3, $integrityCardOwner4, true); // let the new owner investigate this card and notify players
								}

								// swap 5 for 6
								$target5 = $this->getEquipmentTarget5($equipmentId); // get the selected integrity card
								$target6 = $this->getEquipmentTarget6($equipmentId); // get the selected integrity card

								if($target5 && $target6)
								{ // at least 4 cards were selected

										$integrityCardOwner5 = $this->getIntegrityCardOwner($target5); // get the player who owns the integrity card targeted
										$cardPosition5 = $this->getIntegrityCardPosition($target5); // get the position of the integrity card targeted
										$cardId5 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner5, $cardPosition5);

										$integrityCardOwner6 = $this->getIntegrityCardOwner($target6); // get the player who owns the integrity card targeted
										$cardPosition6 = $this->getIntegrityCardPosition($target6); // get the position of the integrity card targeted
										$cardId6 = $this->getCardIdFromPlayerAndPosition($integrityCardOwner6, $cardPosition6);

										$this->swapIntegrityCards($cardId5, $cardId6); // swap the owners of the two integrity cards
										$this->investigateCard($cardId6, $integrityCardOwner5, true); // let the new owner investigate this card and notify players
										$this->investigateCard($cardId5, $integrityCardOwner6, true); // let the new owner investigate this card and notify players
								}

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

						break;

						case 67: // Weapon Crate
								$armedInfectedPlayers = $this->getArmedInfectedPlayers(); // get all players holding a gun with an infection token

								// unaim their guns
								foreach($armedInfectedPlayers as $player)
								{
										$armedPlayerId = $player['player_id'];
										$guns = $this->getGunsHeldByPlayer($armedPlayerId);

										foreach( $guns as $gun )
										{ // go through each gun (should only be 1)
												$gunId = $gun['gun_id'];

												// set their gun's gun_aimed_at to ''
												$sqlUpdate = "UPDATE guns SET ";
												$sqlUpdate .= "gun_aimed_at='' WHERE ";
												$sqlUpdate .= "gun_id=$gunId";
												self::DbQuery( $sqlUpdate );
										}
								}


								$unarmedInfectedPlayers = $this->getUnarmedInfectedPlayers(); // get each unarmed player with an infection token

								// give them a gun
								foreach($unarmedInfectedPlayers as $player)
								{
										$armerPlayerId = $player['player_id'];
										$gun = $this->pickUpGun($armerPlayerId, $this->getStateName());
								}

						break;

						case 68: // Alarm Clock
								$target1 = $this->getPlayerTarget1($equipmentId);
								if($target1 == '')
								{
										throw new BgaUserException( self::_("We do not have a valid target for this Equipment.") );
								}

								$this->addInfectionToken($target1, true); // give them an infection token

								$armedZombies = $this->getAllArmedZombies(); // get all zombies who are armed
								foreach($armedZombies as $zombie)
								{
										$zombiePlayerId = $zombie['player_id'];
										$this->aimGun($zombiePlayerId, $target1); // they aim at the target
								}

								$this->playEquipmentOnTable($equipmentId); // discard the equipment card now that it is resolved

						break;

						default:
							throw new feException( "Unknown equipment: $collectorNumber" );
						break;
				}

				self::incStat( 1, 'equipment_used', $equipmentCardOwner ); // increase end game player stat

				// notify players of exactly which card was played now that it has been resolved
				$equipmentCardName = $this->getEquipmentName($equipmentId); // get the name of the equipment card
				self::notifyAllPlayers( "resolvedEquipment", clienttranslate( '${equipment_name} is now resolved.' ), array(
						'i18n' => array( 'equipment_name' ),
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

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				$this->gamestate->nextState( "investigateChooseCard" ); // go to the state allowing the active player to choose a card to investigate
		}

		// The active player selected an action but now would like to cancel it and choose a new action.
		function clickedCancelButton()
		{
				self::checkAction( 'clickCancelButton' ); // make sure we can take this action from this state

				$equipmentId = $this->getEquipmentCardIdInUse();

				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "chooseIntegrityCards" ||
					 $stateName == "choosePlayer" ||
					 $stateName == "chooseAnotherPlayer" ||
					 $stateName == "chooseActiveOrHandEquipmentCard" )
				{ // we're using an equipment card that requires choosing integrity cards
						$previousState = $this->getEquipmentPlayedInState($equipmentId);	// go to the saved state for this equipment card


						$this->gamestate->nextState( $previousState ); // TODO: THE TRANSITION PROBABLY DOESN'T MATCH THE STATE NAME SO CHANGE $previousState TO GO TO THE CORRECT TRANSITION BASED ON THE NAME or update states.inc.php with a transition matching the name
				}
				elseif($stateName == "chooseEquipmentToPlayReactEndOfTurn" ||
							 $stateName == "chooseEquipmentToPlayReactInvestigate" ||
							 $stateName == "chooseEquipmentToPlayReactShoot" ||
							 $stateName == "chooseEquipmentToPlayReactBite" )
				{ // cancelling using equipment
						$this->gamestate->nextState( "cancelEquipmentUse" ); // this will send us back to the correct state no matter whether it is from a investigate/shoot/end of turn reaction
				}
				else
				{
						$this->gamestate->nextState( "playerAction" ); // go back to start of turn
				}

				$this->resetEquipmentCardAfterCancel($equipmentId); // since we had set this equipment up in a playing state, we need to reset it now that it's back in hand and not being played (this MUST come before nextState)

		}

		function clickedDoneSelectingButton()
		{
				self::checkAction( 'clickDoneSelectingButton' ); // make sure we can take this action from this state

				$equipmentId = $this->getEquipmentCardIdInUse();
				$this->setDoneSelecting($equipmentId, 1); // signify that we are done selecting targts for this equipment card

				$this->setStateForEquipment($equipmentId); // put us in the correct next state
		}

		function getIntegrityCardDetails($playerPosition, $cardPosition)
		{
				$playerAsking = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				if(!self::isSpectator())
				{ // this is not a spectator
						$integrityCardOwner = $this->getPlayerIdFromLetterOrder($playerAsking, $playerPosition); // get the player ID of the player being investigated
						$cardId = $this->getIntegrityCardId($integrityCardOwner, $cardPosition); // get the unique id for this integrity card

						$isSeen = $this->isSeen($playerAsking, $integrityCardOwner, $cardPosition); //1 if this player has seen it
						$isHidden = $this->isIntegrityCardHidden($cardId); // true if this card is hidden
						$cardType = $this->getCardTypeFromCardId($cardId);
						$listOfPlayersSeen = $this->getListOfPlayersWhoHaveSeenCard($cardId); // get the list of players who have seen this card or "all" if all have seen it or "none" if none have seen it

						if($isSeen != 1 && $isHidden)
						{
								$cardType = clienttranslate("Unknown");
						}

						self::notifyPlayer( $playerAsking, 'integrityCardDetails', '', array(
							'player_id' => $playerAsking,
							'card_position' => $cardPosition,
							'card_type' => $cardType,
							'is_seen' => $isSeen,
							'is_hidden' => $isHidden,
							'seen_by_list' => $listOfPlayersSeen,
							'affectedByPlantedEvidence' => $this->isAffectedByPlantedEvidence($cardId),
							'affectedByDisguise' => $this->isAffectedByDisguise($cardId),
							'affectedBySurveillanceCamera' => $this->isAffectedBySurveillanceCamera($cardId)
						) );
				}
		}

		function clickedOpponentIntegrityCard($playerPosition, $cardPosition)
		{
				self::checkAction( 'clickOpponentIntegrityCard' ); // make sure we can take this action from this state
//throw new feException( "clicked opponent integrity card" );
				$playerAsking = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				$integrityCardOwner = $this->getPlayerIdFromLetterOrder($playerAsking, $playerPosition); // get the player ID of the player being investigated
				$integrityCardId = $this->getIntegrityCardId($integrityCardOwner, $cardPosition); // get the unique id for this integrity card

				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "chooseCardToInvestigate")
				{ // if we're in chooseCardToInvestigate state, investigate

						$isValid = $this->validateInvestigatePlayer($playerAsking, $integrityCardOwner); // throw an error if this investigation is invalid

						$isSeen = $this->isSeen($playerAsking, $integrityCardOwner, $cardPosition);
						$isRevealed = $this->getCardRevealedStatus($integrityCardId); //1 if it is revealed

						if($isSeen != 0 || $isRevealed == 1)
						{ // hey... this card has already been seen or is revealed
								throw new BgaUserException( self::_("You can only investigate hidden cards.") );
						}


						if($this->skipInvestigateReactions())
						{
							$this->attemptInvestigation($playerAsking, $integrityCardOwner, $integrityCardId, false); // begin an investigation
								$this->gamestate->nextState( "executeActionInvestigate" ); // go straight to executing the investigation

						}
						else
						{ // allow reactions to the investigation
							$this->attemptInvestigation($playerAsking, $integrityCardOwner, $integrityCardId, true); // notify players that there is an investigation attempt in progress
								$this->setEquipmentHoldersToActive("askInvestigateReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState( "askInvestigateReaction" ); // go to the state allowing the active player to choose a card to investigate

						}
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
//throw new feException( "set state" );
				}
				else
				{
					throw new feException( "Unexpected state name: ".$stateName );
				}
		}

		function clickedMyIntegrityCard($cardPosition)
		{
				self::checkAction( 'clickMyIntegrityCard' ); // make sure we can take this action from this state

				$playerRevealing = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				$this->setLastCardPositionRevealed($playerRevealing, $cardPosition); // save which card was revealed until while we wait for players to react with equipment
				$integrityCardId = $this->getIntegrityCardId($playerRevealing, $cardPosition); // get the unique id for this integrity card

				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "chooseCardToRevealForEquip")
				{
						if($this->getCardRevealedStatus($integrityCardId) == 1)
						{ // card not hidden
							throw new BgaUserException( self::_("Please choose a hidden card.") );
						}

						$this->gamestate->nextState( "executeEquip" ); // go to the state where they will draw their equipment card
				}
				elseif($stateName == "chooseCardToRevealForArm")
				{ // execute arm
						if($this->getCardRevealedStatus($integrityCardId) == 1)
						{ // card not hidden
							throw new BgaUserException( self::_("Please choose a hidden card.") );
						}

						$this->gamestate->nextState( "executeArm" ); // go to the state where they will pick up their gun
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

		function attemptInvestigation($playerAsking, $integrityCardOwner, $integrityCardId, $notifyPlayers)
		{
				$playerNameInvestigating = $this->getPlayerNameFromPlayerId($playerAsking);
				$playerNameTarget = $this->getPlayerNameFromPlayerId($integrityCardOwner);
				$cardPositionTargeted = $this->getIntegrityCardPosition($integrityCardId);

				if($notifyPlayers)
				{
						self::notifyAllPlayers( "investigationAttempt", clienttranslate( '${player_name} is attempting to investigate ${player_name_2}.' ), array(
								'player_name' => $playerNameInvestigating,
								'player_name_2' => $playerNameTarget,
								'player_id_investigated' => $integrityCardOwner,
								'card_position_targeted' => $cardPositionTargeted
						) );
				}

				$this->setLastPlayerInvestigated($playerAsking, $integrityCardOwner); // set player.last_player_investigated so we can resolve the investigation after players react with equipment
				$this->setLastCardPositionInvestigated($playerAsking, $cardPositionTargeted); // set player.last_card_position_investigated so we can resolve the investigation after players react with equipment
		}



		function clickedArmButton()
		{
				self::checkAction( 'clickArmButton' ); // make sure we can take this action from this state

				$guns = $this->getNextGunAvailable(); // get the next gun available
				if(!$guns || count($guns) < 1)
				{ // there are no guns available
						throw new BgaUserException( self::_("All guns have been taken. Please choose a different action.") );
				}

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				$hiddenCards = $this->getHiddenCardsFromPlayer($activePlayerId); // get all this player's hidden integrity cards

				if(count($hiddenCards) > 0)
				{ // they have at least one hidden card
									//throw new feException("armChooseCard");
						$this->gamestate->nextState( "armChooseCard" ); // go to the state allowing the active player to choose a card to reveal for arm
				}
				else
				{
						//throw new feException("executeArm");
						$this->gamestate->nextState( "executeArm" ); // go to the state where they will pick up their gun
				}
		}

		function clickedToggle($htmlIdOfToggle, $isChecked)
		{
				$currentPlayerId = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				$this->toggleSkipEquipmentReactions($currentPlayerId, $isChecked);
		}

		function clickedPlayer($playerPosition, $playerId)
		{
				self::checkAction( 'clickPlayer' ); // make sure we can take this action from this state
				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "askAim" || $stateName == "askAimOutOfTurn" || $stateName == "askAimMustReaim")
				{ // we chose a player to aim at
						$gunHolderPlayer = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
						$aimedAtPlayer = $this->getPlayerIdFromLetterOrder($gunHolderPlayer, $playerPosition); // get the player ID of the player being aimed at


						$this->aimGun($gunHolderPlayer, $aimedAtPlayer); // update the gun in the database for who it is now aimed at
//throw new feException( "rolledvalue:".$this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") );
//throw new feException( "isinfectorhidden:".$this->isInfectorHidden() );
						if($stateName == "askAimOutOfTurn")
						{ // we're choosing our aim potentially out of turn because we just got a gun from an equipment
//throw new feException( "asking" );
								$this->gamestate->nextState("afterAimedOutOfTurn"); // possibly change the active player
						}
						else
						{ // the usual case
								if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
								{ // we are using the zombies expansion and the Infector is hidden

									//throw new feException( "rolling infection" );
										$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
								}
								else
								{
										$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
										//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
								}
						}
				}
				elseif($stateName == "choosePlayer" || $stateName == "chooseAnotherPlayer" || $stateName == "choosePlayerNoCancel")
				{ // we chose a player to target with equipment

						$equipmentCardId = $this->getEquipmentCardIdInUse();

						$isValid = $this->validateEquipmentPlayerSelection($playerId, $equipmentCardId, true); // see if this selection is valid for this equipment card

						if($isValid)
						{
							  $this->setEquipmentPlayerTarget($equipmentCardId, $playerId); // set this as a target for the equipment card
						}

						// validate to see if we are ready to execute the equipment
						$this->setStateForEquipment($equipmentCardId);
				}

//				throw new feException("after clicked player");
		}

		function clickedShootButton()
		{
				self::checkAction( 'clickShootButton' ); // make sure we can take this action from this state
				$gunHolderPlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				$gunId = $this->getGunIdHeldByPlayer($gunHolderPlayerId); // get the gun ID this player is holding
				$gunType = $this->getGunTypeHeldByPlayer($gunHolderPlayerId); // gun or arm

				if($this->getGunCanShoot($gunId) == 0)
				{
						throw new BgaUserException( self::_("You cannot shoot this turn.") );
				}

				$this->setGunState($gunId, 'shooting');
				$targetPlayerId = $this->getPlayerIdOfGunTarget($gunId); // get the player ID
				$targetName = $this->getPlayerNameFromPlayerId($targetPlayerId); // convert the player ID in to a player NAME

				if($gunType == 'arm')
				{ // zombie arms

						// notify players that player A is attempting to shoot player B
						self::notifyAllPlayers( "shootAttempt", clienttranslate( '${player_name} is attempting to bite ${target_name}!' ), array(
								'target_name' => $targetName,
								'player_name' => self::getActivePlayerName(),
								'gunId' => $gunId
						) );
				}
				else
				{ // gun

						// notify players that player A is attempting to shoot player B
						self::notifyAllPlayers( "shootAttempt", clienttranslate( '${player_name} is attempting to shoot ${target_name}!' ), array(
								'target_name' => $targetName,
								'player_name' => self::getActivePlayerName(),
								'gunId' => $gunId
						) );
				}

				$this->setEquipmentHoldersToActive("askShootReaction"); // set anyone holding equipment to active
				//$this->gamestate->nextState( "askShootReaction" ); // go to the state allowing the active player to choose a card to investigate
		}

		function clickedEquipButton()
		{
				self::checkAction( 'clickEquipButton' ); // make sure we can take this action from this state

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
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
					 $stateName == "askInvestigateReaction" ||
					 $stateName == "askBiteReaction")
				{ // multiactive state
						$currentPlayerId = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
						//throw new feException( "state is $stateName and current player is $currentPlayerId" );
						$this->gamestate->changeActivePlayer( $currentPlayerId ); // set the player using the equipment to the active player (this cannot be done in an activeplayer game state)
//throw new feException( "player $currentPlayerId is now the active player." );
						$this->gamestate->nextState( "useEquipment" ); // they are already active so just go to the state where they will use their equipment
				}
		}

		// Called when clicking on a card in hand to play or a card on a player board to target it.
		function clickedEquipmentCard($cardIdClicked, $equipmentType)
		{
				self::checkAction( 'clickEquipmentCard' ); // make sure we can take this action from this state

				$activePlayerId = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				$collectorNumberClicked = $cardIdClicked;
				if($equipmentType == 'hand' || $equipmentType == 'equipment')
				{ // clicked on a card in a player's hand (either on board or large player hand)
						$collectorNumberClicked = $this->getCollectorNumberFromId($cardIdClicked);
				}

				$equipmentIdClicked = $cardIdClicked;
				if($equipmentType == 'active')
				{ // clicked on an active card on the player board
						$equipmentIdClicked = $this->getEquipmentIdFromCollectorNumber($cardIdClicked);
				}

				$equipmentCardInUse = $this->getEquipmentCardIdInUse(); // see if there is an equipment card in use


//throw new feException( "clickedMyEquipmentCard stateName:$stateName currentPlayerId:$currentPlayerId collectorNumber:$collectorNumber" );
				$stateName = $this->getStateName(); // get the name of the current state
				if($stateName == "discardEquipment")
				{ // we have clicked on this Equipment to discard it
						$this->discardEquipmentCard($equipmentIdClicked);

						$this->setStateAfterTurnAction($activePlayerId); // see which state we go into after completing this turn action
				}
				elseif($stateName == "discardOutOfTurn")
				{ // we are discarding potentially out of turn
						$this->discardEquipmentCard($equipmentIdClicked);

						$this->gamestate->nextState( "afterDiscardedOutOfTurn" ); // go to a "game" state where we can figure out who's supposed to go next since this was potentially done out of turn order
				}
				elseif($stateName == "askEndTurnReaction" ||
					 $stateName == "askShootReaction" ||
					 $stateName == "askInvestigateReaction" ||
					 $stateName == "askBiteReaction")
				{ // the player clicked the equipment card before the Use Equipment button
						throw new BgaUserException( self::_("Please click the Use Equipment button first.") );
				}
				elseif($stateName == "chooseActiveOrHandEquipmentCard")
				{ // we have clicked on this Equipment to target it (like when Evidence Bag is played)

						$equipmentIdInUse = $this->getEquipmentCardIdInUse(); // get the ID of the equipment card being used
						$isValid = $this->validateEquipmentEquipmentSelection($equipmentIdInUse, $equipmentIdClicked); // a valid target was clicked

						if($isValid)
						{ // this is a valid target

								$this->setEquipmentCardTarget($equipmentIdInUse, $equipmentIdClicked); // set the target1 of the equipment card in use to this equipment card chosen
								$this->gamestate->nextState( "chooseAnotherPlayer" ); // after choosing which equipment to target with Evidence Bag, we want to choose a player (if we use this state for other equipment cards, we may need choose the next state differently)
						}
						else
						{
								$this->gamestate->nextState( "chooseActiveOrHandEquipmentCard" ); // stay in this state
						}
				}
				else
				{ // we have clicked on this equipment to use it

						if(!$this->validateEquipmentUsage($equipmentIdClicked, $activePlayerId, true))
						{ // we cannot use this equipment right now
								throw new BgaUserException( self::_("You cannot use this Equipment right now.") );
						}

						$equipmentCardName = $this->getEquipmentName($equipmentIdClicked); // get the name of the equipment card
						$equipName = $this->getTranslatedEquipmentName($collectorNumberClicked);
						$equipEffect = $this->getTranslatedEquipmentEffect($collectorNumberClicked);
						$equipmentIdInUse = $this->getEquipmentCardIdInUse(); // get the ID of the equipment card being used
						$collectorNumberClicked = $this->getCollectorNumberFromId($equipmentIdClicked);
						$extraDescriptionText = $this->getExtraDescriptionTextForEquipment($collectorNumberClicked);
//throw new feException( "description: ".$extraDescriptionText );

						if($this->isEquipmentTargetsChosenByOtherPlayer($collectorNumberClicked))
						{ // the target(s) are chosen by another player so we need to tell them exactly which card was played
								self::notifyAllPlayers( "playEquipment", clienttranslate( '${player_name} is playing ${equipment_name}: ${equip_effect}' ), array(
												'i18n' => array( 'equipment_name','equip_effect' ),
												'equipment_name' => $equipmentCardName,
												'equip_name' => $equipName,
												'equip_effect' => $equipEffect,
												'player_id_playing_equipment' => $activePlayerId,
												'collector_number' => $collectorNumberClicked,
												'equipment_id' => $equipmentIdClicked,
												'player_name' => self::getActivePlayerName(),
												'descriptionText' => $extraDescriptionText,
												'reveal_card' => true
								) );
						}
						else
						{ // the targets were chosen by the player playing the equipment so we don't need to tell everyone which card they are planning to play until they select targets (in case they cancel)
								self::notifyAllPlayers( "playEquipment", '', array(
												'i18n' => array( 'equipment_name' ),
												'equipment_name' => $equipmentCardName,
												'equip_name' => $equipName,
												'equip_effect' => $equipEffect,
												'player_id_playing_equipment' => $activePlayerId,
												'collector_number' => $collectorNumberClicked,
												'equipment_id' => $equipmentIdClicked,
												'player_name' => self::getActivePlayerName(),
												'descriptionText' => $extraDescriptionText,
												'reveal_card' => false
								) );
						}

						// save the state name for this equipment so we know where to go back afterwards
						$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
						$this->setEquipmentCardState($playerWhoseTurnItIs, $equipmentIdClicked, $stateName); // set this equipment to being PLAYING

						// send us to the state that will ask for input or move forward to playing
						//$equipmentId = $this->getEquipmentCardIdInUse();
						$this->setStateForEquipment($equipmentIdClicked); // see which state we should move to next
//throw new feException( "after set state" );
				}
		}


		// All players have passed on using their equipment. This could be during any of the equipment reaction states.
		function passOnUseEquipment()
		{
				self::checkAction( 'clickPassOnUseEquipmentButton' ); // make sure we can take this action from this state

				$currentPlayerId = self::getCurrentPlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				// Make this player unactive now
				// (and tell the machine state to use transtion "directionsChosen" if all players are now unactive
				$this->gamestate->setPlayerNonMultiactive( $currentPlayerId, "allPassedOnReactions" );
		}

		// The active player is asking to end their turn.
		function clickedEndTurnButton()
		{
				self::checkAction( 'clickEndTurnButton' ); // make sure we can take this action from this state

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
				{ // we are using the zombies expansion and the Infector is hidden
						$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
				}
				else
				{
						$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
						//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
				}
		}

		function clickedSkipButton()
		{
				self::checkAction( 'clickSkipButton' ); // make sure we can take this action from this state

				self::notifyAllPlayers( "skipMyTurn", clienttranslate( '${player_name} is not taking an action on their turn.' ), array(
						'player_name' => self::getActivePlayerName()
				) );

				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
				{ // we are using the zombies expansion and the Infector is hidden
						$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
				}
				else
				{
						$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
						//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
				}
		}

		// This is called in a "game" state after someone picks up a gun from an Equipment card and then they aim it. We need to figure out which state we need
		// to go back to and which player is active.
		function afterAimedOutOfTurn()
		{
			//throw new feException( "afteraimedoutofturn" );
				$unaimedGuns = $this->getHeldUnaimedGuns(); // see if we have an unaimed guns held by a player
				$countOfUnaimedGuns = count($unaimedGuns);
				if($countOfUnaimedGuns > 0)
				{ // there IS another unaimed gun

						$firstUnaimedGun = array_values($unaimedGuns)[0]; // get the first unaimed gun
						$gunId = $firstUnaimedGun['gun_id'];

						$gunHolder = $this->getPlayerIdOfGunHolder($gunId); // get the player holding the unaimed gun

						$this->gamestate->changeActivePlayer($gunHolder); // make that player active so they can aim it

						$this->gamestate->nextState( "askAimOutOfTurn" );
				}
				else
				{ // the one we just aimed was the only unaimed gun

					//throw new feException( "afterAimedOutOfTurn" );
						$activePlayerId = self::getActivePlayerId(); // get the current active player who just aimed... Current Player = player who played the current player action (the one who made the AJAX request). Active Player = player whose turn it is.
						$gunId = $this->getGunIdHeldByPlayer($activePlayerId); // get the gun that was just aimed

						$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active because someone might be aiming after picking up a gun off-turn)
						$this->gamestate->changeActivePlayer( $playerWhoseTurnItIs ); // change to the player it's turn it's supposed to be

//						$gunAcquiredInState = $this->getGunAcquiredInState($gunId); // see which state the gun was acquired in
						$equipmentIdInUse = $this->getEquipmentCardIdInUse(); // this will only be used from an equipment so get the state that equipment was used in to know where to put us into afterwards
						$equipmentUsedInState = $this->getEquipmentPlayedInState($equipmentIdInUse);
//throw new feException( "activePlayerId:$activePlayerId equipmentIdInUse:$equipmentIdInUse equipmentUsedInState:$equipmentUsedInState playerWhoseTurnItIs:$playerWhoseTurnItIs" );
						if($equipmentUsedInState == "chooseEquipmentToPlayOnYourTurn")
						{
								$this->gamestate->nextState("playerTurn"); // go back to that state
						}
						elseif($equipmentUsedInState == "chooseEquipmentToPlayReactInvestigate")
						{
								$this->setEquipmentHoldersToActive("askInvestigateReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState("askInvestigateReaction"); // go back to that state
						}
						elseif($equipmentUsedInState == "chooseEquipmentToPlayReactShoot")
						{
								$this->setEquipmentHoldersToActive("askShootReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState("askShootReaction"); // go back to that state
						}
						elseif($equipmentUsedInState == "chooseEquipmentToPlayReactBite")
						{
								$this->setEquipmentHoldersToActive("askBiteReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState("askBiteReaction"); // go back to that state
						}
						elseif($equipmentUsedInState == "chooseEquipmentToPlayReactEndOfTurn")
						{
								if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
								{ // we are using the zombies expansion and the Infector is hidden
										$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
								}
								else
								{
										$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
										//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
								}
						}
						else
						{ // we shouldn't get here
								if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
								{ // we are using the zombies expansion and the Infector is hidden
										$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
								}
								else
								{
										$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
										//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
								}
						}

						$this->discardCardsBeingPlayed(); // discard any cards in play (like Weapon Crate after everyone has aimed or Taser after they have aimed)
				}

		}

		// This is called in a "game" state after someone draws an Equipment card out of turn and they are over their hand limit. We need to figure out which state we need
		// to go back to and which player is active.
		function afterDiscardedOutOfTurn()
		{
			//throw new feException( "afterDiscardedOutOfTurn" );

				$playedInState = $this->getEvidenceBagPlayedInState(); // find the state we need to return to after that equipment is resolve

				// figure out who the next player should be
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active because someone might be aiming after picking up a gun off-turn)
				$this->gamestate->changeActivePlayer( $playerWhoseTurnItIs ); // change to the player it's turn it's supposed to be

				if($playedInState == "chooseEquipmentToPlayOnYourTurn")
				{
						$this->gamestate->nextState("playerTurn"); // go back to that state
				}
				elseif($playedInState == "chooseEquipmentToPlayReactInvestigate")
				{
						$this->setEquipmentHoldersToActive("askInvestigateReaction"); // set anyone holding equipment to active
						//$this->gamestate->nextState("askInvestigateReaction"); // go back to that state
				}
				elseif($playedInState == "chooseEquipmentToPlayReactShoot")
				{
						$this->setEquipmentHoldersToActive("askShootReaction"); // set anyone holding equipment to active
						//$this->gamestate->nextState("askShootReaction"); // go back to that state
				}
				elseif($playedInState == "chooseEquipmentToPlayReactBite")
				{
						$this->setEquipmentHoldersToActive("askBiteReaction"); // set anyone holding equipment to active
						//$this->gamestate->nextState("askBiteReaction"); // go back to that state
				}
				elseif($playedInState == "chooseEquipmentToPlayReactEndOfTurn")
				{
						if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
						{ // we are using the zombies expansion and the Infector is hidden
								$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
						}
						else
						{
								$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
						}
				}
				else
				{ // we shouldn't get here
						if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
						{ // we are using the zombies expansion and the Infector is hidden
								$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
						}
						else
						{
								$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
						}
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
		function argGetGunTargets()
		{
				return array(
						'validPlayers' => self::getGunTargets(false)
				);
		}

		function argGetGunTargetsMustReaim()
		{
				return array(
						'validPlayers' => self::getGunTargets(true)
				);
		}

		function argGetPlayerButtonTargets()
		{
				return array(
						'validPlayers' => self::getPlayerButtonTargets()
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

		function argGetPlayerBoardEquipment()
		{
				return array(
						'playerBoardEquipmentList' => self::getPlayerBoardEquipmentList()
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
				//$this->setAllPlayersInactive();

				// notify all players the turn has ended to they can remove highlights
				self::notifyAllPlayers( "endTurn", clienttranslate( '${player_name} has ended their turn.' ), array(
						'player_name' => self::getActivePlayerName()
				) );

				$this->setGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN", 0); // reset whether we rolled the infection die this turn

				// set the current active player to the one whose turn it should be right now
				$playerWhoseTurnItWas = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				//throw new feException( "currentPlayer:$playerWhoseTurnItWas" );
				$this->gamestate->changeActivePlayer( $playerWhoseTurnItWas ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was

				$guns = $this->getAllGuns(); // get the guns that are currently shooting (should just be 1)
				foreach( $guns as $gun )
				{ // go through each gun
						$gunId = $gun['gun_id'];

						$this->setGunCanShoot($gunId, 1); // make sure it can shoot in case taser was used on it this turn
						$this->setGunShotThisTurn($gunId, 0); // reset it so we know it did not fire this turn
				}

				if($this->isCoffeeActive())
				{ // coffee is active so we need to go to a specific player's turn
						$coffeeId = $this->getEquipmentIdFromCollectorNumber(2);
						$coffeeOwnerId = $this->getEquipmentCardOwner($coffeeId);
						//throw new feException( "coffeeOwnerId:$coffeeId coffeeOwnerId:$coffeeOwnerId" );
						$this->gamestate->changeActivePlayer( $coffeeOwnerId ); // make coffee owner go next
						//throw new feException( "player $coffeeOwnerId is now the active player." );
				}
				else
				{ // coffee is NOT active so we can act normally
						if($this->isTurnOrderClockwise())
						{ // the turn order is going clockwise
								$this->activeNextPlayer(); // go to the next player clockwise in turn order
						}
						else
						{ // the turn order is going counter-clockwise
							//throw new feException( "counterclockwise." );
								$this->activePrevPlayer(); // go to the next player counter-clockwise in turn order
						}
				}

				$this->discardSingleActiveTurnCards(); // discard any cards that are active for a turn (coffee, restraining order, riot shield, etc.)

				$newActivePlayer = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.
				$this->setGameStateValue("CURRENT_PLAYER", $newActivePlayer);
//if($newActivePlayer != '2342822')
//		throw new feException("setting newActivePlayer:$newActivePlayer");

				if($this->isPlayerEliminated($newActivePlayer))
				{ // skip the player if they are eliminated
//throw new feException("playereliminated:$newActivePlayer");
							$this->endTurnCleanup(); // recursively call this
				}
				else
				{ // the player is NOT eliminated
						//throw new feException( "new active player:$newActivePlayer" );

						//self::incStat( 1, 'turns_number', $newActivePlayer ); // increase end game player stat
						self::incStat( 1, 'turns_number' ); // increase end game table stat

						self::giveExtraTime( $newActivePlayer ); // give the player some extra time to make their decision

						$this->gamestate->changeActivePlayer( $newActivePlayer );
						$this->gamestate->nextState( "startNewPlayerTurn" ); // begin a new player's turn

						self::notifyAllPlayers( "startTurn", clienttranslate( '${player_name} has started their turn.' ), array(
								'player_name' => self::getActivePlayerName(),
								'new_player_id' => $newActivePlayer,
								'is_clockwise' => $this->isTurnOrderClockwise(),
								'current_player_name' => $this->getCurrPlayerName(),
								'next_player_name' => $this->getNextPlayerName()
						) );
				}

//				throw new feException("end endturncleanup");
		}

		function setAllPlayersInactive()
		{
				$players = $this->getPlayersDeets(); // get player details, mainly to use for notification purposes
				foreach($players as $player)
				{
						$playerId = $player['player_id'];
						$this->gamestate->setPlayerNonMultiactive( $playerId, "startNewPlayerTurn" ); // just make them inactive
				}
		}

		function discardCardsBeingPlayed()
		{
				$sql = "SELECT * FROM equipmentCards ";
				$sql .= "WHERE card_location='playing' ";

				$playingCards = self::getObjectListFromDB( $sql );

				foreach($playingCards as $card)
				{
						$equipmentId = $card['card_id'];
						$this->playEquipmentOnTable($equipmentId); // discard the equipment card

				}
		}

		function discardSingleActiveTurnCards()
		{
			$equipmentId = 0;

			if($equipmentId = $this->isCoffeeActive())
			{ // coffee was played this turn
					$this->discardActivePlayerEquipmentCard($equipmentId); // discard it
			}

			if($equipmentId = $this->isRestrainingOrderActive())
			{ // restraining order was played this turn
					$this->discardActivePlayerEquipmentCard($equipmentId); // discard it
			}

			if($equipmentId = $this->isRiotShieldActive())
			{ // riot shield was played this turn
					$this->discardActivePlayerEquipmentCard($equipmentId); // discard it
			}

			if($equipmentId = $this->isZombieSerumActive())
			{ // zombie serum was played this turn
					$this->discardActivePlayerEquipmentCard($equipmentId); // discard it
			}

			if($equipmentId = $this->isWeaponCrateActive())
			{ // weapon crate was played this turn
					$this->discardActivePlayerEquipmentCard($equipmentId); // discard it
			}
		}

		function executeActionInvestigate()
		{
				// update the integrity cards seen table
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				$playerInvestigated = $this->getLastPlayerInvestigated($playerWhoseTurnItIs);
				$positionOfCardInvestigated = $this->getLastCardPositionInvestigated($playerWhoseTurnItIs);
//throw new feException( "playerInvestigated:$playerInvestigated positionOfCardInvestigated:$positionOfCardInvestigated" );
				$cardId = $this->getCardIdFromPlayerAndPosition($playerInvestigated, $positionOfCardInvestigated);

				$this->investigateCard($cardId, $playerWhoseTurnItIs, false); // investigate this card and notify players

				$this->setStateAfterTurnAction($playerWhoseTurnItIs); // see which state we go into after completing this turn action

		}

		function executeActionEquip()
		{
				$playerWhoseTurnItIs = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.


				// draw an equipment card
				$this->drawEquipmentCard($playerWhoseTurnItIs, 1); // draw 1 equipment card

				// reveal the card of the player who armed
				$integrityCardPositionRevealed = $this->getLastCardPositionRevealed($playerWhoseTurnItIs); // get the card position revealed

				if($this->getInfectorCardId() == $this->getCardIdFromPlayerAndPosition($playerWhoseTurnItIs, $integrityCardPositionRevealed))
				{ // the infector was revealed

						$this->infectorFound($playerWhoseTurnItIs, $integrityCardPositionRevealed, $playerWhoseTurnItIs);
				}
				else
				{
					$this->revealCard($playerWhoseTurnItIs, $integrityCardPositionRevealed); // reveal the integrity card from this player's perspective and notify all players
				}


				$this->setStateAfterTurnAction($playerWhoseTurnItIs);
		}


		function executeActionArm()
		{
				// make the active player the owner of the gun by updating the database
				$playerWhoseTurnItIs = self::getActivePlayerId(); // Current Player = player who played the current player action (the one who made the AJAX request). In general, only use this in multiplayer states. Active Player = player whose turn it is.

				// reveal the card of the player who armed
				$integrityCardPositionRevealed = $this->getLastCardPositionRevealed($playerWhoseTurnItIs); // get the card position revealed
				if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getInfectorCardId() == $this->getCardIdFromPlayerAndPosition($playerWhoseTurnItIs, $integrityCardPositionRevealed))
				{ // the infector was revealed

						$this->infectorFound($playerWhoseTurnItIs, $integrityCardPositionRevealed, $playerWhoseTurnItIs);
				}
				else
				{
						$this->revealCard($playerWhoseTurnItIs, $integrityCardPositionRevealed); // reveal the integrity card from this player's perspective and notify all players
						$gun = $this->pickUpGun($playerWhoseTurnItIs, $this->getStateName());
				}

				$this->gamestate->nextState( "askAim" ); // begin a new player's turn
		}

		function executeActionBite()
		{
				$initialStateName = $this->getStateName();
				$diceRolled = $this->getZombieDiceRolled();
				$playerBiting = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				$armId = $this->getGunIdHeldByPlayer($playerBiting); // get the ID of the arms
				//throw new feException( "armId:$armId" );
				$playerBeingBitten = $this->getPlayerIdOfGunTarget($armId); // the player targeted by arms
				$isTargetALeader = $this->isPlayerALeader($playerBeingBitten); // see if the player turning into a zombie was a LEADER
				//throw new feException( "playerBeingBitten:$playerBeingBitten" );

				$zombieFaceRolled = false;
				$biterReaimsRolled = false;
				$numberOfInfectionTokensAdded = 0;
				foreach( $diceRolled as $die )
				{
						$dieId = $die['die_id']; // internal id
						$dieValue = $die['die_value']; // the face rolled

						switch($dieValue)
						{
								case 6: // turn into a zombie
										$zombieFaceRolled = true;

								break;

								case 7: // biter re-aims arms
								case 8: // biter re-aims arms
										$biterReaimsRolled = true;
								break;

								case 9: // blank
										// do nothing
								break;

								case 10: // add extra infection token
								case 11: // add extra infection token
										$this->addInfectionToken($playerBeingBitten, true); // give them an extra Infection Token and notify them
								break;
						}
				}

				// ELIMINATE A NON-ZOMBIE IF THEY WERE BITTEN
				if($zombieFaceRolled && !$isTargetALeader)
				{ // we are zombifying a NON-leader

						$this->eliminatePlayer($playerBeingBitten); // turn into a zombie, notify everyone, reveal all cards, drop guns
				}

				// SEE WHICH STATE WE END UP IN
				if($zombieFaceRolled && $isTargetALeader)
				{ // we are zombifying a LEADER
						$this->endGameCleanup('team_win', 'zombie');
						$this->gamestate->nextState( "endGame" );
				}
				elseif($biterReaimsRolled)
				{ // the biter must re-aim
						$this->gamestate->changeActivePlayer( $playerBiting ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
						$this->gamestate->nextState( "askAimMustReaim" ); // ask the player to aim their arms
				}
				else
				{ // the game is NOT ending and the zombie does NOT need to reaim
						$this->setStateAfterTurnAction($playerBiting); // see which state we go into after completing this turn action
				}

		}

		// All equipment cards have been resolved in reaction to a SHOOT action so it's time to
		// resolve the SHOOT action.
		function executeActionShoot()
		{
			//throw new feException("executeActionShoot");
				$guns = $this->getGunsShooting();
				if(is_null($guns) || count($guns) < 1)
				{ // something happened where the player who was shooting no longer has a gun
						self::notifyAllPlayers( "noGuns", clienttranslate( 'The player is no longer armed so the shoot has no effect.' ), array(
								'player_name' => self::getActivePlayerName()
						) );

						$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
						$this->setStateAfterTurnAction($playerWhoseTurnItIs); // see which state we go into after completing this turn action
				}

				foreach( $guns as $gun )
				{ // go through each gun that is currently shooting
						$targetPlayerId = $gun['gun_aimed_at']; // get the PLAYER ID of the target of this gun
						$gunId = $gun['gun_id'];
						$gunType = $gun['gun_type'];
						$heldByPlayerId = $gun['gun_held_by']; // get the player shooting
//throw new feException( "gunType:$gunType" );
						$wasPlayerZombie = $this->isPlayerZombie($targetPlayerId); // this needs to be BEFORE the shootPlayer() because if you shoot them and they turn into a zombie, we want you to drop your gun
						$this->shootPlayer($heldByPlayerId, $targetPlayerId, $gunType); // shoot the player
						if($gunType == 'gun')
						{ // this is a gun shooting
								if(!$wasPlayerZombie)
								{ // this was a GUN shot and it did not hit a zombie
										$this->dropGun($gunId); // drop the gun in the database (do this BEFORE setState so you know whether you should ask them to aim or not)
								}
								else
								{
										$this->setGunState($gunId, 'aimed'); // it can't stay in the 'shooting' state
								}

								$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
//throw new feException("playerWhoseTurnItIs:$playerWhoseTurnItIs");
								$this->setStateAfterTurnAction($playerWhoseTurnItIs); // see which state we go into after completing this turn action
						}
						else
						{ // this is a zombie biting

								// they don't drop their arms

								$this->setGunState($gunId, 'aimed'); // it can't stay in the 'shooting' state

								$this->setEquipmentHoldersToActive("askBiteReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState( "askBiteReaction" );
						}
				}
		}

		function removeInfectionToken($playerIdRemoving, $cardPositionRemoving, $shouldWeNotify)
		{
	//throw new feException( "woundedCardId: $woundedCardId" );
				// reset the token in the database
				$sqlUpdate = "UPDATE integrityCards SET ";
				$sqlUpdate .= "has_infection=0 WHERE ";
				$sqlUpdate .= "card_location_arg=$cardPositionRemoving AND card_location='$playerIdRemoving'";

				self::DbQuery( $sqlUpdate );

				if($shouldWeNotify)
				{
						$playerName = $this->getPlayerNameFromPlayerId($playerIdRemoving); // get the player's name

						// notify all players that the wounded token has been removed
						self::notifyAllPlayers( "removeInfectionToken", clienttranslate( 'The Infection Token has been removed from ${player_name}.' ), array(
								'player_name' => $playerName,
								'player_id_removing' => $playerIdRemoving,
								'card_position_removing' => $cardPositionRemoving
						) );
				}
		}

		function addInfectionToken($playerId, $shouldWeNotify)
		{
						$integrityCardPositionGettingToken = $this->getIntegrityCardPositionForNextInfectionToken($playerId); // find the next card position (1,2,3) to get an Infection Token
						$this->addInfectionTokenToCard($playerId, $integrityCardPositionGettingToken); // set the database to show this card now has an infection token

						if($shouldWeNotify)
						{
								$playerBittenName = $this->getPlayerNameFromPlayerId($playerId); // the name of the player being bitten

								self::notifyAllPlayers( "addInfectionToken", clienttranslate( '${player_name} has been infected.' ), array(
														'player_id_of_infected' => $playerId,
														'card_position' => $integrityCardPositionGettingToken,
														'player_name' => $playerBittenName
								) );
						}

						return $integrityCardPositionGettingToken;
		}

		function moveInfectionToken($cardIdSource, $cardIdDestination)
		{
				$sourcePlayerId = $this->getIntegrityCardOwner($cardIdSource);
				$sourceCardPosition = $this->getIntegrityCardPosition($cardIdSource);
				$sourcePlayerName = $this->getPlayerNameFromPlayerId($sourcePlayerId);

				$destinationPlayerId = $this->getIntegrityCardOwner($cardIdDestination);
				$destinationCardPosition = $this->getIntegrityCardPosition($cardIdDestination);
				$destinationPlayerName = $this->getPlayerNameFromPlayerId($destinationPlayerId);

				// update the database with the new locations
				$this->removeInfectionToken($sourcePlayerId, $sourceCardPosition, false); // remove token in database
				$this->addInfectionTokenToCard($destinationPlayerId, $destinationCardPosition); // add token in database

				// notify players of the move
				self::notifyAllPlayers( "moveInfectionToken", clienttranslate( 'The infection token has been moved from ${player_name} to ${player_name_2}.' ), array(
										'token_player_id' => $sourcePlayerId,
										'token_card_position' => $sourceCardPosition,
										'destination_player_id' => $destinationPlayerId,
										'destination_card_position' => $destinationCardPosition,
										'player_name' => $sourcePlayerName,
										'player_name_2' => $destinationPlayerName
				) );
		}

		function rollZombieDice($playerBiting, $playerBeingBitten)
		{
				$this->clearDieValues();
				$infectionTokenCount = $this->countInfectionTokensForPlayer($playerBeingBitten);
//throw new feException("tokencount:$infectionTokenCount");
				for ($x = 1; $x <= $infectionTokenCount; $x+=1)
				{ // roll a die for each infection token
						$randomValue = bga_rand( 6, 11 ); // get random values
						$this->setDieValue($x, $randomValue, $playerBiting, $playerBeingBitten, 0); // set the value in the database

						$playerBittenName = $this->getPlayerNameFromPlayerId($playerBeingBitten); // the name of the player being bitten
						$resultText = $this->getEffectFromDieValue($x, $randomValue);

						// notify players
						self::notifyAllPlayers( "rolledZombieDie", clienttranslate( '${player_name} rolled a Zombie Die: ${result_text}' ), array(
										'rolled_face' => $randomValue,
										'die_rolled' => $x,
										'result_text' => $resultText,
										'player_name' => $playerBittenName
						) );
				}
		}

		function rollInfectionDie()
		{
				$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
				$this->gamestate->changeActivePlayer( $playerWhoseTurnItIs ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was

				$this->setGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN", 1); // save that we have rolled the infection die this round so we don't do it again

				//throw new feException( "ROLL INFECTION DIE" );

				$infectionTokenCount = $this->countInfectionTokensForPlayer($playerWhoseTurnItIs); // see how many infection token this player currently has

				if($infectionTokenCount > 2)
				{ // player is a zombie or already has 3 infection tokens
						self::notifyAllPlayers( "noInfectionToken", clienttranslate( '${player_name} is already fully infected so they will not roll the Infection Die.' ), array(
										'player_name' => self::getActivePlayerName()
						) );
				}
				elseif($this->isPlayerZombie($playerWhoseTurnItIs))
				{ // they are already a zombie
						self::notifyAllPlayers( "noInfectionToken", clienttranslate( '${player_name} is already a zombie so they will not roll the Infection Die.' ), array(
										'player_name' => self::getActivePlayerName()
						) );
				}
				else
				{ // they have less than 3 infection tokens

						// check random number to see if infection token happens
						$randomValue = bga_rand( 0, 5 ); // get random values

						$resultText = $this->getEffectFromDieValue(4, $randomValue);

						self::notifyAllPlayers( "rolledInfectionDie", clienttranslate( '${player_name} rolled the Infection Die: ${result_text}' ), array(
										'rolled_face' => $randomValue,
										'result_text' => $resultText,
										'player_name' => self::getActivePlayerName()
						) );

						$infectedCardPosition = 0;
						if($randomValue < 2)
						{	// we rolled an infection symbol and we aren't maxed out on infection tokens yet

								$infectedCardPosition = $this->addInfectionToken($playerWhoseTurnItIs, true);
						}
						else
						{ // we are NOT adding an infection token
								self::notifyAllPlayers( "noInfectionToken", clienttranslate( '${player_name} avoided infection.' ), array(
												'player_name' => self::getActivePlayerName()
								) );
						}

						$this->setDieValue(4, $randomValue, $playerWhoseTurnItIs, $playerWhoseTurnItIs, $infectedCardPosition); // set the value in the database for the infection die (id=4)
				}

				$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
				//$this->gamestate->nextState( "endTurnReaction" ); // go to end turn reaction
		}

		// This is called when we entered the state to play an equipment card, which means all the input required has
		// been set in the database and we can simply resolve the equipment.
		function executeEquipmentPlay()
		{

				$equipmentId = $this->getEquipmentCardIdInUse(); // get the ID of the equipment card that is being played
				if(!$equipmentId)
				{ // there is no equipment in use (could be possible if a player drops from the game while they're using equipment)
					$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)
					$this->gamestate->changeActivePlayer( $playerWhoseTurnItIs ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was

						if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
						{ // we are using the zombies expansion and the Infector is hidden and we haven't rolled it yet this turn
								$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
						}
						else
						{
								$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
								//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
						}
				}

				$collectorNumber = $this->getCollectorNumberFromId($equipmentId);

				$stateName = $this->getEquipmentPlayedInState($equipmentId); // get the state in which this equipment was played (DO THIS BEFORE RESOLVING BECAUSE DISCARDING WILL CLEAR IT)
				$this->resolveEquipment($equipmentId); // take all the input saved in the database and resolve the equipment card
				$stateAfterEquipmentResolution = $this->getStateName();
//throw new feException( "stateAfterEquipmentResolution:$stateAfterEquipmentResolution" );
				$unaimedGuns = $this->getHeldUnaimedGuns(); // see if we have an unaimed guns held by a player

				$playersOverEquipmentCardLimit = $this->getPlayersOverEquipmentHandLimit(); // get any players over the equipment card hand limit

				//$countPlayerOver = count($playersOverEquipmentCardLimit);
				//throw new feException( "playersOverEquipmentCardLimit:$countPlayerOver" );
				if(count($playersOverEquipmentCardLimit) > 0)
				{ // someone is over the equipment card hand limit
					//throw new feException( "over hand limit" );
						foreach($playersOverEquipmentCardLimit as $player)
						{ // go through each player over the hand limit
								$playerIdOverLimit = $player['player_id'];

								$this->gamestate->changeActivePlayer($playerIdOverLimit); // make that player active so they can aim it
								$this->gamestate->nextState( "askDiscardOutOfTurn" );
						}
				}
				elseif(count($unaimedGuns) > 0)
				{ // there IS an unaimed gun
//throw new feException( "before changing active player" );
							foreach($unaimedGuns as $unaimedGun)
							{ // go through each unaimed gun (there should only be 1)
									$gunId = $unaimedGun['gun_id'];

									$gunHolder = $this->getPlayerIdOfGunHolder($gunId); // get the player holding the unaimed gun

									$this->gamestate->changeActivePlayer($gunHolder); // make that player active so they can aim it

									$this->gamestate->nextState( "askAimOutOfTurn" );

									return; // if multiple guns need to be aimed out of turn, we need to do one at a time since you can't change active player in askAimOutOfTurn
							}

//throw new feException( "after changing active player" );
				}
				else
				{ // there are no special situations we need to handle
//throw new feException( "stateName:$stateName" );
						if($stateName == "chooseEquipmentToPlayOnYourTurn" || $stateName == "playerTurn")
						{ // this was NOT played in reaction to something

								// do NOT set players to multiactive because we are just going back to their turn
								$this->gamestate->nextState( "playerTurn" ); // go back to player action state
						}
						elseif($stateName == "askEndTurnReaction" || $stateName == "chooseEquipmentToPlayReactEndOfTurn")
						{
								if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
								{ // we are using the zombies expansion and the Infector is hidden
										$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
								}
								else
								{
										$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
										//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
								}
						}
						elseif($stateName == "chooseEquipmentToPlayReactInvestigate")
						{
								if($this->skipInvestigateReactions())
								{
										$this->gamestate->nextState( "executeActionInvestigate" ); // go straight to executing the investigation
								}
								else
								{ // allow reactions to the investigation
										$this->setEquipmentHoldersToActive("askInvestigateReaction"); // set anyone holding equipment to active
										//$this->gamestate->nextState( "askInvestigateReaction" ); // go back to allowing other players to play equipment (which state depends on the state we came from)
								}
						}
						elseif($stateName == "chooseEquipmentToPlayReactShoot" || $stateName == "chooseEquipmentToPlayReactBite")
						{ // this equipment was played in reaction to a shoot or bite
							//throw new feException( "chooseEquipmentToPlayReactShoot" );
								if($this->canWeRechooseAction())
								{ // the player will be allowed to choose a new action after the equipment is resolved

										$playerWhoseTurnItIs = $this->getGameStateValue("CURRENT_PLAYER"); // get the player whose real turn it is now (not necessarily who is active)

										$this->gamestate->changeActivePlayer( $playerWhoseTurnItIs ); // set the active player (this cannot be done in an activeplayer game state) to the one whose turn it was
//throw new feException( "yes you can re-choose action $playerWhoseTurnItIs" );
										$this->gamestate->nextState( "playerTurn" ); // go back to player action state
								}
								else
								{ // this equipment affect the shoot but it doesn't let you choose a new action (like Riot Shield, Restraining Order, Mobile Detonator)
										//throw new feException( "no you cannot re-choose action" );

										if($this->isZombieSerumActive())
										{ // zombie serum was played
											//throw new feException( "serum active" );
													$this->setEquipmentHoldersToActive("askBiteReaction"); // set anyone holding equipment to active
													//$this->gamestate->nextState( "askBiteReaction" );
										}
										else
										{
												if($stateName == "chooseEquipmentToPlayReactBite")
												{
														$this->setEquipmentHoldersToActive("askBiteReaction"); // set anyone holding equipment to active
														//$this->gamestate->nextState( "askBiteReaction" ); // go back to allowing other players to play equipment (which state depends on the state we came from)
												}
												else
												{
														$this->setEquipmentHoldersToActive("askShootReaction"); // set anyone holding equipment to active
														//$this->gamestate->nextState( "askShootReaction" ); // go back to allowing other players to play equipment (which state depends on the state we came from)
												}
										}
								}
						}
						elseif($stateName == "chooseActiveOrHandEquipmentCard")
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
								$this->gamestate->nextState( "choosePlayerNoCancel" ); // choose player but do not give them a cancel button, otherwise the target gets to cancel the equipment
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
								$this->gamestate->nextState( "choosePlayerNoCancel" ); // choose player but do not give them a cancel button, otherwise the target gets to cancel the equipment
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

        if ($state['type'] === "activeplayer")
				{
            switch ($statename)
						{
								case "chooseCardToInvestigate":
										$this->gamestate->nextState( "cancelAction" );
								break;

								case "chooseCardToRevealForArm":
										$this->gamestate->nextState( "cancelAction" );
								break;

								case "chooseCardToRevealForEquip":
										$this->gamestate->nextState( "cancelAction" );
								break;

								case "discardEquipment":
										if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
										{ // we are using the zombies expansion and the Infector is hidden
												$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
										}
										else
										{
												$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
												//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
										}
								break;

								case "askAimMustReaim":
								case "askAim":
										if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
										{ // we are using the zombies expansion and the Infector is hidden
												$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
										}
										else
										{
												$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
												//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
										}
								break;

								case "aimAtPlayer":
										$this->gamestate->nextState( "endTurn" );
								break;

								case "askAimOutOfTurn":
										$this->gamestate->nextState( "afterAimedOutOfTurn" );
								break;

								case "discardOutOfTurn":
										$this->gamestate->nextState( "afterDiscardedOutOfTurn" );
								break;

								case "chooseIntegrityCards":
										$this->gamestate->nextState( "executeEquipment" );
								break;

								case "choosePlayer":
										$this->gamestate->nextState( "executeEquipment" );
								break;

								case "choosePlayerNoCancel":
										$this->gamestate->nextState( "executeEquipment" );
								break;

								case "chooseActiveOrHandEquipmentCard":
										$this->gamestate->nextState( "executeEquipment" );
								break;

								case "chooseAnotherPlayer":
										$this->gamestate->nextState( "executeEquipment" );
								break;

                default:
										if($this->getGameStateValue('ZOMBIES_EXPANSION') == 2 && $this->getGameStateValue("ROLLED_INFECTION_DIE_THIS_TURN") == 0 && $this->isInfectorHidden())
										{ // we are using the zombies expansion and the Infector is hidden
												$this->gamestate->nextState( "rollInfectionDie" ); // player must roll the infection die
										}
										else
										{
												$this->setEquipmentHoldersToActive("endTurnReaction"); // set anyone holding equipment to active
												//$this->gamestate->nextState( "endTurnReaction" ); // allow end of turn equipment reactions
										}
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer")
				{
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, "allPassedOnReactions" );

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
