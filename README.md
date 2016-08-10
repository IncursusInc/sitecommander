# SiteCommander
SiteCommander is an interactive dashboard module to manage and monitor your Drupal 8 sites.

NOTE: We have applied to have the project be promoted from a sandbox project to a full project on Drupal.org. Stay tuned!

SiteCommander features include:

- Implemented as a Drupal block, so you can put it on the same page as other blocks to make your own dashboard
- Interface
  - Tabbed interface for ease of navigation
  - Most data elements are updated via AJAX in near-realtime
  - Most site actions are performed via AJAX for a seamless experience
- Main Dashboard for primary site actions
  - Breakdown of published nodes by type, with shortcuts to create new nodes, or browse nodes by type
  - Interactive content tag cloud (based on tags/taxonomy)
  - Quickly see how many modules you have installed and a shortcut to install new ones
  - Quickly see how many authenticated users are currently online (and view a list of them)
  - Quickly see how many visitors (non authenticated) are currently online (if using Redis as a caching backend)
  - Quickly browse active sessions (by user) and optionally terminate them
  - Quickly see today's top site searches
  - See when cron last ran and a shortcut to manually run it
  - See when the updates checker last ran, and a shortcut to manually check for updates
  - Shortcut to clear/rebuild the Drupal cache
  - Shortcut to clear the Redis cache (if installed and used as a backend cache for Drupal)
  - Shortcut to clear the PHP OpCache (if enabled)
  - Shortcut to clear out old aggregated CSS/JS files that just take up space
  - See how much disk space your full Drupal install is taking up
  - Integration with the MailChimp module for high level stats about lists, subscribers, open rates, & click through rates
- Server Status Dashboard
  - CPU load average gauges
  - Server memory pool statistics
- Database Performance Dashboard
  - Connection statistics
  - Memory usage
  - Performance gauges (key buffers, query cache, etc.)
  - Currently works with MySQL/MariaDB only. Other databases will be supported as needed/requested.
- Caching Performance Dashboard
  - Integration with Redis
  - Integration with APC
  - Integration with the PHP OpCache
  - Visual metrics in gauges
- Storage Health Dashboard
  - Shows visual usage information on all attached/mounted storage devices
- Full featured Backup Manager
  - Run ad hoc backups in the background or foreground
  - One click backup/restore
  - Support for retention strategy
  - Automated backup scheduler (with no cron delays for your users!)
- Live Broadcast Manager
  - Allows you to broadcast realtime growl-like messages to your current site visitors
  - Control message type/color, and screen position for the notifications all from within SiteCommander
- More to come!

# Requirements

* Drupal 8.x
* Drush installed
* PHP 5.5 or higher (untested under PHP 7)
* Composer Manager Module
* ssh2 module for PHP (optional, but needed for backup mirroring functionality)

# Known Issues

* Certain functionality, such as CPU Load Averages, currently only works in Linux/UNIX environments. Support for Windoze servers will be added soon.

* Currently, this module works best when the environment is running only ONE (1) Drupal server. If you are running multiple Drupal servers in a load-balanced configuration, please keep in mind that only limited testing has been done in such environments. Feedback and testing help is appreciated!

* Certain parts of the look/feel are going to depend on your theme, which means your install may not look like these screenshots. We are working on making the CSS more encapsulated to provide a more consistent experience.

# Screenshots

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot1_2.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot2_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot3_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot4_0.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot5_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot6_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot7_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot8_0.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot9_0.png?itok=BpuRa1jE "SiteCommander Screenshot")

# Installation Instructions

1. Download and install the module (./modules/custom/sitecommander)
2. Run an update with Drush to pull in dependencies: "drush up" (Be sure to have the Composer Manager module installed!)
3. Configure it either on the modules page, or via admin/config/sitecommander
4. Create a new page (e.g. /system-status), and add the Site Commander block to it. It is a full-width block, so put it in the main content area, etc. If you are also using our Redistat module, they can both be on the same page, as they are blocks. :)
5. Be sure to restrict access to the new page to admins only or what not.
6. Many of the icons on the page are interactive, so click on them to add new nodes, put the system in maintenance mode, etc.
