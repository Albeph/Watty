import random
from pyspark.sql import SparkSession

spark = SparkSession.builder.appName("Elettrodomestici").getOrCreate()

elettrodomestici = {
    "Frigorifero": [(100, 300), (1, 10)],
    "Climatizzatore": [(100, 300), (1, 10)],
    "Forno": [(60, 2500), (1, 10)],
    "Microonde": [(60, 2500), (1, 10)],
    "TV": [(50, 200), (0.5, 6)],
    "Robot_Cucina": [(5, 1200), (1, 4)],
    "Rilevatore_allagamento": [(1, 5)],
    "Rilevatore_gas": [(1, 5)],
    "Lampade": [(1, 60)],
    "Luci": [(1, 60)],
    "Sistema_surround": [(6, 800), (1, 5)],
    "Computer": [(20, 1000), (1, 20)],
    "Monitor": [(3, 500), (0.5, 2)],
    "Asciugacapelli": [(1000, 2000)],
    "Caldaia": [(10, 91), (2, 7)],
    "Lavatrice": [(500, 2400), (10, 20)],
    "Other": []
}

def determina_stato(elettrodomestico, potenza):
    if elettrodomestico == "Frigorifero":
        if 100 <= potenza <= 300:
            return "Active"
        elif 1 <= potenza <= 10:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Climatizzatore":
        if 100 <= potenza <= 300:
            return "Active"
        elif 1 <= potenza <= 10:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Microonde":
        if 60 <= potenza <= 2500:
            return "Active"
        elif 1 <= potenza <= 10:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Forno":
        if 60 <= potenza <= 2500:
            return "Active"
        elif 1 <= potenza <= 10:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "TV":
        if 50 <= potenza <= 200:
            return "Active"
        elif 1 <= potenza <= 3:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Robot_Cucina":
        if 5 <= potenza <= 1200:
            return "Active"
        elif 1 <= potenza <= 4:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Rilevatore_allagamento":
        if 1 <= potenza <= 5:
            return "Active"
        else:
            return "Strange"
    elif elettrodomestico == "Rilevatore_gas":
        if 1 <= potenza <= 5:
            return "Active"
        else:
            return "Strange"
    elif elettrodomestico == "Lampade":
        if 1 <= potenza <= 60:
            return "Active"
        else:
            return "Strange"
    elif elettrodomestico == "Luci":
        if 1 <= potenza <= 100:
            return "Active"
        else:
            return "Strange"
    elif elettrodomestico == "Sistema_surround":
        if 6 <= potenza <= 800:
            return "Active"
        elif 1 <= potenza <= 5:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Computer":
        if 20 <= potenza <= 1000:
            return "Active"
        elif 1 <= potenza <= 20:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Monitor":
        if 5 <= potenza <= 500:
            return "Active"
        elif 1 <= potenza <= 5:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Asciugacapelli":
        if 1000 <= potenza <= 2000:
            return "Active"
        else:
            return "Strange"
    elif elettrodomestico == "Caldaia":
        if 10 <= potenza <= 91:
            return "Active"
        elif 1 <= potenza <= 7:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Lavatrice":
        if 500 <= potenza <= 2400:
            return "Active"
        elif 1 <= potenza <= 20:
            return "Idle"
        else:
            return "Strange"
    elif elettrodomestico == "Other":
        if potenza > 0:
            return "Active"
    return "Unknown"

data = []

for _ in range(3000):
    elettrodomestico = random.choice(list(elettrodomestici.keys()))
    while(True):
        potenza_istantanea = random.uniform(1, 3000)
        stato = determina_stato(elettrodomestico, potenza_istantanea)
        if stato != "Active":
            continue
        else:
            data.append((elettrodomestico, potenza_istantanea, stato))
            break
    print(stato)

for _ in range(3000):
    elettrodomestico = random.choice(list(elettrodomestici.keys()))
    while(elettrodomestico=="Other"):
        elettrodomestico = random.choice(list(elettrodomestici.keys()))
    while(True):
        potenza_istantanea = random.uniform(1, 3000) 
        stato = determina_stato(elettrodomestico, potenza_istantanea)
        if stato != "Strange":
            print(str(potenza_istantanea) + stato +" "+ elettrodomestico + "!!")
            continue
        else:
            data.append((elettrodomestico, potenza_istantanea, stato))
            break
    #print(stato)

for _ in range(3000):
    elettrodomestico = random.choice(list(elettrodomestici.keys()))
    while(elettrodomestico=="Other" or elettrodomestico=="Rilevatore_allagamento" or elettrodomestico=="Rilevatore_gas" or elettrodomestico=="Lampade" or elettrodomestico=="Luci" or elettrodomestico=="Asciugacapelli"):
        elettrodomestico = random.choice(list(elettrodomestici.keys()))
    while(True):
        potenza_istantanea = random.uniform(1, 20) 
        stato = determina_stato(elettrodomestico, potenza_istantanea)
            
        if stato != "Idle":
            print(str(potenza_istantanea) + stato +" "+ elettrodomestico + "!!")
            continue
        else:
            data.append((elettrodomestico, potenza_istantanea, stato))
            break
        

schema = ["elettrodomestico", "potenza_istantanea", "stato"]
df = spark.createDataFrame(data, schema=schema)

df.show()

df.write.csv("./dataset", header=True)