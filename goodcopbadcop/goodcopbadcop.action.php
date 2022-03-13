<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * goodcopbadcop implementation : © <Your name here> <Your email address here>
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

    public function chooseCardToInvestigate()
    {
        self::setAjaxMode();

        $this->game->chooseCardToInvestigate(); // tell the server that the current player is choosing a card to investigate

        self::ajaxResponse( );
    }

    public function clickedCardToInvestigateCard()
    {
        self::setAjaxMode();

        // Retrieve arguments
        $playerPosition = self::getArg( "playerPosition", AT_alphanum, true ); // a, b, c, etc.
        $cardPosition = self::getArg( "cardPosition", AT_posint, true ); // 1, 2, 3

        $this->game->clickedCardToInvestigateCard( $playerPosition, $cardPosition );

        self::ajaxResponse( );
    }

    public function passOnUseEquipment()
    {
        self::setAjaxMode();

        $this->game->passOnUseEquipment(); // tell the server that this player is not using an equipment as a reaction

        self::ajaxResponse( );
    }

    public function clickedEndTurnButton()
    {
      self::setAjaxMode();

      $this->game->clickedEndTurnButton();

      self::ajaxResponse( );
    }

}
