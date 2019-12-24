#!/usr/bin/env bash
set -ex
sudo apt update
sudo apt install unixodbc-dev
pecl install pdo_sqlsrv-5.6.0
curl https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -
curl https://packages.microsoft.com/config/ubuntu/14.04/prod.list | sudo tee /etc/apt/sources.list.d/mssql.list
sudo apt update

ACCEPT_EULA=Y sudo apt-get install -qy msodbcsql17 mssql-tools unixodbc libssl1.0.0