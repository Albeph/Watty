from datetime import datetime
import json
from pytz import timezone
import requests
import asyncio
import os
import time
import csv

from tapo import ApiClient

with open('./pass.csv', mode='r') as file:
    reader = csv.DictReader(file)
    credentials = next(reader)
    email = credentials['email']
    password = credentials['pass']

device_ip = os.getenv('device_ip')
zone_id = os.getenv('zone_id')
product_id = os.getenv('product_id')
server = os.getenv('server')
if not device_ip:
    raise ValueError("device_ip environment variable not set")
if not zone_id:
    raise ValueError("zone_id environment variable not set")
if not product_id:
    raise ValueError("product_id environment variable not set")
if not server:
    raise ValueError("server environment variable not set")

print(device_ip)
print(zone_id)
print(product_id)
print(server)

def send_value(current_power):
    values = {
            "zona": zone_id,
            "prodotto_id": product_id,
            "value": current_power,
            "timestamp": datetime.now(timezone('CET')).isoformat() #pytz.timezone necessario in quanto la data veniva visualizzata in Coordinated Universal Time (UTC)        
        }
    url = f"http://{server}"
    headers = {'Content-Type': 'application/json'}
    try:
        response = requests.post(url, data=json.dumps(values), headers=headers)
        print(f"Inviato {values} a {url} con risposta {response.status_code}")
    except requests.exceptions.RequestException as e:
        print(f"Errore durante l'invio dei dati: {e}")

def ping(ind):
    ip = ind.split(':')[0]
    response = os.system(f"ping -c 1 {ip} > /dev/null 2>&1")
    return response == 0

async def main():

    client = ApiClient(email, password)

    current_power = 0

    # Funzione per inviare un ping
    
    while True:
        # Tenta di pingare il dispositivo
        if not ping(device_ip):
            current_power = -1
        else:
            device = await client.p110(device_ip)

            current_power_t = await device.get_current_power()
            current_power = current_power_t.to_dict().get('current_power')
        
        
        send_value(current_power)
        print(f"Current power: {current_power}")
        time.sleep(10)

if __name__ == "__main__":
    asyncio.run(main())