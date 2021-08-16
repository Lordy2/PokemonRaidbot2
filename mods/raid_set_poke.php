<?php
// Write to log.
debug_log('raid_set_poke()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
raid_access_check($update, $data, 'pokemon');

// Set the id.
$id = $data['id'];
$pokemon_id_form = get_pokemon_by_table_id($data['arg']);

// Update pokemon in the raid table.
my_query(
    "
    UPDATE    raids
    SET       pokemon = '{$pokemon_id_form['pokedex_id']}',
              pokemon_form = '{$pokemon_id_form['pokemon_form_id']}'
      WHERE   id = {$id}
    "
);

// Get raid times.
$raid = get_raid($data['id']);

// Create the keys.
$keys = [];

// Build message string.
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid);

// Build callback message string.
$callback_response = getTranslation('raid_boss_saved');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Get raid poll messages to be updated from cleanup.
$rs = my_query(
    "
    SELECT    *
    FROM      cleanup
      WHERE   raid_id = {$id}
    "
);

// Get updated raid poll message and keys.
$updated_msg = show_raid_poll($raid);
$updated_keys = keys_vote($raid);

// Update the shared raid polls.
if($config->RAID_PICTURE) {
    require_once(LOGIC_PATH . '/raid_picture.php');
    while ($raidmsg = $rs->fetch()) {
        $picture_url = raid_picture_url($raid);
        $tg_json[] = editMessageMedia($raidmsg['message_id'], $updated_msg['short'], $updated_keys, $raidmsg['chat_id'], ['disable_web_page_preview' => 'true'], true, $picture_url);
    } 
} else {
    while ($raidmsg = $rs->fetch()) {
        $tg_json[] = editMessageText($raidmsg['message_id'], $updated_msg['full'], $updated_keys, $raidmsg['chat_id'], ['disable_web_page_preview' => 'true'], true);    
    }
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Alert users.
alarm($raid, $update['callback_query']['from']['id'], 'new_boss');

// Exit.
exit();
