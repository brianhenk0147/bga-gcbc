<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * goodcopbadcop implementation : © Pull the Pin Games - support@pullthepingames.com
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * goodcopbadcop.action.php
 *
 * goodcopbadcop main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/goodcopbadcop/goodcopbadcop/myAction.html", ...)
 *
 */

class action_goodcopbadcop extends APP_GameAction
{
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "goodcopbadcop_goodcopbadcop";
            self::trace( "Complete reinitialization of board game" );
        }
  	}

  	// TODO: defines your action entry points there


    /*

    Example:

    public function myAction()
    {
        self::setAjaxMode();

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }

    */

    public function clickedInvestigateButton()
    {
        self::setAjaxMode();

        $this->game->clickedInvestigateButton(); // tell the server that the current player is choosing a card to investigate

        self::ajaxResponse( );
    }

    public function clickedInfectButton()
    {
        self::setAjaxMode();

        $this->game->clickedInfectButton(); // tell the server that the current player is choosing a card to investigate

        self::ajaxResponse( );
    }

    public function clickedOpponentIntegrityCard()
    {
        self::setAjaxMode();

        // Retrieve arguments
        $playerPosition = self::getArg( "playerPosition", AT_alphanum, true ); // a, b, c, etc.
        $cardPosition = self::getArg( "cardPosition", AT_posint, true ); // 1, 2, 3

        $this->game->clickedOpponentIntegrityCard( $playerPosition, $cardPosition );

        self::ajaxResponse( );
    }

    public function getIntegrityCardDetails()
    {
        self::setAjaxMode();

        // Retrieve arguments
        $playerPosition = self::getArg( "playerPosition", AT_alphanum, true ); // a, b, c, etc.
        $cardPosition = self::getArg( "cardPosition", AT_posint, true ); // 1, 2, 3

        $this->game->getIntegrityCardDetails( $playerPosition, $cardPosition ); // get data and send back via notification to this player

        self::ajaxResponse();
    }

    public function clickedCancelButton()
    {
        self::setAjaxMode();

        $this->game->clickedCancelButton(); // tell the server that the current player wants to cancel their action

        self::ajaxResponse( );
    }

    public function clickedDoneSelectingButton()
    {
        self::setAjaxMode();

        $this->game->clickedDoneSelectingButton(); // tell the server that the current player is done making their selections

        self::ajaxResponse( );
    }

    public function clickedArmButton()
    {
        self::setAjaxMode();

        $this->game->clickedArmButton(); // tell the server that the current player is choosing a card to reveal for their Arm action

        self::ajaxResponse( );
    }

    public function clickedMyIntegrityCard()
    {
        self::setAjaxMode();

        // Retrieve arguments
        $cardPosition = self::getArg( "cardPosition", AT_posint, true ); // 1, 2, 3

        $this->game->clickedMyIntegrityCard( $cardPosition );

        self::ajaxResponse( );
    }

    public function clickedPlayer()
    {
        self::setAjaxMode();

        // Retrieve arguments
        $playerPosition = self::getArg( "letterAim", AT_alphanum, true ); // a, b, c, etc.
        $playerId = self::getArg( "player", AT_alphanum, true ); // player ID

        $this->game->clickedPlayer( $playerPosition, $playerId );

        self::ajaxResponse( );
    }

    public function clickedToggle()
    {
        self::setAjaxMode();

        $toggleHtmlId = self::getArg( "toggleHtmlId", AT_alphanum, true ); // the html ID of the toggle that was clicked
        $isChecked = self::getArg( "isChecked", AT_bool, true );

        $this->game->clickedToggle( $toggleHtmlId, $isChecked );

        self::ajaxResponse();
    }

    public function clickedShootButton()
    {
        self::setAjaxMode();

        $this->game->clickedShootButton(); // tell the server that the current player has decided to shoot

        self::ajaxResponse( );
    }

    public function clickedEquipButton()
    {
        self::setAjaxMode();

        $this->game->clickedEquipButton(); // tell the server that the current player has decided to draw an equipment card

        self::ajaxResponse( );
    }

    public function clickedUseEquipmentButton()
    {
        self::setAjaxMode();

        $this->game->clickedUseEquipmentButton(); // tell the server that the current player has indicated that they want to use an equipment card

        self::ajaxResponse( );
    }

    public function clickedEquipmentCard()
    {
        self::setAjaxMode();

        // Retrieve arguments
        $cardId = self::getArg( "cardId", AT_posint, true ); // either equipmentId if in hand or collectorId if active
        $equipmentType = self::getArg( "equipmentType", AT_alphanum, true ); // hand, active

        $this->game->clickedEquipmentCard($cardId, $equipmentType); // tell the server that the current player has decided to click on their equipment card (usually to use it)

        self::ajaxResponse( );
    }

    public function passOnUseEquipment()
    {
        self::setAjaxMode();

        $this->game->passOnUseEquipment(); // tell the server that this player is not using an equipment as a reaction

        self::ajaxResponse( );
    }

    public function passOnOption()
    {
        self::setAjaxMode();

        $this->game->passOnOption(); // tell the server that this player is oassing on a bonus option

        self::ajaxResponse( );
    }

    public function clickedEndTurnButton()
    {
        self::setAjaxMode();

        $this->game->clickedEndTurnButton();

        self::ajaxResponse( );
    }

    public function clickedSkipButton()
    {
        self::setAjaxMode();

        $this->game->clickedSkipButton();

        self::ajaxResponse( );
    }

}
