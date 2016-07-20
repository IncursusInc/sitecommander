# drupalstat
DrupalStat is an interactive dashboard module to manage and monitor your Drupal 8 installs.

![alt text](http://incurs.us/sites/default/files/styles/juicebox_small/public/2016-07/drupalstat-screenshot_2.png?itok=1HZBcaut "DrupalStat Screenshot")

# Notes

Proper documentation coming soon. But for now, here are a few tips to get it going:

1. Download and install the module (./modules/custom/drupalstat)
2. Create a new page (e.g. /system-status), and add the DrupalStat block to it. It is a full-width block, so put it in the main content area, etc. If you are also using our Redistat module, they can both be on the same page, as they are blocks. :)
3. Be sure to restrict access to the new page to admins only or what not.
4. Many of the icons on the page are interactive, so click on them to add new nodes, put the system in maintenance mode, etc.
5. NOTE: currently, the CPU load averages is a feature of Linux/UNIX based systems. We're working on a Windows solution, but there is no ETA on that.
