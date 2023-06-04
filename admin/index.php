<html>
  <head>
    <title>phpAP Admin</title>
<?php
include_once 'functions.php';

// We do a bunch of db setup on visiting this admin page
$db = new SQLite3("db.sqlite3", SQLITE3_OPEN_READWRITE);

// Create the tables we care about
query($db, 'CREATE TABLE IF NOT EXISTS instance(rsa_public TEXT NOT NULL, rsa_private TEXT NOT NULL, PRIMARY KEY(rsa_public, rsa_private)) WITHOUT ROWID');
query($db, 'CREATE TABLE IF NOT EXISTS acct (user STRING COLLATE NOCASE PRIMARY KEY, type STRING NOT NULL COLLATE NOCASE, rsa_public TEXT NOT NULL, rsa_private TEXT NOT NULL, key TEXT NOT NULL) WITHOUT ROWID');
query($db, 'CREATE TABLE IF NOT EXISTS sub (user STRING NOT NULL COLLATE NOCASE REFERENCES acct(user) ON DELETE CASCADE ON UPDATE CASCADE, dest STRING NOT NULL COLLATE NOCASE, PRIMARY KEY(user, dest)) WITHOUT ROWID');
query($db, 'CREATE TABLE IF NOT EXISTS post(id INTEGER PRIMARY KEY, user STRING NOT NULL COLLATE NOCASE REFERENCES acct(user) ON DELETE CASCADE ON UPDATE CASCADE, content STRING NOT NULL)');

// Check the instance actor and set it up if needed
$instance = query($db, 'SELECT rsa_public, rsa_private FROM instance');

if (count($instance) == 0) {
  // Instance actor does not exist yet and needs to be set up.

  // Create the keypair
  $res=openssl_pkey_new();

  // Get public key
  $rsa_public =(openssl_pkey_get_details($res))['key'];

  // Get private key
  openssl_pkey_export($res, $rsa_private);

  // Put details back into the table.
  query($db, 'INSERT INTO instance(rsa_public, rsa_private) VALUES (?, ?)', $rsa_public, $rsa_private);
} else {
  $rsa_public = $instance[0]['rsa_public'];
}

// Get any post-able actors (accounts)
$actors = query($db, 'SELECT user, type, key FROM acct');

$db->close();
?>
  </head>
  <body>
    <h1>phpAP Admin</h1>
    <hr>
    <h2>Instance Actor</h2>
    Public key:
    <pre><?php echo $rsa_public ?></pre>
    <hr>
    <h2>User List</h2>
    <table>
      <tr><th>User</th><th>Type</th><th>Post Key</th></tr>
<?php
foreach ($actors as $a)
  echo '<tr><td>', $a['user'], '</td><td>', $a['type'], '</td><td>', $a['key'], "</td></tr>\n";
?>
    </table>
    <hr>
    <h2>Add User</h2>
    <form action="create.php" method="POST">
      <input name="user" placeholder="MyCoolBot">
      <select name="type">
        <option value="Application">Application</option>
        <option value="Group">Group</option>
        <option value="Organization">Organization</option>
        <option value="Person">Person</option>
        <option value="Service">Service</option>
      </select>
      <input type="submit">
    </form>
  </body>
</html>
