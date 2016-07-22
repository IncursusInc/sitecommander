# drupalstat
DrupalStat is an interactive dashboard module to manage and monitor your Drupal 8 sites.

DrupalStat features include:

* CPU load average gauges, so you can monitor your server's workload
* Implemented as a Drupal block, so you can put it on the same page as our Redistat block module, or other blocks to make your own dashboard
* Breakdown of published nodes by type, with shortcuts to create new nodes, or browse nodes by type
* Quickly see how many modules you have installed and a shortcut to install new ones
* Quickly see how many authenticated users and visitors are currently online
* Quickly see today's top site searches
* See when cron last ran and a shortcut to manually run it
* See when the updates checker last ran, and a shortcut to manually check for updates
* Shortcut to clear/rebuild the Drupal cache
* See how much disk space your full Drupal install is taking up
* Integration with the MailChimp module to see some high level statistics about your lists, subscribers, open rates, and click through rates.
* More to come!

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/drupalstat-screenshot.png?itok=uI38msGu&z "DrupalStat Screenshot")

# Notes

Proper documentation coming soon. But for now, here are a few tips to get it going:

1. Download and install the module (./modules/custom/drupalstat)
2. Create a new page (e.g. /system-status), and add the DrupalStat block to it. It is a full-width block, so put it in the main content area, etc. If you are also using our Redistat module, they can both be on the same page, as they are blocks. :)
3. Be sure to restrict access to the new page to admins only or what not.
4. Many of the icons on the page are interactive, so click on them to add new nodes, put the system in maintenance mode, etc.
5. NOTE: currently, the CPU load averages is a feature of Linux/UNIX based systems. We're working on a Windows solution, but there is no ETA on that.
