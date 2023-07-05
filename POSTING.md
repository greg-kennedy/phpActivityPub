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
 
