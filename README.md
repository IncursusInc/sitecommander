# SiteCommander
SiteCommander is an interactive dashboard module to manage and monitor your Drupal 8 sites.

NOTE: We have applied to have the project be promoted from a sandbox project to a full project on Drupal.org. Stay tuned!

SiteCommander features include:

* Implemented as a Drupal block, so you can put it on the same page as other blocks to make your own dashboard
* Breakdown of published nodes by type, with shortcuts to create new nodes, or browse nodes by type
* Quickly see how many modules you have installed and a shortcut to install new ones
* Quickly see how many authenticated users are currently online (and view a list of them)
* Quickly see how many visitors (non authenticated) are currently online (if using Redis as a caching backend)
* Quickly browse active sessions (by user) and optionally terminate them
* Quickly see today's top site searches
* Full featured Backup Manager, with 1 click backup/restore, backup scheduler
* See when cron last ran and a shortcut to manually run it
* See when the updates checker last ran, and a shortcut to manually check for updates
* Shortcut to clear/rebuild the Drupal cache
* CPU load average gauges, so you can monitor your server's workload
* Status of server memory pool
* Integration with Redis for stats, ability to clear Redis cache
* Integration with PHP Opcache for stats, ability to clear Opcache
* Integration with APC for stats, ability to clear APC cache
* Shortcut to clear out old aggregated CSS/JS files that just take up space
* See how much disk space your full Drupal install is taking up
* Integration with the MailChimp module to see some high level statistics about your lists, subscribers, open rates, and click through rates.
* Tabbed interface for ease of navigation
* More to come!

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot1_2.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot2_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot3_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot4_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot5_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot6_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot7_1.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot8_2.png?itok=BpuRa1jE "SiteCommander Screenshot")

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/sitecommander-screenshot9_2.png?itok=BpuRa1jE "SiteCommander Screenshot")

# Installation Instructions

1. Download and install the module (./modules/custom/sitecommander)
2. Create a new page (e.g. /system-status), and add the Site Commander block to it. It is a full-width block, so put it in the main content area, etc. If you are also using our Redistat module, they can both be on the same page, as they are blocks. :)
3. Be sure to restrict access to the new page to admins only or what not.
4. Many of the icons on the page are interactive, so click on them to add new nodes, put the system in maintenance mode, etc.
5. NOTE: currently, the CPU load averages is a feature of Linux/UNIX based systems. We're working on a Windows solution, but there is no ETA on that.
