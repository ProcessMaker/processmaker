#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.
#
# If you have user-specific configurations you would like
# to apply, you may also create user-customizations.sh,
# which will be run after this script.

# Install docker, which we use to run scripts
sudo apt-get -y \
    -o Dpkg::Options::="--force-confdef" \
    -o pkg::Options::="--force-confold" \
install docker.io

# Start the docker service and ensure it runs at startup
sudo systemctl start docker
sudo systemctl enable docker

# Add vagrant to our docker group so that our webapp (which runs under vagrant user) can create docker containers
sudo usermod -a -G docker vagrant

# Restart PHP, to ensure that it picks up the new group membership
sudo service php7.2-fpm restart

# Install echo server which will help run socket.io locally
sudo npm install -g laravel-echo-server

# Copy over appropriate supervisor files to start echo server and horizon
sudo cp /home/vagrant/processmaker/homestead/etc/supervisor/conf.d/* /etc/supervisor/conf.d/

# Reload supervisor to ensure starting echo server and horizon
sudo service supervisor stop
sudo service supervisor start