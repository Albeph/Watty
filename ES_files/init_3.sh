#!/bin/sh

while true; do
  response=$(curl -s -X POST http://kibana:5601/api/saved_objects/_import \
    -H "kbn-xsrf: true" \
    --form file=@/usr/local/bin/ES_files/dashboard.ndjson)

  echo "$response"

  if echo "$response" | grep -q '"success":true'; then
    break
  else
    echo 'Unsupported Media Type error, retrying...'
  fi

  echo '3. Waiting for Elasticsearch...'
  sleep 5
done

exit 0