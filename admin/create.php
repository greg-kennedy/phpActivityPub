<?php

include_once 'functions.php';

if (! empty($_POST['user']) && ! empty($_POST['type'])) {
  // Create the keypair
  $res=openssl_pkey_new();

  // Get public key
  $rsa_public=(openssl_pkey_get_details($res))['key'];

  // Get private key
  openssl_pkey_export($res, $rsa_private);

  // generate API key
  $key = base64_encode(random_bytes(32));

  $db = new SQLite3('db.sqlite3', SQLITE3_OPEN_READWRITE);

  $actors = query($db, 'INSERT INTO acct(user, type, rsa_public, rsa_private, key) VALUES(?, ?, ?, ?, ?)',
    $_POST['user'],
    $_POST['type'],
    $rsa_public,
    $rsa_private,
    $key
  );

  $db->close();
}

header('Location: index.php');
exit();

?>
