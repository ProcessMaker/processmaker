HOST=$1
PORT=$2
USER=$3
PASSWORD=$4
DB=$5
TMPFILE=$6

CRED="-h $HOST -P $PORT -u $USER -p$PASSWORD"
mysql $CRED -e "drop database $DB"
mysql $CRED -e "create database $DB"
mysql $CRED $DB < $TMPFILE