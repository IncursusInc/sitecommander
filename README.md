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
* Pusher Integration Module
* ssh2 module for PHP (optional, but needed for backup mirroring functionality)

# Known Issues

* Certain functionality, such as CPU Load Averages, currently only works in Linux/UNIX environments. Support for Windoze servers will be added soon.

* Currently, this module works best when the environment is running only ONE (1) Drupal server. If you are running multiple Drupal servers in a load-balanced configuration, please keep in mind that only limited testing has been done in such environments. Feedback and testing help is appreciated!

* Certain parts of the look/feel are going to depend on your theme, which means your install may not look like these screenshots. We are working on making the CSS more encapsulated to provide a more consistent experience.

# Screenshots

For screenshots, [click here](http://incurs.us/open-source-projects/sitecommander).

# Installation Instructions

1. Download and install the module (./modules/custom/sitecommander)
2. Run an update with Drush to pull in dependencies: "drush up" (Be sure to have the Composer Manager module installed!)
3. Configure it either on the modules page, or via admin/config/sitecommander
4. Create a new page (e.g. /system-status), and add the Site Commander block to it. It is a full-width block, so put it in the main content area, etc. If you are also using our Redistat module, they can both be on the same page, as they are blocks. :)
5. Be sure to restrict access to the new page to admins only or what not.
6. Many of the icons on the page are interactive, so click on them to add new nodes, put the system in maintenance mode, etc.

# Configuration

Again, to configure, click the gears icon on your SiteCommander page, go to the configuration page and look for SiteCommander, or just navigate to /admin/config/sitecommander.

## General Settings

### Exclude Specific Content Types From Dashboard

Select the content types that would like to exclude from appearing on the dashboard. Currently, this only affects the "Content Items By Type" widget.

### Include Bootstrap CSS via CDN

SiteCommander uses the most-awesome Bootstrap CSS framework. If you have a theme that is based on Bootstrap, you won't need to check this box. If you already have a theme using Bootstrap, and you check this box, it will still work, but modals will disappear as soon as they appear. Also, there is no reason for an extra page request, so disable it if you are already using it elsewhere!

### Include jQuery via CDN

Most people, if not everyone, won't need to check this, but if for some bizarre reason, you aren't using jQuery in Drupal, check this box.

### Dashboard AJAX Refresh Rate (in seconds)

Most of the SiteCommander statistics will refresh automatically/periodically. Just provide some sensible value here. We recommend the default, which is every 60 seconds, but edit to taste.

## Tag Cloud Widget Settings

### Name of the Taxonomy Vocabulary to Use in the Tag Cloud Widget

By default, SiteCommander will use the "tags" vocabulary to generate the tag cloud. However, you are free to specify whatever vocabulary you wish. The only caveat is that the terms in the vocabulary actually be tied to content types.

### Restrict the Tag Cloud to This Many Entries

This simply limits the tag cloud to a certain number of terms (important if you have a boat load of terms in your vocabulary!).

### Starting Color for Tags (Smallest Frequency)

Pretty self-explanatory. Terms with the lowest number of occurances in the tag cloud will start out at this color.

### Ending Color for Tags (Largest Frequency)

Terms with the largest number of occurances will end up with this color. Terms will be shaded to some color between the starting color and ending color, again, depending on how often they are used/seen.

