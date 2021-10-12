#!/usr/bin/env bash
set -ex

sudo apt-get install unzip
sudo rm -rf /opt/oracle
sudo mkdir -p /opt/oracle

sudo curl -o /opt/oracle/basic.zip https://download.oracle.com/otn_software/linux/instantclient/213000/instantclient-basic-linux.x64-21.3.0.0.0.zip
sudo curl -o /opt/oracle/sdk.zip https://download.oracle.com/otn_software/linux/instantclient/213000/instantclient-sdk-linux.x64-21.3.0.0.0.zip

sudo unzip /opt/oracle/basic.zip -d /opt/oracle
sudo unzip /opt/oracle/sdk.zip -d /opt/oracle

sudo ln -s /opt/oracle/instantclient_21_3 /opt/oracle/instantclient

sudo rm /opt/oracle/instantclient/libclntsh.so
sudo rm /opt/oracle/instantclient/libocci.so
sudo ln -s /opt/oracle/instantclient/libclntsh.so.12.1 /opt/oracle/instantclient/libclntsh.so
sudo ln -s /opt/oracle/instantclient/libocci.so.12.1 /opt/oracle/instantclient/libocci.so

sudo sh -c 'echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf'
sudo apt-get install -y make php-dev php-pear build-essential libaio1
sudo pecl channel-update pecl.php.net
sudo pecl install oci8

# When you are prompted for the Instant Client location, enter the following:
# instantclient,/opt/oracle/instantclient