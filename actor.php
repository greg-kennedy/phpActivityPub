<?php

/**
 * Actor page - phpActivityPub
 *
 * This endpoint gives information about the Actor at the host.
 * Remote servers find this endpoint by looking up the account
 * using WebFinger, which should return this URL.
 *
 * The response for ActivityPub is a JSON document like this:
 * {
 *   "@context": [
 *     "https://www.w3.org/ns/activitystreams",
 *     "https://w3id.org/security/v1"
 *   ],
 *
 *   "id": "https://domain.tld/path/to/this/actor",
 *   "type": "[Application or Person or Group etc]",
 *
 *   "inbox": "https://domain.tld/path/to/users/inbox",
 *
 *   "publicKey": {
 *     "id": "https://domain.tld/path/to/this/actor#main-key",
 *     "owner": "https://domain.tld/path/to/this/actor",
 *     "publicKeyPem": "-----BEGIN PUBLIC KEY-----\nMIIB..."
 *   }
 * }
 *
 * Both @context and id MUST be present, although id MAY be null for transient objects.
 * type is optional, but in practice, is also required.
 *
 * inbox and outbox are required by the spec; however, many servers will let other
 *  hosts get by without one (or both!)
 *
 * publicKey is necessary for other hosts to validate our activity, otherwise optional.
 *
 * The spec recommends followers and following collections as SHOULD, but Pleroma
 * requires at least followers.
 *
 * preferredUsername is optional, but Mastodon requires it.
 *
 * The definition of an Actor is in the W3C's ActivityStreams recommendation
 *  https://www.w3.org/TR/activitystreams-core/
 * but see also the Actor Types in ActivityStreams Vocabulary
 *  https://www.w3.org/TR/activitystreams-vocabulary/#actor-types
 */

// common helper functions and definitions
include_once 'admin/functions.php';

// check query parameters and extract userpart
if (! empty($_GET['user'])) {

  if (strtolower($_GET['user']) === $_SERVER['SERVER_NAME']) {
    // instance actor - always served without needing signature
    $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
    $info = query($db, 'SELECT rsa_public FROM instance');
    $db->close();

    if (count($info) > 0) {
      // The instance actor has been set up properly.
      $user = $_SERVER['SERVER_NAME'];
      $type = 'Application';
      $rsa_public = $info[0]['rsa_public'];
    } else {
      response(500, [ 'error' => 'Instance actor has not been properly configured.' ]);
    }
  } else {
    // TODO: Verify signature

    // connect to db, look up user info (verify the actor exists)
    $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
    $info = query($db, 'SELECT user, type, rsa_public FROM acct WHERE user=?', $_GET['user']);
    $db->close();

    if (count($info) > 0) {
      // Found an account by this name.
      $user = $info[0]['user'];
      $type = $info[0]['type'];
      $rsa_public = $info[0]['rsa_public'];
    } else {
      response(404, [ 'error' => 'No such user ' . $_GET['user'] . ' in Actor request' ]);
    }
  }

  // Build the complete Actor doc and send it out.
  $actor = [
    "@context" => ["https://www.w3.org/ns/activitystreams", "https://w3id.org/security/v1"],
    "id" => $phpActivityPub_root . 'actor.php?user=' . $user,
    "type" => $type,

    "inbox" => $phpActivityPub_root . 'inbox.php?user=' . $user,
    "outbox" => $phpActivityPub_root . 'outbox.php?user=' . $user,

    "preferredUsername" => $user,
    "followers" => $phpActivityPub_root . 'followers.php?user=' . $user,
    "following" => $phpActivityPub_root . 'following.php?user=' . $user,

    "publicKey" => [
      "id" => $phpActivityPub_root . 'actor.php?user=' . $user . '#main-key',
      "owner" => $phpActivityPub_root . 'actor.php?user=' . $user,
      "publicKeyPem" => $rsa_public
    ]
  ];

  response(200, $actor);
} else {
  response(400, [ 'error' => 'User missing from Actor request' ]);
}

?>
