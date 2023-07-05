<?php

/**
 * Common functions - phpActivityPub
 */

// ////////////////
// GLOBALS

// SERVER_NAME is only defined when invoked through CGI.
//  You may set a value here that be used for CLI calls.
if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'example.com';
}

// Sets the "root" of phpActivityPub - that is, the hostname plus path to the
//  PHP scripts (but not the scripts themselves)

// NW; check to handle webfinger being redirected via Apache
// for example, we have a rewrite rule like this:
//
// RewriteRule ^/.well-known/webfinger /activitypub/webfinger.php [L,QSA]
//
if (preg_match('#/webfinger.php$#', $_SERVER['SCRIPT_FILENAME'])) {
  $filepath = preg_replace('#[^/]+\.php$#', '', $_SERVER['SCRIPT_FILENAME']);
  $phpActivityPub_root = 'https://' . $_SERVER['SERVER_NAME'] . preg_replace('#' . $_SERVER['DOCUMENT_ROOT'] .'#', '', $filepath);
} else {
  $phpActivityPub_root = 'https://' . $_SERVER['SERVER_NAME'] . preg_replace('#[^/]+\.php$#', '', $_SERVER['SCRIPT_NAME']);
}

// ////////////////
// TEMPLATE

// Send a JSON response and a status code, then exit
function response($code = 204, $object = null)
{
  http_response_code($code);
  if ($object) {
    header('Content-Type: application/json');
    echo json_encode($object, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
  exit;
}

// ////////////////
// DATABASE

// Prepare a query, bind the parameters, execute it and return
function query($db, $sql, ...$params)
{
  // prepare query and bind parameters
  $stmt = $db->prepare($sql);
  // check that the right number of params were passed in
  if ($stmt->paramCount() != count($params)) {
    throw new Exception("Error in query '$sql': expected " . $stmt->paramCount() . " but got " . count($params));
  }

  for ($i = 0; $i < count($params); $i++) {
    $stmt->bindParam($i + 1, $params[$i], gettype($params[$i]) == 'integer' ? SQLITE3_INTEGER : SQLITE3_TEXT);
  }

  $result = $stmt->execute();

  $response = array();
  // a FALSE value here might indicate a db error but we should not explode
  //  e.g. in case of duplicate key or something non-fatal
  if ($result) {
    // retrieve all responses
    while ($info = $result->fetchArray(SQLITE3_ASSOC)) {
      array_push($response, $info);
    }
    $result->finalize();
  }

  // close everything and return
  $stmt->close();

  return $response;
}

// ////////////////
// WEB REQUEST

// Many services, like Mastodon, require HTTP Signatures for GET and POST requests.
//  Therefore this function takes an actor to get their private key,
//  signs the message, and for POST requests also calculates document digest
// Empty $body is assumed to be a GET request, populated is POST.
function request($user, $url, $body = null)
{

  // fetch the private key: we need these to encode the message headers
  $db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
  $result = query($db, 'SELECT rsa_private FROM acct WHERE user=?', $user);
  if (count($result) == 1) {
    $keypair = $result[0]['rsa_private'];
  } else {
    throw new Exception("Failed to get rsa_private for user $user");
  }
  $db->close();

  // Break the URL into components for putting into headers
  $components = parse_url($url);

  // Build headers and signature string

  // get today's date as well
  $date = gmdate('D, d M Y H:i:s T');

  // concatenate it all together
  $signed_string = "(request-target): " . ($body ? 'post' : 'get') . ' ' . $components['path'] .
  "\nhost: " . $components['host'] .
  "\ndate: " . $date;
  if ($body) {
    // create digest of the document
    $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
    $signed_string .= "\ndigest: " . $digest;
  }

  // SIGN THE HEADERS
  openssl_sign($signed_string, $signature, $keypair, OPENSSL_ALGO_SHA256);

  // Create the final header with signature
  $header = 'keyId="' . $GLOBALS['phpActivityPub_root'] . 'actor.php?user=' . $user . '#main-key",headers="(request-target) host date' . ($body ? ' digest' : '') . '",signature="' . base64_encode($signature) . '"';

  // READY TO POST!
  //open connection
  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 50);

  #'Host: ' . $components['host'],
  $headers = array(
  'Accept: application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
  'Date: ' . $date,
  'Signature: ' . $header
  );

  if ($body) {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    array_push(
      $headers,
      'Content-Type: application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
      'Digest: ' . $digest
    );
  };

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  error_log("REQUEST: $url, headers:" . print_r($headers, true));
  if ($body) {
    error_log("body: $body");
  }

  //execute the request!
  $result = curl_exec($ch);

  if ($result === false) {
    $e = curl_errno($ch);
    $err_str = "cURL error: ($e: " . curl_strerror($e) . "): " . curl_error($ch);
    curl_close($ch);

    throw new Exception($err_str);
  }

  $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  if ($responseCode < 200 || $responseCode >= 300) {
    throw new Exception("cURL error: Server responded $responseCode: $result");
  }
  if ($result) {
    error_log("Received result: $result");
    $json_response = json_decode($result, true);
    if (! $json_response) {
      throw new Exception("json_decode error: Failed to decode '$result'");
    }
    return $json_response;
  }
  return array();
}

// Given an Actor URL, get some info we need to post to them
//  * Domain
//  * Inbox portion
// This involves a cURL request to their URL
function get_inbox($user, $url)
{
  // use the instance actor for this req
  $json_content = request($user, $url);

  if ($json_content['inbox']) {
    return $json_content['inbox'];
  }

  throw new Exception("Failed to look up actor for " . $url . ": " . $json_content);
}

// Send an Activity to a remote server.
//  This includes signing using the account's private key.
function sendActivity($user, $dest, $activity)
{

  // right FIRST of all, we need the destination inbox
  $dest = get_inbox($user, $dest);

  $document = json_encode(array_merge([
  '@context' => 'https://www.w3.org/ns/activitystreams',
  'actor' => $GLOBALS['phpActivityPub_root'] . 'actor.php?user=' . $user
  ], $activity), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  return request($user, $dest, $document);
}
