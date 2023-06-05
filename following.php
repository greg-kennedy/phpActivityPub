<?php

/**
 * This is the Following list for a user.
 *
 * Following is a SHOULD collection for an actor, but some ActivityPub
 * services (e.g. Pleroma) request and format this for presentation, and
 * it's nice to have anyway.
 *
 * Of course, since phpActivityPub can't follow users, this is always empty...
 */

include_once 'admin/functions.php';

// Required parameters: user
if (! empty($_GET['user'])) {
  // instance actor - always return an empty collection
    if (strtolower($_GET['user']) === $_SERVER['SERVER_NAME']) {
        $following = [];
    } else {
      // TODO: Validate request signature

        $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
        $result = query($db, 'SELECT user FROM acct WHERE user=?', $_GET['user']);
        $db->close();

        if (count($result) > 0) {
            $following = [];
        } else {
            response(404, [ 'error' => 'Invalid user ' . $_GET['user'] . ' in following request' ]);
        }
    }

    response(200, [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'type' => 'Collection',
    'totalItems' => count($following),
    'items' => $following
    ]);
} else {
    response(400, [ 'error' => 'User missing from following request' ]);
}
