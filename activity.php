<?php

/**
 * This is a retrieval for Activity.
 *
 * While not strictly required, Note (or Image, Article, etc) activities should
 * have an `id` field which refers to a (semi-)permanent, unique location where
 * the post itself can be found - even if the entire content of the post is
 * also included in the Create message.
 *
 * phpActivityPub will return the Note itself if passed only an id.
 *
 * With create=1 in query parameters, it will wrap the note in a corresponding
 * Create message.  This way the same script can handle both requests.
 */

include_once 'admin/functions.php';

// Required parameters: id
if (! empty($_GET['id'])) {
  // TODO: Validate request signature

    $db = new SQLite3('admin/db.sqlite3', SQLITE3_OPEN_READONLY);
    $result = query($db, 'SELECT id, user, content FROM post WHERE id=?', intval($_GET['id']));
    $db->close();

    if (count($result) > 0) {
        $id = $result[0]['id'];
        $user = $result[0]['user'];
        $content = json_decode($result[0]['content'], true);

      // recreate the original activity from the db bits
        $activity = array_merge([
        "id" => $phpActivityPub_root . 'activity.php?id=' . $id,
        "type" => 'Note',
        "attributedTo" => $phpActivityPub_root . 'actor.php?user=' . $user,
        "to" => [ 'https://www.w3.org/ns/activitystreams#Public' ]
        ], $content);

        if (! empty($_GET['create'])) {
            // asking for the Create which wrapped the note
            $activity = [
            'id' => $phpActivityPub_root . 'activity.php?create=1&id=' . $id,
            'type' => 'Create',
            'actor' => $phpActivityPub_root . 'actor.php?user=' . $user,
            "to" => [ 'https://www.w3.org/ns/activitystreams#Public' ],
            'object' => $activity
            ];
        }

      // Set JSON-LD schema on the root, and return
        $activity['@context'] = 'https://www.w3.org/ns/activitystreams';

        response(200, $activity);
    } else {
        response(404, [ 'error' => 'Invalid ID ' . $_GET['id'] . ' in Activity request' ]);
    }
} else {
    response(400, [ 'error' => 'ID missing from Activity request' ]);
}
