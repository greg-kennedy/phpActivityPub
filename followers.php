<?php

/**
 * This is the Followers list for a user.
 *
 * Followers is a SHOULD collection for an actor, but some ActivityPub
 * services (e.g. Pleroma) request and format this for presentation, and
 * it's nice to have anyway.
 *
 * Returns a list of accepted Follow requests, filtered by the user.
 */

include_once 'admin/functions.php';

// Required parameters: user
if (! empty($_GET['user'])) {
  // instance actor - always return an empty collection
  if (strtolower($_GET['user']) === $_SERVER['SERVER_NAME']) {
    $followers = [];
  } else {

    // TODO: Validate request signature

    $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
    $result = query($db, 'SELECT dest FROM sub WHERE user=?', $_GET['user']);
    $db->close();

    if (count($result) > 0) {
      // push each activity into the orderedItems
      foreach ($result as $r) {
        $followers[] = $r['dest'];
      }
    } else {
      response(404, [ 'error' => 'Invalid user ' . $_GET['user'] . ' in followers request' ]);
    }
  }

  response(200, [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'type' => 'Collection',
    'totalItems' => count($followers),
    'items' => $followers
  ]);
} else {
  response(400, [ 'error' => 'User missing from followers request' ]);
}

?>
