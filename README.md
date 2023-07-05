# phpActivityPub
Greg Kennedy, 2023

Small, post-only ActivityPub implementation in PHP

## Overview
This is a PHP-based project to allow a host to post messages into the Fediverse to subscribed Followers.  It implements the minimum of [the ActivityPub spec](https://www.w3.org/TR/activitypub/) for server-to-server federation, specifically:
* a WebFinger response at /.well-known/webfinger to describe Actor inboxes and accounts
* an Inbox which receives (and approves) Follow and Undo-Follow requests
* a post.php webhook which accepts a post and broadcasts it to Followers' inboxes

These features together allow the host's accounts to be discoverable by other ActivityPub servers, and they may issue Follow requests which are automatically approved.  When this host wants to make a post, a cURL call will then broadcast the message to all Followers who have opted in.

It is similar to [express-activitypub](https://github.com/dariusk/express-activitypub) by Darius Kazemi, except written in PHP instead of Node.js.

There is a basic human-readable Index page with information about the Actors located on the server.  For managing Actors on this host, or manual posting, there is an admin page as well.  Newly created actors receive a randomized API Key, which must be included in the header of subsequent calls to the post.php webhook.

Note that this extremely reduced spec is missing a lot of critical functionality one would expect in an ActivityPub service - for example, phpActivityPub does not accept posts from others.  It is thus mostly useful as a tool for bots, relays (RSS / Twitter / etc), or other read-only broadcast applications.

## Requirements
* PHP
  * php-sqlite3
  * php-openssl
  * php-curl
* Web server: I use Apache with `.htaccess` rewrites, you will need alternate rules for other servers

## Installation
Clone the repository into your web service HTML tree.

The `admin/` folder is password-protected using HTTP Basic auth with a `.htpasswd` file.  Use the `htpasswd` utility to create a username and password for logging in.

## Posting
Log into the Admin page and click "create" to set up a new Actor.  The server will provide a random string of characters for your Key, which must be present in an HTTP "X-API-KEY" header to post.

Posts should be JSON and will be added to the necessary Activity fields to build a complete post.  Some examples of post body:

```json
{ "content": "Hello, world!" }
```

creates a Note (the default) with the text "Hello, world!"

```json
{ "type": "Link", "url": "http://www.example.com" }
```

```json
{ "type": "Image", "url": "http://www.example.com/image.jpg" }
```

etc.

# Nigel Whitfield updates, July 2023
This fork provides an additional profile page within admin, allowing youto populate some of the information that will be displayed when people search for your actor.

It additionally addresses a couple of minor issues relevant to my own installation, viz database creation, and an Apache re-write.
