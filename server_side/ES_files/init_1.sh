#!/bin/sh

until curl -s -X PUT 'http://elasticsearch:9200/energy-stream' -H 'Content-Type: application/json' -d'
      {
        "mappings": {
          "properties": {
            "timestamp": {
              "type": "date"
            },
            "nome_zona": {
              "type": "keyword"
            },
            "elettrodomestico": {
              "type": "keyword"
            },
            "potenza_istantanea": {
              "type": "double"
            },
            "probability_percentages": {
              "type": "double"
            },
            "state": {
              "type": "keyword"
            },
            "role": {
              "type": "keyword"
            }
          }
        }
      }
      '; do
        echo '1. Waiting for Elasticsearch...'
        sleep 5
      done

      exit 0