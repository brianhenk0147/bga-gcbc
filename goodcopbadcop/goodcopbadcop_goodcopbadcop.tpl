{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- goodcopbadcop implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    goodcopbadcop_goodcopbadcop.tpl

    This is the HTML template of your game.

    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.

    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format

    See your "view" PHP file to check how to set variables and control blocks

    Please REMOVE this comment before publishing your game on BGA
-->


<div id="board_area">
    <div id="board_row_1" class="board_row">
        <div id="upper_left_corner_box" class="corner_box">
            <div id="player_e_name_holder" class="player_name_holder_vertical" style="color:#{PLAYER_e_COLOR}; font-weight: bold;">{PLAYER_e_NAME}
            </div>

            <div id="upper_left_name_divider">
            </div>

            <div id="player_b_name_holder" class="player_name_holder_horizontal" style="color:#{PLAYER_b_COLOR}; font-weight: bold;">{PLAYER_b_NAME}
            </div>
        </div>
        <div id="player_e_area" class="player_holder_vertical">
            <div id="player_e_row_1" class="player_row_vertical">
                <div id="player_e_reference_card_holder" class="reference_card_holder_vertical">
                </div>

                <div id="player_e_equipment_hand_holder" class="equipment_hand_holder_vertical">
                </div>
            </div>
            <div id="player_e_row_2" class="player_row_vertical">
                <div id="player_e_integrity_card_3_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
                <div id="player_e_integrity_card_2_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
                <div id="player_e_integrity_card_1_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
            </div>
            <div id="player_e_row_3" class="player_row_vertical">
                <div id="player_e_first_equipment_active_holder" class="first_equipment_active_holder_vertical">
                </div>
                <div id="player_e_gun_holder" class="gun_holder_vertical">
                </div>
                <div id="player_e_other_equipment_active_holder" class="other_equipment_active_holder_vertical">
                </div>
            </div>
        </div>

        <div id="player_c_area" class="player_holder_vertical">
            <div id="player_c_row_1" class="player_row_vertical">

                <div id="player_c_reference_card_holder" class="reference_card_holder_vertical">
                </div>

                <div id="player_c_equipment_hand_holder" class="equipment_hand_holder_vertical">
                </div>
            </div>
            <div id="player_c_row_2" class="player_row_vertical">
                <div id="player_c_integrity_card_3_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
                <div id="player_c_integrity_card_2_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
                <div id="player_c_integrity_card_1_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
            </div>
            <div id="player_c_row_3" class="player_row_vertical">
                <div id="player_c_first_equipment_active_holder" class="first_equipment_active_holder_vertical">
                </div>
                <div id="player_c_gun_holder" class="gun_holder_vertical">
                </div>
                <div id="player_c_other_equipment_active_holder" class="other_equipment_active_holder_vertical">
                </div>
            </div>
        </div>

        <div id="upper_right_corner_box" class="corner_box">
            <div id="player_c_name_holder" class="player_name_holder_vertical" style="color:#{PLAYER_c_COLOR}; font-weight: bold;">{PLAYER_c_NAME}
            </div>

            <div id="player_f_name_holder" class="player_name_holder_horizontal" style="color:#{PLAYER_f_COLOR}; font-weight: bold;">{PLAYER_f_NAME}
            </div>
        </div>
    </div>
    <div id="board_row_2" class="board_row">
        <div id="player_b_area" class="player_holder_horizontal">
            <div id="player_b_row_1" class="player_row_horizontal">
                <div id="player_b_reference_card_holder" class="reference_card_holder_horizontal">

                </div>
                <div id="player_b_integrity_card_1_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>
                <div id="player_b_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>
                </div>
            </div>
            <div id="player_b_row_2" class="player_row_horizontal">
                <div id="player_b_equipment_hand_holder" class="equipment_hand_holder_horizontal">

                </div>

                <div id="player_b_integrity_card_2_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>
                <div id="player_b_gun_holder" class="gun_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>
                </div>
            </div>
            <div id="player_b_row_3" class="player_row_horizontal">
                <div class="placeholder_horizontal">

                </div>
                <div id="player_b_integrity_card_3_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>
                <div id="player_b_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>
                </div>
            </div>
        </div>
        <div id="center_area_top">
            <div id="active_equipment_center_holder">
            </div>
        </div>
        <div id="player_f_area" class="player_holder_horizontal">
            <div id="player_f_row_1" class="player_row_horizontal">
                <div id="player_f_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>
                </div>

                <div id="player_f_integrity_card_1_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>

                <div id="player_f_reference_card_holder" class="reference_card_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>'
                </div>


            </div>
            <div id="player_f_row_2" class="player_row_horizontal">
                <div id="player_f_gun_holder" class="gun_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>
                </div>

                <div id="player_f_integrity_card_2_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>

                <div id="player_f_equipment_hand_holder" class="equipment_hand_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>
                </div>
            </div>
            <div id="player_f_row_3" class="player_row_horizontal">
                <div id="player_f_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">
                  <div class="card_placeholder_horizontal">
                  </div>
                </div>
                <div id="player_f_integrity_card_3_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>

            </div>
        </div>
    </div>

    <div id="board_row_3" class="board_row">
      <div id="player_g_area" class="player_holder_horizontal">
          <div id="player_g_row_1" class="player_row_horizontal">

              <div id="player_g_reference_card_holder" class="reference_card_holder_horizontal">
                <div class="card_placeholder_horizontal">
                </div>
              </div>
              <div id="player_g_integrity_card_1_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
              </div>
              <div id="player_g_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">
                <div class="card_placeholder_horizontal">
                </div>
              </div>
          </div>
          <div id="player_g_row_2" class="player_row_horizontal">
              <div id="player_g_equipment_hand_holder" class="equipment_hand_holder_horizontal">
                <div class="card_placeholder_horizontal">
                </div>
              </div>
              <div id="player_g_integrity_card_2_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
              </div>
              <div id="player_g_gun_holder" class="gun_holder_horizontal">
                <div class="card_placeholder_horizontal">
                </div>
              </div>
          </div>
          <div id="player_g_row_3" class="player_row_horizontal">
              <div class="placeholder_horizontal">
                <div class="card_placeholder_horizontal">
                </div>
              </div>

              <div id="player_g_integrity_card_3_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
              </div>
              <div id="player_g_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">
                <div class="card_placeholder_horizontal">
                </div>
              </div>
          </div>
      </div>
        <div id="center_area_bottom">
            <div id="gun_row">
                <div id="gun_1_holder" class="gun_holder">
                </div>
                <div id="gun_2_holder" class="gun_holder">
                </div>
                <div id="gun_3_holder" class="gun_holder">
                </div>
                <div id="gun_4_holder" class="gun_holder">
                </div>
            </div>
        </div>
        <div id="player_d_area" class="player_holder_horizontal">
            <div id="player_d_row_1" class="player_row_horizontal">
                <div id="player_d_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">

                </div>

                <div id="player_d_integrity_card_1_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>

                <div id="player_d_reference_card_holder" class="reference_card_holder_horizontal">

                </div>

            </div>
            <div id="player_d_row_2" class="player_row_horizontal">

                <div id="player_d_gun_holder" class="gun_holder_horizontal">

                </div>


                <div id="player_d_integrity_card_2_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>

                <div id="player_d_equipment_hand_holder" class="equipment_hand_holder_horizontal">

                </div>

            </div>
            <div id="player_d_row_3" class="player_row_horizontal">
                <div id="player_d_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">

                </div>
                <div id="player_d_integrity_card_3_holder" class="integrity_card_holder_horizontal opponent_integrity_card_slot">
                </div>

            </div>
        </div>

    </div>
    <div id="board_row_4" class="board_row">
        <div id="bottom_left_corner_box" class="corner_box">

          <div id="player_g_name_holder" class="player_name_holder_horizontal" style="color:#{PLAYER_g_COLOR}; font-weight: bold;">{PLAYER_g_NAME}
          </div>
          <div id="player_g_a_win_condition_row">
              <div id="player_g_a_win_condition_holder">
              </div>

              <div id="player_a_name_holder" class="player_name_holder_vertical" style="color:#{PLAYER_a_COLOR}; font-weight: bold;">{PLAYER_a_NAME}
              </div>
          </div>

        </div>
        <div id="player_a_area" class="player_holder_vertical">
            <div id="player_a_row_1" class="player_row_vertical">
                <div id="player_a_first_equipment_active_holder" class="first_equipment_active_holder_vertical">
                </div>
                <div id="player_a_gun_holder" class="gun_holder_vertical">
                </div>
                <div id="player_a_other_equipment_active_holder" class="other_equipment_active_holder_vertical">
                </div>


            </div>
            <div id="player_a_row_2" class="player_row_vertical">
                <div id="player_a_integrity_card_3_holder" class="integrity_card_holder_vertical my_integrity_card_slot">
                </div>
                <div id="player_a_integrity_card_2_holder" class="integrity_card_holder_vertical my_integrity_card_slot">
                </div>
                <div id="player_a_integrity_card_1_holder" class="integrity_card_holder_vertical my_integrity_card_slot">
                </div>
            </div>
            <div id="player_a_row_3" class="player_row_vertical">
                <div id="player_a_reference_card_holder" class="reference_card_holder_vertical">
                </div>
                <div id="player_a_equipment_hand_holder" class="equipment_hand_holder_vertical">
                </div>
            </div>
        </div>

        <div id="player_h_area" class="player_holder_vertical">
            <div id="player_h_row_1" class="player_row_vertical">
                <div id="player_h_first_equipment_active_holder" class="first_equipment_active_holder_vertical">
                </div>
                <div id="player_h_gun_holder" class="gun_holder_vertical">
                </div>
                <div id="player_h_other_equipment_active_holder" class="other_equipment_active_holder_vertical">
                </div>

            </div>
            <div id="player_h_row_2" class="player_row_vertical">
                <div id="player_h_integrity_card_3_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
                <div id="player_h_integrity_card_2_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
                <div id="player_h_integrity_card_1_holder" class="integrity_card_holder_vertical opponent_integrity_card_slot">
                </div>
            </div>
            <div id="player_h_row_3" class="player_row_vertical">
                <div id="player_h_reference_card_holder" class="reference_card_holder_vertical">
                </div>
                <div id="player_h_equipment_hand_holder" class="equipment_hand_holder_vertical">
                </div>
            </div>
        </div>
        <div id="bottom_right_corner_box" class="corner_box">
            <div id="player_d_name_holder" class="player_name_holder_horizontal" style="color:#{PLAYER_d_COLOR}; font-weight: bold;">{PLAYER_d_NAME}
            </div>

            <div id="player_h_name_holder" class="player_name_holder_vertical" style="color:#{PLAYER_h_COLOR}; font-weight: bold;">{PLAYER_h_NAME}
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

<!-- match the class to the css class for the image location -->
<!-- match the id to the js file when you dojo.place it -->
var jstpl_integrityCard = '<div class="integrity_card component_rounding" id="player_${playerLetter}_integrity_card_${cardPosition}" style="background-position:-${x}px -${y}px">\
                        </div>';
var jstpl_gun = '<div class="gun component_rounding" id="gun_${gunId}" style="background-position:-${x}px -${y}px">\
                        </div>';
var jstpl_wounded = '<div class="wounded_token" id="wounded_token_${cardType}">\
                        </div>';

var jstpl_largeEquipment = '<div class="large_equipment component_rounding" style="background-position:-${x}px -${y}px">\
                        </div>';
var jstpl_activeEquipment = '<div class="active_equipment_card component_rounding" id="player_${playerLetter}_active_equipment_${equipmentId}" style="background-position:-${x}px -${y}px">\
                        </div>';
var jstpl_equipmentInHand = '<div class="hand_equipment_card component_rounding" id="player_${playerLetter}_hand_equipment_${equipmentId}" style="background-position:-${x}px -${y}px">\
                        </div>';
var jstpl_equipmentCardBack = '<div class="equipment_card_back component_rounding">\
                        </div>';
var jstpl_movingEquipmentCard = '<div class="moving_equipment_card_back component_rounding" id="moving_equipment_card" style="background-position:-${x}px -${y}px">\
                        </div>';
var jstpl_activeCenterEquipment = '<div class="active_center_equipment_card component_rounding" id="center_active_equipment_${equipmentId}" style="background-position:-${x}px -${y}px">\
                        </div>';

</script>

{OVERALL_GAME_FOOTER}
