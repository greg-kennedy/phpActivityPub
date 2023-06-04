<?php

/**
 * This is the Outbox for a user.
 *
 * Outbox is required by the ActivityPub spec, but most servers do not
 * actually care about this (it's used mainly for client-to-server posting).
 *
 * However, it COULD be used by a server wanting to replay missed events
 * in case of connection errors or new federation, to populate a cache on
 * the new instance.
 *
 * We return a list of events, in reverse chronological order, filtered by
 * the user.
 */

include_once 'admin/functions.php';

// Required parameters: user
if (! empty($_GET['user'])) {
  // instance actor - always return an empty collection
  if (strtolower($_GET['user']) === $_SERVER['SERVER_NAME']) {
    $outbox = [];
  } else {
    // TODO: Validate request signature

    $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
    $result = query($db, 'SELECT id, content FROM post WHERE user=? ORDER BY id DESC', $_GET['user']);
    $db->close();

    if (count($result) > 0) {
      // push each activity into the orderedItems
      foreach ($result as $a) {
        // recreate the original activity from the db bits
        $activity = [
          'id' => $phpActivityPub_root . 'activity.php?create=1&id=' . $a['id'],
          'type' => 'Create',
          'actor' => $phpActivityPub_root . 'actor.php?user=' . $_GET['user'],
          "to" => 'https://www.w3.org/ns/activitystreams#Public',
          'content' => array_merge([
            "id" => $phpActivityPub_root . 'activity.php?id=' . $a['id'],
            "type" => 'Note',
            "attributedTo" => $phpActivityPub_root . 'actor.php?user=' . $_GET['user'],
            "to" => 'https://www.w3.org/ns/activitystreams#Public'
          ], json_decode($a['content'], true))
        ];

        $outbox[] = $activity;
      }
    } else {
      response(404, [ 'error' => 'Invalid user in outbox request' ]);
    }
  }

  response(200, [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'type' => 'OrderedCollection',
    'totalItems' => count($outbox),
    'orderedItems' => $outbox
  ]);
} else {
  response(400, [ 'error' => 'User missing from outbox request' ]);
}

?>
