#!/bin/sh

until curl -s -X PUT 'http://elasticsearch:9200/energy-consumption' -H 'Content-Type: application/json' -d'
      {
        "mappings": {
          "properties": {
            "f_timestamp": {
              "type": "date"
            },
            "nome_zona": {
              "type": "keyword"
            },
            "elettrodomestico": {
              "type": "keyword"
            },
            "consumo_Wm": {
              "type": "double"
            }
          }
        }
      }
      '; do
        echo '2. Waiting for Elasticsearch...'
        sleep 5
      done

      exit 0