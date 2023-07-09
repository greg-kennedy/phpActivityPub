# Mastodon post example

This is an example to show how a post can be formatted so that it displays with a summary text, an image, and a link in the text body.
Our use case is posting details of forthcoming events from our calendar. The formatting is aimed at Mastodon, but should work find on 
other fedi platforms. On Mastodon, the summary (which includes event name and date) will appear as a content warning, with the rest
of the text collapsed; this avoids cluttering up timelines with long event descriptions if people don't want to read them.

    {
      "type": "Note",
      "summary": "Tom of Finland Art & Culture Festival and HUNTER - Launch Party, 21 July 2023",
      "attachment": {
        "summary": "HUNTER, Electrowerkz, Islington",
        "url": "https://bluf.com/photos/events/4662/f5255291-056f-45a9-840d-6723665b79a8_1_201_a.jpg"
      },
    "content": "<p>HUNTER Invites you to the Opening Party of the Tom Of Finland Arts &amp; Culture Festival and launch of HUNTER, your new Hardcore Leather F*tish night.    </p>\n<p>@electrowerkz_ teams up with infamous @brewhunter.dom (who reignited London’s Leather scene with his now-fabled night MASTERY) to present an explosive night on the London scene.    HUNTER is also proud to be the Opening Party for Tom Of Finland Art &amp; Culture London Festival 2023.    </p>\n<p>The Launch will feature a welcome hour zone, but as darkness falls, the CIGAR ZONE, MASTERY ZONE and DARK ZONE (hardcore dress code only) will open for The Hunt. Vintage Cinema, cigars, cru<em>sing, c</em>ttaging, dungeon and hardcore play.</p>\n<p>9p-10p  <strong>FREE ENTY</strong>  RELAXED DRESS CODE FOR WELCOME HOUR ONLY  (STRICT DRESS CODE REQUIRED ALL OTHER ZONES, ACCESS AT 10p)<br />\n10p-3a  <strong>£10 LAUNCH PARTY TICKETS</strong>  HUNTER HARDCORE     </p>\n<p>More info: <a href=\"/profiles/2316\" title=\"SMOKINLEATHER\">SMOKINLEATHER (2316)</a></p><br><br>Electrowerkz, 7 Torrens St, London, United Kingdom<br><a href='https://bluf.com/e/4662'>View on bluf.com</a>"
     }
 
You can post an item like this - assuming it's saved in a file called event.json with curl from the command line:

    curl -X POST -H 'X-API-KEY: {actor POST key}' https://example.com/{install path}/post.php -d @event.json

## Automated posting
We use this on BLUF.com, where our site has an event object, to which we have added method asActivityPub, which returns JSON formatted as
above for an event with an image, or a simple note, for events without an image. Automating posts via the phpActivityPub is simple. You can see our bot in action as @events@bluf.com from your favourite Fediverse platform.

Here's the code-snippets:

        define('FEDI_URL','https://example.com//path/to/activityPub/post.php') ;
        define('APIKEY','POST_KEY for your actor') ;
        
        while ($event = $events->fetch_assoc()) {
		    $e = new \BLUF\Calendar\event($event['id']) ;
		    fedi_post($e->asActivityPub()) ;

		    sleep(rand(5, 20)) ; // avoid flooding
	    }

        function fedi_post($note)
        {
	        $curl = curl_init() ;

	        curl_setopt_array($curl, [
	          CURLOPT_URL => FEDI_URL,
	          CURLOPT_RETURNTRANSFER => true,
	          CURLOPT_ENCODING => "",
	          CURLOPT_MAXREDIRS => 10,
	          CURLOPT_TIMEOUT => 30,
	          CURLOPT_POSTFIELDS => $note,
	          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	          CURLOPT_HTTPHEADER => [
	        	"Content-Type: application/json;charset=utf-8",
	        	"X-API-KEY: " . APIKEY
	          ],
	        ]);

	        $response = curl_exec($curl);
	        $err = curl_error($curl);

	        curl_close($curl);
        }
