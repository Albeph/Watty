#!/bin/sh

chmod 777 -R /home/sftpuser/upload

/usr/sbin/sshd -D &

/usr/local/bin/check_files.sh
