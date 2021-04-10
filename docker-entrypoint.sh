#!/bin/bash
set -e
echo "Initializing..."
service docker start
service redis-server start
# npm run dev
echo "Starting supervisord"
/usr/bin/supervisord
