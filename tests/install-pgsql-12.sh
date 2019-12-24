#!/usr/bin/env bash
set -ex
# from Doctrine
sudo service postgresql stop
sudo apt-get remove -q 'postgresql-*'
sudo apt-get update -q
sudo apt-get install -q postgresql-12 postgresql-client-12
sudo cp /etc/postgresql/{9.6,10}/main/pg_hba.conf
sudo service postgresql restart