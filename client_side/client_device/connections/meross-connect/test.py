from datetime import datetime
import json
from pytz import timezone
import requests
import asyncio
import os
import time
import csv

#from meross_iot.controller.mixins.electricity import ElectricityMixin
from meross_iot.http_api import MerossHttpClient
from meross_iot.manager import MerossManager

with open('./pass.csv', mode='r') as file:
    reader = csv.DictReader(file)
    credentials = next(reader)
    email = credentials['email']
    password = credentials['pass']

device_ip = os.getenv('device_ip')
device_nm = os.getenv('device_nm')
zone_id = os.getenv('zone_id')
product_id = os.getenv('product_id')
server = os.getenv('server')
if not device_ip:
    raise ValueError("device_ip environment variable not set")
if not device_nm:
    raise ValueError("device_nm environment variable not set")
if not zone_id:
    raise ValueError("zone_id environment variable not set")
if not product_id:
    raise ValueError("product_id environment variable not set")
if not server:
    raise ValueError("server environment variable not set")

print(device_ip)
print(device_nm)
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
    try:
        # Setup the HTTP client API from user-password
        http_api_client = await MerossHttpClient.async_from_user_password(email=email, password=password, api_base_url="https://iotx-eu.meross.com")

        # Setup and start the device manager
        manager = MerossManager(http_client=http_api_client)
        await manager.async_init()

        check=0

        while True:
                
            if not ping(device_ip):
                instant_consumption = -1
                check=0
            else:

                if check == 0:
                    await manager.async_device_discovery()
                    devs = manager.find_devices(device_name=device_nm)
                    print("No electricity-capable device found...")
                    instant_consumption = -1
                    if len(devs) > 0: 
                        dev = devs[0]
                        check=1
                        await dev.async_update()
                    else:
                        time.sleep(10)
                else:    

                    # Update device status: this is needed only the very first time we play with this device (or if the
                    #  connection goes down)

                    # Read the electricity power/voltage/current
                    metrics = await dev.async_get_instant_metrics()
                    instant_consumption = metrics.power
            send_value(instant_consumption)
            print(f"Current power: {instant_consumption}")
            time.sleep(10)

    except KeyboardInterrupt:
        print("Interrupted by user")

        # Close the manager and logout from http_api
        manager.close()
        await http_api_client.async_logout()

if __name__ == '__main__':
    if os.name == 'nt':
        asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
    loop = asyncio.get_event_loop()
    loop.run_until_complete(main())
    loop.stop()