<?php

/**
 * WebFinger for phpActivityPub
 *
 * WebFinger responds to requests of the form
 *  https://domain.tld/.well-known/webfinger?resource=acct:user@domain.tld
 *
 * The response for ActivityPub is a JSON document like this:
 * {
 *   "subject": "acct:user@domain.tld",
 *   "links": [
 *     {
 *       "rel": "self",
 *       "type": "application/activity+json",
 *       "href": "https://domain.tld/path/to/users/actor"
 *     }
 *   ]
 * }
 *
 * This provides the requester with the location of the user's Actor
 * object, where all the necessary ActivityPub endpoints are defined.
 *
 * For some services, an "Instance Actor" is used to represent actions
 * taken by the instance itself (instead of originating from a specific
 * user).  This generally takes the form "domain.tld@domain.tld" and is
 * handled slightly differently from a regular user.
 *
 * The WebFinger spec is defined in rfc7033
 *  https://tools.ietf.org/html/rfc7033
 * while the acct: scheme is defined in rfc7565
 *  https://tools.ietf.org/html/rfc7565
 */

// common helper functions and definitions
include_once 'admin/functions.php';

// check query parameters and extract userpart
if (! empty($_GET['resource']) &&
    preg_match('/^acct:(.+)@' . $_SERVER['SERVER_NAME'] . '$/i', $_GET['resource'], $matches)) {

  if (strtolower($matches[1]) === $_SERVER['SERVER_NAME']) {
    // request for the Instance Actor (domain.tld@domain.tld)
    $entity = [
      "subject" => "acct:" . $_SERVER['SERVER_NAME'] . '@' . $_SERVER['SERVER_NAME'],
      "links" => [[
         "rel" => 'self',
         "type" => 'application/activity+json',
         "href" => $phpActivityPub_root . 'actor.php?user=' . $_SERVER['SERVER_NAME']
      ]]
    ];
    error_log('Webfinger for instance actor, returning.');
    response(200, $entity);
  } else {
    // connect to db, look up user info (verify the actor exists)
    $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
    $info = query($db, 'SELECT user FROM acct WHERE user=?', $matches[1]);
    $db->close();

    if ( count($info) == 1) {
      // found the user!  give back the WebFinger response
      $entity = [
        "subject" => "acct:" . $info[0]['user'] . '@' . $_SERVER['SERVER_NAME'],
        "links" => [[
           "rel" => 'self',
           "type" => 'application/activity+json',
           "href" => $phpActivityPub_root . 'actor.php?user=' . $info[0]['user']
        ]]
      ];
      error_log('Webfinger for user ' . $info[0]['user'] . ', returning.');
      response(200, $entity);
    } else {
      // the request looked OK but the user was not found
      response(404, [ 'error' => 'No account for ' . $matches[1] . '@', $_SERVER['SERVER_NAME'] ]);
    }
  }
} else {
  // Request didn't look like the right format or was for the wrong server
  response(400, [ 'error' => 'Bad request. Please make sure "acct:USER@' . $_SERVER['SERVER_NAME'] . '" is what you are sending as the "resource" query parameter.' ]);
}

?>
