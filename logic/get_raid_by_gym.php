<?php
/**
 * Get raid data by gym_id
 * @param $gym_id
 * @return array
 */
function get_raid_by_gym($gym_id)
{
    // Get the raid data by gym_id.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   users.name,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left,
                   TIMESTAMPDIFF(MINUTE,raids.start_time,raids.end_time) as t_duration
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        LEFT JOIN  users
        ON         raids.user_id = users.user_id
        WHERE      end_time>UTC_TIMESTAMP()
        AND        gyms.id = {$gym_id}
        "
    );

    // Get the row.
    $raid = $rs->fetch();
    
    // Inject raid level
    $raid['level'] = get_raid_level($raid['pokemon'], $raid['pokemon_form']);

    debug_log($raid);

    return $raid;
}

?>
