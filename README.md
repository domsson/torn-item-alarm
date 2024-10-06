# item-alarm

A web-based tool that watches the in-game marketplace of torn.com for bargains and notifies the user if any were found.

![item-alarm preview](https://github.com/domsson/torn-item-alarm/blob/main/itemalarm.png?raw=true)

# Dependencies

 - Apache (due to reliance on some rewrite magic for the URLs)
 - PHP
 - Twig

# Setup

 - Install the dependencies
 - Plop the contents of the repo into `/var/www/itemalarm` or wherever you want it
 - Set up an Apache config in `/etc/apache2/sites-available` and enable it
 - Possibly deal with any errors stemming from non-existing directories or missing permissions
 - ???
 - Profit

# Usage

 - Add items of interest
 - Configure the items by clicking the item image (reveals a 'cog' icon)
 - Set 'alarm' price to the price you want to buy for
 - Set 'reference' price to the price you sell for (optional, but used to calculate profits)
 - Click the floppy icon to save (note: can only edit one at a time, saving reloads page)
 - Start monitoring items by clicking the play button in front of them
