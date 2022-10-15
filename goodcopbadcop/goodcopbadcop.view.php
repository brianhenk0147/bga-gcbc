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
 * goodcopbadcop.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in goodcopbadcop_goodcopbadcop.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once( APP_BASE_PATH."view/common/game.view.php" );

class view_goodcopbadcop_goodcopbadcop extends game_view
{
    function getGameName() {
        return "goodcopbadcop";
    }

  	function build_page( $viewArgs )
  	{
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/


        /*

        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );

        */

        /*

        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock -->
        //          ... my HTML code ...
        //      <!-- END myblock -->


        $this->page->begin_block( "goodcopbadcop_goodcopbadcop", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array(
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }

        */

        global $g_user;
        $current_player_id = $g_user->get_id(); // get the ID of the player making the request (the perspective we are using)

        if($this->game->isSpectator())
				{ // this is a spectator
						$current_player_id = $this->game->getPlayerIdFromPlayerNo(1); // pretend they are player 1
				}

        $this->tpl['EQUIPMENT_REFERENCE_LABEL'] = self::_("Equipment Reference");

        // default the names to nothing so players who are not playing do not show a name
        $this->tpl['PLAYER_a_NAME'] = "";
        $this->tpl['PLAYER_b_NAME'] = "";
        $this->tpl['PLAYER_c_NAME'] = "";
        $this->tpl['PLAYER_d_NAME'] = "";
        $this->tpl['PLAYER_e_NAME'] = "";
        $this->tpl['PLAYER_f_NAME'] = "";
        $this->tpl['PLAYER_g_NAME'] = "";
        $this->tpl['PLAYER_h_NAME'] = "";

        $playersForDisplay = $this->game->getPlayerDisplayInfo($current_player_id); // get all display properties for players

        // set variables for each player for name and player color
        foreach( $playersForDisplay as $player_id => $player )
						{
              $positionLetter = $player['player_position']; // a, b, c, etc.
              $playerName = $player['player_name']; // the player's BGA account handle
              $playerColor = $player['player_color']; // the color they are using in this game

              $this->tpl['PLAYER_'.$positionLetter.'_NAME'] = $playerName; // store the player's name to use in the TPL file
              $this->tpl['PLAYER_'.$positionLetter.'_COLOR'] = $playerColor; // store the player's color to use in the TPL file
						}




        /*********** Do not change anything below this line  ************/
  	}
}
