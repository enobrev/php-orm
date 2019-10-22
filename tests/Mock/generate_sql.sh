#!/usr/bin/env bash

HOST=192.168.1.2

# generate sql.json
../../bin/sql_to_json.php -h ${HOST} -u dev -p -n dev_orm_mock

# generate mysql.sql
mysqldump -h ${HOST} -u dev -p --no-data --skip-extended-insert --compact --skip-quote-names --databases dev_orm_mock > mysql.sql

# download script for converting mysql database to sqlite - https://github.com/dumblob/mysql2sqlite/blob/master/mysql2sqlite
if [ ! -f ./mysql2sqlite.sh ]; then
    wget https://raw.githubusercontent.com/dumblob/mysql2sqlite/master/mysql2sqlite
    mv mysql2sqlite mysql2sqlite.sh
    chmod +x mysql2sqlite.sh
fi

# generate sqlite.sql
./mysql2sqlite.sh mysql.sql > sqlite.sql
sed -i '/CREATE DATABASE/d' ./sqlite.sql
sed -i '/CONSTRAINT/d' ./sqlite.sql
sed -i '/PRAGMA/d' ./sqlite.sql
sed -i '/TRANSACTION/d' ./sqlite.sql
sed -i '/CREATE INDEX/d' ./sqlite.sql
sed -i 's/address_id int(10)  NOT NULL/address_id INTEGER/' ./sqlite.sql
sed -i 's/user_id int(10)  NOT NULL/user_id INTEGER/' ./sqlite.sql