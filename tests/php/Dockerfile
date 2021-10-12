FROM php:8.0

RUN apt-get update
RUN apt-get install unzip

RUN mkdir -p /opt/oracle

RUN curl -o /opt/oracle/basic.zip https://download.oracle.com/otn_software/linux/instantclient/213000/instantclient-basic-linux.x64-21.3.0.0.0.zip
RUN curl -o /opt/oracle/sdk.zip https://download.oracle.com/otn_software/linux/instantclient/213000/instantclient-sdk-linux.x64-21.3.0.0.0.zip

RUN unzip /opt/oracle/basic.zip -d /opt/oracle
RUN unzip /opt/oracle/sdk.zip -d /opt/oracle

RUN ln -s /opt/oracle/instantclient_21_3 /opt/oracle/instantclient
RUN rm /opt/oracle/instantclient/libclntsh.so
RUN rm /opt/oracle/instantclient/libocci.so
RUN rm /opt/oracle/instantclient/libclntshcore.so

RUN ln -s /opt/oracle/instantclient/libclntsh.so.12.1 /opt/oracle/instantclient/libclntsh.so
RUN ln -s /opt/oracle/instantclient/libclntshcore.so.12.1 /opt/oracle/instantclient/libclntshcore.so
RUN ln -s /opt/oracle/instantclient/libocci.so.12.1 /opt/oracle/instantclient/libocci.so

RUN sh -c 'echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf'

RUN apt-get install -y make build-essential libaio1
RUN pecl channel-update pecl.php.net

RUN echo 'instantclient,/opt/oracle/instantclient' | pecl install oci8 && ldconfig

ENV ORACLE_HOME=/opt/oracle/instantclient
ENV LD_LIBRARY_PATH /opt/oracle/instantclient

RUN docker-php-ext-install pdo
RUN docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,$ORACLE_HOME,12.1 && docker-php-ext-install pdo_oci
RUN docker-php-ext-configure mysqli --with-mysqli=mysqlnd && docker-php-ext-install pdo_mysql

RUN apt-get install -y libpq-dev
RUN docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && docker-php-ext-install pgsql pdo_pgsql

RUN apt-get clean -y && rm -rf /var/lib/apt/lists/*