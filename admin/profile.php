<?php

// a simple page to allow updating Actor details for ActivityPub
// Nigel Whitfield, July 2023

include_once 'functions.php';

if (!isset($_REQUEST['user'])) {
	// redirect to the admin main page
	header('Location: index.php') ;
}

$db = new SQLite3('db.sqlite3', SQLITE3_OPEN_READWRITE);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// update the record, then return to the index

	if ($_REQUEST['action'] == 'update') {
		$profile = query(
			$db,
			'UPDATE profile SET summary = ?, url = ?, name = ?, icon = ?, homepage = ? WHERE user = ?',
			$_POST['summary'],
			$_POST['url'],
			$_POST['name'],
			$_POST['icon'],
			$_POST['homepage'],
			$_POST['user']
		);
	} else {
		$profile = query(
			$db,
			'INSERT INTO profile(user, summary, url, name, icon, homepage) VALUES(?, ?, ?, ?, ?, ?)',
			$_POST['user'],
			$_POST['summary'],
			$_POST['url'],
			$_POST['name'],
			$_POST['icon'],
			$_POST['homepage']
		);
	}


	$db->close() ;


	header('Location: index.php') ;
}

$profile =  query($db, 'SELECT summary, url, name, icon, homepage FROM profile WHERE user=?', $_REQUEST['user']);
$db->close() ;

?>
<html>

<head>
	<title>phpAP profile for <?=$_REQUEST['user']; ?></title>
</head>

<body>
	<h1>phpAP profile for <?=$_REQUEST['user']; ?></h1>
	<form method="post" action="profile.php">
		<input type="hidden" name="user" value="<?=$_REQUEST['user']; ?>">
		<?php

if (count($profile) > 0) {
	?>
		<p><label>Summary<br />
				<input type="text" name="summary" placeholder="Summary description" width="150" value="<?=$profile[0]['summary'] ?>"></label></p>
		<p><label>URL<br />
				<input type="url" name="url" placeholder="Web link" width="150" value="<?=$profile[0]['url'] ?>"></label></p>
		<p><label>Name<br />
				<input type="text" name="name" placeholder="Name" width="150" value="<?=$profile[0]['name'] ?>"></label></p>
		<p><label>Icon url<br />
				<input type="url" name="icon" width="150" value="<?=$profile[0]['icon'] ?>"></label></p>
		<p><label>Homepage url<br />
				<input type="url" name="homepage" width="150" value="<?=$profile[0]['homepage'] ?>"></label></p>
		<input type="hidden" name="action" value="update">
		<?php
} else {
		?>
		<p><label>Summary<br />
				<input type="text" name="summary" placeholder="Summary description" width="150"></label></p>
		<p><label>URL<br />
				<input type="url" name="url" placeholder="Web link" width="150"></label></p>
		<p><label>Name<br />
				<input type="text" name="name" placeholder="Name" width="150"></label></p>
		<p><label>Icon url (png)<br />
				<input type="url" name="icon" width="150"></label></p>
		<p><label>Homepage url<br />
				<input type="url" name="homepage" width="150"></label></p>
		<input type="hidden" name="action" value="add">
		<?php
	}

?>
		<input type="submit" value="save">

	</form>
	<p>
		<a href="index.php">Back to index</a>
	</p>
</body>

</html>