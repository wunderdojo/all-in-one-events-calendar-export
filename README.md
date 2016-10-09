#All in One Events Calendar Export

**Contributors**: James Currie

**Tags:** All in One Events Calendar

**Requires at least**: 4.4 

**Tested up to:** 4.6.1

**Stable tag:** 1.0.0


Add on to the WordPress All in One Event Calendar by Time.ly that allows exporting events to rtf. Filter events to be exported by date range and, optionally, category. Developed using All in One Event Calendar version 2.5.11. Not tested with earlier versions.


All in One Events Calendar can be found here: https://wordpress.org/plugins/all-in-one-event-calendar/


This add on was created for a single client and makes certain assumptions (ex: that All in One Event Calendar is already installed as well as the Venues add-on). It is a very simple tool with limited options but would make an easy jumping off point for anyone with the need to do something similar. In particular, if you're having trouble figuring out how to do custom event queries for your own theme / widget / whatever this will show you how to do it. 

![democast][cast]

[cast]: https://github.com/wunderdojo/all-in-one-events-calendar-export/blob/master/assets/export-events.gif "Democastgit add"

##NOTES
Recurring events are listed only once on the date they first occur, like so:
* * *

**SATURDAY, OCT. 8 - FRIDAY, OCT. 14**

**Event Title**
This is the description of the event. Event start - end times. $ticket price. **Venue**: Street Address, City, State, Zip; ticket url OR venue url OR organizer url
* * *


If the event has start / end times specified they will be listed, same for ticket price. It outputs only one URL per event -- first it looks for a ticket URL, if none exists it looks for a venue URL, and then it checks for an organizer URL.

After any recurring events the rest of the events for that day are listed.

Have questions, see a bug or need to hire someone for some All in One Event Calendar customizations or other custom plugin work? Email <a href='mailto:info@wunderdojo.com'>info@wunderdojo.com</a>

