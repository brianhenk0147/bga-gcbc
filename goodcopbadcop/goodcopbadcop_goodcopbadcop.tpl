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
        <div id="upper_left_corner_box" class="corner_box">.
        </div>
        <div id="player_e_area" class="player_holder_vertical">
            <div id="player_e_row_1" class="player_row_vertical">
                <div id="player_e_name_holder" class="player_name_holder_vertical">namee
                </div>
                <div id="player_e_equipment_hand_holder" class="equipment_hand_holder_vertical">eqha
                </div>
            </div>
            <div id="player_e_row_2" class="player_row_vertical">
                <div id="player_e_integrity_card_3_holder" class="integrity_card_holder_vertical">I3
                </div>
                <div id="player_e_integrity_card_2_holder" class="integrity_card_holder_vertical">I2
                </div>
                <div id="player_e_integrity_card_1_holder" class="integrity_card_holder_vertical">I1
                </div>
            </div>
            <div id="player_e_row_3" class="player_row_vertical">
                <div id="player_e_first_equipment_active_holder" class="first_equipment_active_holder_vertical">aceq
                </div>
                <div id="player_e_gun_holder" class="gun_holder_vertical">gun
                </div>
                <div id="player_e_other_equipment_active_holder" class="other_equipment_active_holder_vertical">oteq
                </div>
            </div>
        </div>

        <div id="player_c_area" class="player_holder_vertical">
            <div id="player_c_row_1" class="player_row_vertical">
                <div id="player_c_name_holder" class="player_name_holder_vertical">namec
                </div>
                <div id="player_c_equipment_hand_holder" class="equipment_hand_holder_vertical">eqha
                </div>
            </div>
            <div id="player_c_row_2" class="player_row_vertical">
                <div id="player_c_integrity_card_3_holder" class="integrity_card_holder_vertical">I3
                </div>
                <div id="player_c_integrity_card_2_holder" class="integrity_card_holder_vertical">I2
                </div>
                <div id="player_c_integrity_card_1_holder" class="integrity_card_holder_vertical">I1
                </div>
            </div>
            <div id="player_c_row_3" class="player_row_vertical">
                <div id="player_c_first_equipment_active_holder" class="first_equipment_active_holder_vertical">aceq
                </div>
                <div id="player_c_gun_holder" class="gun_holder_vertical">gun
                </div>
                <div id="player_c_other_equipment_active_holder" class="other_equipment_active_holder_vertical">oteq
                </div>
            </div>
        </div>

        <div id="upper_right_corner_box" class="corner_box">.
        </div>
    </div>
    <div id="board_row_2" class="board_row">
        <div id="player_b_area" class="player_holder_horizontal">
            <div id="player_b_row_1" class="player_row_horizontal">
                <div id="player_b_equipment_hand_holder" class="equipment_hand_holder_horizontal">eqha
                </div>
                <div id="player_b_integrity_card_1_holder" class="integrity_card_holder_horizontal">I1
                </div>
                <div id="player_b_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">oteq
                </div>
            </div>
            <div id="player_b_row_2" class="player_row_horizontal">
                <div class="placeholder_horizontal">plc
                </div>
                <div id="player_b_integrity_card_2_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_b_gun_holder" class="gun_holder_horizontal">gun
                </div>
            </div>
            <div id="player_b_row_3" class="player_row_horizontal">
                <div id="player_b_name_holder" class="player_name_holder_horizontal">namec
                </div>
                <div id="player_b_integrity_card_3_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_b_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">aceq
                </div>
            </div>
        </div>
        <div id="center_area_top">.
        </div>
        <div id="player_f_area" class="player_holder_horizontal">
            <div id="player_f_row_1" class="player_row_horizontal">
                <div id="player_f_equipment_hand_holder" class="equipment_hand_holder_horizontal">eqha
                </div>
                <div id="player_f_integrity_card_1_holder" class="integrity_card_holder_horizontal">I1
                </div>
                <div id="player_f_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">oteq
                </div>
            </div>
            <div id="player_f_row_2" class="player_row_horizontal">
                <div class="placeholder_horizontal">plc
                </div>
                <div id="player_f_integrity_card_2_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_f_gun_holder" class="gun_holder_horizontal">gun
                </div>
            </div>
            <div id="player_f_row_3" class="player_row_horizontal">
                <div id="player_f_name_holder" class="player_name_holder_horizontal">namec
                </div>
                <div id="player_f_integrity_card_3_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_f_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">aceq
                </div>
            </div>
        </div>
    </div>

    <div id="board_row_3" class="board_row">
        <div id="player_g_area" class="player_holder_horizontal">
            <div id="player_g_row_1" class="player_row_horizontal">
                <div id="player_g_equipment_hand_holder" class="equipment_hand_holder_horizontal">eqha
                </div>
                <div id="player_g_integrity_card_1_holder" class="integrity_card_holder_horizontal">I1
                </div>
                <div id="player_g_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">oteq
                </div>
            </div>
            <div id="player_g_row_2" class="player_row_horizontal">
                <div class="placeholder_horizontal">plc
                </div>
                <div id="player_g_integrity_card_2_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_g_gun_holder" class="gun_holder_horizontal">gun
                </div>
            </div>
            <div id="player_g_row_3" class="player_row_horizontal">
                <div id="player_g_name_holder" class="player_name_holder_horizontal">namec
                </div>
                <div id="player_g_integrity_card_3_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_g_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">aceq
                </div>
            </div>
        </div>
        <div id="center_area_bottom">.
        </div>
        <div id="player_d_area" class="player_holder_horizontal">
            <div id="player_d_row_1" class="player_row_horizontal">
                <div id="player_d_equipment_hand_holder" class="equipment_hand_holder_horizontal">eqha
                </div>
                <div id="player_d_integrity_card_1_holder" class="integrity_card_holder_horizontal">I1
                </div>
                <div id="player_d_other_equipment_active_holder" class="other_equipment_active_holder_horizontal">oteq
                </div>
            </div>
            <div id="player_d_row_2" class="player_row_horizontal">
                <div class="placeholder_horizontal">plc
                </div>
                <div id="player_d_integrity_card_2_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_d_gun_holder" class="gun_holder_horizontal">gun
                </div>
            </div>
            <div id="player_d_row_3" class="player_row_horizontal">
                <div id="player_d_name_holder" class="player_name_holder_horizontal">namec
                </div>
                <div id="player_d_integrity_card_3_holder" class="integrity_card_holder_horizontal">I2
                </div>
                <div id="player_d_first_equipment_active_holder" class="first_equipment_active_holder_horizontal">aceq
                </div>
            </div>
        </div>
    </div>
    <div id="board_row_4" class="board_row">
        <div id="upper_left_corner_box" class="corner_box">.
        </div>
        <div id="player_a_area" class="player_holder_vertical">
            <div id="player_a_row_1" class="player_row_vertical">
                <div id="player_a_name_holder" class="player_name_holder_vertical">namee
                </div>
                <div id="player_a_equipment_hand_holder" class="equipment_hand_holder_vertical">eqha
                </div>
            </div>
            <div id="player_a_row_2" class="player_row_vertical">
                <div id="player_a_integrity_card_3_holder" class="integrity_card_holder_vertical">I3
                </div>
                <div id="player_a_integrity_card_2_holder" class="integrity_card_holder_vertical">I2
                </div>
                <div id="player_a_integrity_card_1_holder" class="integrity_card_holder_vertical">I1
                </div>
            </div>
            <div id="player_a_row_3" class="player_row_vertical">
                <div id="player_a_first_equipment_active_holder" class="first_equipment_active_holder_vertical">aceq
                </div>
                <div id="player_a_gun_holder" class="gun_holder_vertical">gun
                </div>
                <div id="player_a_other_equipment_active_holder" class="other_equipment_active_holder_vertical">oteq
                </div>
            </div>
        </div>

        <div id="player_h_area" class="player_holder_vertical">
            <div id="player_h_row_1" class="player_row_vertical">
                <div id="player_h_name_holder" class="player_name_holder_vertical">namec
                </div>
                <div id="player_h_equipment_hand_holder" class="equipment_hand_holder_vertical">eqha
                </div>
            </div>
            <div id="player_h_row_2" class="player_row_vertical">
                <div id="player_h_integrity_card_3_holder" class="integrity_card_holder_vertical">I3
                </div>
                <div id="player_h_integrity_card_2_holder" class="integrity_card_holder_vertical">I2
                </div>
                <div id="player_h_integrity_card_1_holder" class="integrity_card_holder_vertical">I1
                </div>
            </div>
            <div id="player_h_row_3" class="player_row_vertical">
                <div id="player_h_first_equipment_active_holder" class="first_equipment_active_holder_vertical">aceq
                </div>
                <div id="player_h_gun_holder" class="gun_holder_vertical">gun
                </div>
                <div id="player_h_other_equipment_active_holder" class="other_equipment_active_holder_vertical">oteq
                </div>
            </div>
        </div>
        <div id="upper_right_corner_box" class="corner_box">.
        </div>
    </div>
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

</script>

{OVERALL_GAME_FOOTER}
