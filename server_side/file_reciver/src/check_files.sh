#!/bin/sh


while true; do
    FILE1="/home/sftpuser/upload/mapping_elettr_conn.csv"
    FILE2="/home/sftpuser/upload/zone_room.csv"

    if [ -f "$FILE1" ] && [ -f "$FILE2" ]; then
        echo "Both files are present. Stopping the container."
        exit 0
    fi
    echo "Waiting for the files."
    sleep 10
done
