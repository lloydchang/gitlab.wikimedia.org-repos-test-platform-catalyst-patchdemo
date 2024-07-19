The public https://patchdemo.wmcloud.org/ website currently runs on an `g4.cores8.ram16.disk20` instance at [Wikimedia Cloud VPS](https://wikitech.wikimedia.org/wiki/Portal:Cloud_VPS).

16GB RAM is required to avoid occasional OOM issues in the `rdfind` cron job (#607), in MySQL under heavy load (#612), and when building wikis with the Codex library (#622).

For extra space an additional 80GiB volume is attached to `/dev/sdb` and mounted as `/srv`.

This requires some extra setup to ensure that wiki and mysql data is stored on this volume, and was originally implemented in issues #233 and #599.

## Adding a volume

Follow the instructions at https://wikitech.wikimedia.org/wiki/Help:Adding_disk_space_to_Cloud_VPS_instances

### Moving wikis
----
Symlink the wikis folder to point to the new volume:

```sh
sudo rsync -a /var/www/html/wikis/ /srv/patchdemo-wikis/
sudo mv /var/www/html/wikis /var/www/html/wikis-old
sudo ln -s /srv/patchdemo-wikis /var/www/html/wikis
```

When everything is verified, the old folder can be removed:

```sh
sudo rm -rf /var/www/html/wikis-old
```

### Moving MySQL data

Move the mysql data files, then change the datadir setting in the config:

```sh
sudo service mysql stop
sudo rsync -a /var/lib/mysql/ /srv/patchdemo-db/
sudo sed -i 's/#\?\(datadir *= *\).*/\1\/srv\/patchdemo-db/' /etc/mysql/mariadb.conf.d/50-server.cnf
sudo service mysql start
```

When everything is verified, the old folder can be removed:
```sh
sudo rm -rf /var/lib/mysql
```
