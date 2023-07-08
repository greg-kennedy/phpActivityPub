<?php

// Direct message parser for ActivityPub
//
// this is designed to plug in to our inbox, to keep site-specific code separate
//
// You can adapt this to your own needs; it contains a function, parse_content, which
// will be given the user & content of the message received, and is expected to return
// a new response to be sent back to the originating actor, or false if you can't
// create a suitable response

function parse_content($user, $activityPub)
{
	setlocale(LC_ALL, 'en_EN.UTF8') ;

	switch ($user) {
		case 'events' :
			// our event bot
			// we're really only interested in the content part in $activityPub['object']['content']
			// when a DM comes from a Mastodon server, it's prefixed by @events ; the regex removes that

			$request = trim(preg_replace('#@events#', '', strip_tags($activityPub['object']['content']))) ;

			preg_match('#://([^/]*)/#', $activityPub['actor'], $matches) ;
			$actorName = '@' .  basename($activityPub['actor']) . '@' . $matches[1] ; // turn into @user@host.format

			if (strtolower($request) == 'help') {
				return([ 'type' => 'Note',
						'to' => $activityPub['actor'],
						'published' => strftime('%FT%TZ', time()),
						'content' => "Send the name of a city to see what events we have listed in the next 30 days, eg Berlin",
						'tag' => [[
							'type' => 'Mention',
							'href' => $activityPub['actor'],
							'name' => $actorName
							]],
					]);
			} else {
				// look up info in the database and build a response
				require_once('common/v4database.php') ;

				$sql = sprintf("SELECT title, startdate FROM events WHERE private = 'n' AND ( city LIKE '%%%s%%' OR localisedcity LIKE '%%%s%%') AND  startdate BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL +30 DAY) ORDER BY startdate ASC", $v4read->real_escape_string($request), $v4read->real_escape_string($request)) ;
				$events = $v4read->query($sql) ;

				if ($events->num_rows == 0) {
					return([ 'type' => 'Note',
						'to' => $activityPub['actor'],
						'published' => strftime('%FT%TZ', time()),
						'content' => "Sorry, we have can't find any events matching your request. Try sending the name of a city",
						'tag' => [[
						'type' => 'Mention',
						'href' => $activityPub['actor'],
						'name' => $actorName
						]],
					]);
				} else {
					$content = sprintf("<b>Here's your list of what's coming up in the next 30 days in %s<b><p><p>", $request) ;

					while ($e = $events->fetch_assoc()) {
						$when = strftime('%A %d %B %Y', strtotime($e['startdate'])) ;
						$content .= "<p>" . $e['title'] . " on " . $when ;
					}

					$content .= sprintf("<p><i>See more details at <a href='https://bluf.com/e/%s'>bluf.com/e/%s</a>", strtolower($request), strtolower($request)) ;

					return([ 'type' => 'Note',
						'to' => $activityPub['actor'],
						'published' => strftime('%FT%TZ', time()),
						'content' => $content,
						'tag' => [[
						'type' => 'Mention',
						'href' => $activityPub['actor'],
						'name' => $actorName
						]],
					]);
				}
			}

			break ;

		default:
			// not one of our handled bots
			return false ;
	}
}
