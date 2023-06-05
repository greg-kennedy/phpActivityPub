<html>
  <head>
    <title>phpActivityPub</title>
  </head>
  <body>
    <h1>phpActivityPub</h1>
    <a href="admin/">Admin Panel</a> (password-protected)
    <h2>User List</h2>
    <table>
      <tr><th>User</th><th>Actor Link</th><th>Inbox Link</th><th>Outbox Link</th><th>Followers</th><th>Following</th></tr>
<?php
include_once 'admin/functions.php';

$db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
$result = query($db, 'SELECT user FROM acct');
$db->close();

// put the instance actor at the top
$result = array_merge([ [ 'user' => $_SERVER['SERVER_NAME'] ] ], $result);

foreach ($result as $info) {
    echo '<tr><th><a href="https://', $_SERVER['SERVER_NAME'], '/.well-known/webfinger?resource=acct:', $info['user'], '@', $_SERVER['SERVER_NAME'], '">', $info['user'], '</a></th>';
    echo '<td><a href="https://', $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'], 'actor.php?user=', $info['user'], '">Actor Link</a></td>';
    echo '<td><a href="https://', $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'], 'inbox.php?user=', $info['user'], '">Inbox Link</a></td>';
    echo '<td><a href="https://', $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'], 'outbox.php?user=', $info['user'], '">Outbox Link</a></td>';
    echo '<td><a href="https://', $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'], 'followers.php?user=', $info['user'], '">Followers Link</a></td>';
    echo '<td><a href="https://', $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'], 'following.php?user=', $info['user'], '">Following Link</a></td></tr>';
}
?>
    </table>
  </body>
</html>
