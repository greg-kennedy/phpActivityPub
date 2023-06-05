<?php

/**
 * Post - phpActivityPub
 *
 * This endpoint is not a part of ActivityPub support - it is how you,
 * the user, post messages into the Fediverse.
 *
 * Every account in phpActivityPub has a "key" of random digits assigned
 * at its creation.
 * To post a message, send a POST request (cURL, etc) where key=(actor's key)
 * and content=(content you wish to post).
 *
 * A successful POST will add the content to the local database, where it
 * can be seen from the view.php link.
 *
 * It also iterates through all Followers from the table and sends the content
 * to them.
 */

include_once 'admin/functions.php';

// Attempt to json_decode the body, in whatever form it arrives.
//  (multipart/form-data is not supported and just returns an empty array)
$sContentType = $_SERVER["CONTENT_TYPE"] ?? 'text/plain';
$content = ($sContentType == "multipart/form-data" ? $_POST : json_decode(file_get_contents("php://input"), true));

// Requirements to post
if (! empty($_SERVER['HTTP_X_API_KEY']) && !empty($content)) {
    $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READWRITE);
    $user = query($db, 'SELECT user FROM acct WHERE key=?', $_SERVER['HTTP_X_API_KEY']);

    if (count($user) > 0) {
      // This creates the Note, locally.
        query(
            $db,
            'INSERT INTO post (user, content) VALUES (?, ?)',
            $user[0]['user'],
            json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $id = $db->lastInsertRowId();

      // create document we plan to send out
        $activity = [
        "id" => $phpActivityPub_root . 'activity.php?create=1&id=' . $id,
        "type" => "Create",
        "to" => 'https://www.w3.org/ns/activitystreams#Public',
        "object" => array_merge([
          "id" => $phpActivityPub_root . 'activity.php?id=' . $id,
          "type" => 'Note',
          "attributedTo" => $phpActivityPub_root . 'actor.php?user=' . $user[0]['user'],
          "to" => 'https://www.w3.org/ns/activitystreams#Public'
        ], $content)
        ];

      // Now, for every follower, send the Create activity to them.
        $sub_result = query($db, 'SELECT dest FROM sub WHERE user=?', $user[0]['user']);

        foreach ($sub_result as $sub_info) {
          // compose and send a Create -> Note activity
            error_log("Sending to " . $sub_info['dest']);
            sendActivity($user[0]['user'], $sub_info['dest'], $activity);
        }

        error_log('Received post, forwarded to recipients');
        response(204);
    } else {
        error_log('Received post but key was invalid');
        response(401, [ 'message' => 'Invalid key' ]);
    }
} else {
    error_log('Received post but no key or content');
    response(400, [ 'message' => 'Key or content missing' ]);
}
