<?php

// Basic profile display page for ActivityPub
// This is an addition to the phpActivity Pub to allow remote followers to see
// a simple profile page that lists the other people following one of our bots
//
// The link to the 'orginal profile' that people will see on sites like Mastodon
// is the 'url' field that you can configure via the admin/profile page
//
// For a more friendly URL, you may want to use a Rewrite rule like this
// RewriteRule ^/users/(.*) /activitypub/user.php?user=$1 [L]

// Nigel Whitfield, July 2023

// common helper functions and definitions
include_once 'admin/functions.php';


 // connect to db, look up user info (verify the actor exists)
$db = new SQLite3("admin/db.sqlite3", SQLITE3_OPEN_READONLY);
$info = query($db, 'SELECT user, type, rsa_public FROM acct WHERE user=?', $_GET['user']);


if (count($info) > 0) {
	// Found an account by this name.

	$profile = query($db, 'SELECT summary, url, name, icon, homepage FROM profile WHERE user=?', $_GET['user']) ;

	$followers = query($db, 'SELECT dest FROM sub WHERE user=?', $_GET['user']) ;
	$db->close();
} else {
	$db->close(); ?>
<html>

<head>
	<title>No user found</title>
</head>

<body>
	<p>No such user</p>
</body>

</html>
<?php
exit ;
}

?>
<html>

<head>
	<title>ActivityPub Profile for @<?= $_GET['user']; ?></title>
</head>

<body>
	<h1>Profile information for @<?= $_GET['user']; ?></h1>
	<?php

if (count($profile) > 0) {
	?>
	<p><img src="<?= $profile[0]['icon']; ?>" width="125" alt="profile icon"></p>
	<h3><?= $profile[0]['name']; ?></h3>
	<p><?= $profile[0]['summary']; ?></p>
	<p><a href="<?= $profile[0]['homepage']; ?>">More info</a></p>
	<h4>Followers: <?= count($followers) ; ?></h4>
	<ul>
		<?php
	foreach ($followers as $acct) {
		preg_match('#://([^/]*)/#', $acct['dest'], $matches) ;
		echo '<li><a href="' . $acct['dest'] . '" target="_blank">@' . basename($acct['dest']) . '@' . $matches[1] . '</a></li>' ;
	} ?>
	</ul>
	<?php
} else { ?>
	<p>Sorry, can't find profile information for this user</p>
	<?php
} ?>
</body>

</html>