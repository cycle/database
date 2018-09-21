#!/usr/bin/env bash
set -ex
sudo echo "binlog-format = MIXED" >> /etc/mysql/my.cnf
sudo cat /etc/mysql/my.cnf
sudo service mysql restart