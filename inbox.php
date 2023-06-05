<?php

/**
 * Inbox - phpActivityPub
 *
 * The inbox handles ActivityPub messages coming from remote servers.
 *
 * Each Actor has an Inbox.  Some servers also support a "shared inbox",
 * we do not.  (It wouldn't help us much anyway.)
 *
 * This Inbox handles only two types of activity:
 *  . Follow
 *    User wishes to Follow our Actor (in other words, 'subscribe').  When
 *    receiving a Follow request we should add the user to the table
 *    so that future Posts can be broadcast to them.  Also, we will send
 *    back an Accept activity.  Otherwise, the remote user will see their
 *    request in "pending" state.
 *  . Undo -> Follow
 *    "Undo" a Follow request, i.e. unsubscribe.  Remove the user from
 *    the table of subscribers and stop posting to them in the future.
 *    Undo Follow does not need at Accept, it is assumed to be unilateral.
 *
 * Additional actions could be added here, like receiving a post (reply) or
 * directed message, a Like, and so on.
 */

include_once 'admin/functions.php';

// Attempt to json_decode the body, in whatever form it arrives.
//  (multipart/form-data is not supported and just returns an empty array)
$sContentType = $_SERVER["CONTENT_TYPE"] ?? 'text/plain';
$content = ($sContentType == "multipart/form-data" ? $_POST : json_decode(file_get_contents("php://input"), true));

// Required parameters: user
if (! empty($_GET['user'])) {
    if (strtolower($_GET['user']) === $_SERVER['SERVER_NAME']) {
      // the instance actor does not actually have a working inbox
        response(405, [ 'error' => 'Instance actor rejects all inbox posts' ]);
    } else {
      // TODO: Verify signature

      // verify the Object matches our Actor URL... if not, this request was sent to the wrong inbox!
      //if ($content['object'] === $phpActivityPub_root . 'actor.php?user=' . $_GET['user']) {

        // switch based on received activity type
        if ($content['type'] === 'Follow') {
            $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READWRITE);
            query($db, 'INSERT OR IGNORE INTO sub(user, dest) VALUES(?, ?)', $_GET['user'], $content['actor']);
            $db->close();

          // create and send Accept reply
            sendActivity($_GET['user'], $content['actor'], [
            'type' => 'Accept',
            'object' => $content['id']
            ]);

            response(204);
        } elseif ($content['type'] === 'Undo' && $content['object']['type'] === 'Follow') {
            $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READWRITE);
            query($db, 'DELETE FROM sub WHERE user=? AND dest=?', $_GET['user'], $content['object']['actor']);
            $db->close();

          // Undo is unilateral and does not expect an Accept response

            response(204);
        } else {
            response(405, [ 'error' => 'Unsupported request type ' . $content['type'] ]);
        }
      //} else {
        //http_response_code(400);
        //echo 'Wrong inbox';
      //}
    }
} else {
    response(400, [ 'error' => 'Username missing' ]);
}
