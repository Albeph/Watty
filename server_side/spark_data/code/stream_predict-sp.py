from __future__ import print_function

from datetime import datetime
import os

from pyspark.sql import SparkSession

from pyspark.ml import PipelineModel

from pyspark.sql.functions import from_json, col, when, udf
from pyspark.sql.types import StringType, DoubleType, StructField, StructType, TimestampType

def probability_to_percentage(probability):
    max_probability = max(probability)
    return f"{max_probability * 100:.2f}"

def determine_state(prediction, potenza_istantanea, elettrodomestico):
    if potenza_istantanea < 1 and potenza_istantanea > -1:
        return "OFF"
    elif potenza_istantanea == -1:
        return "DISCONNECTED"
    elif elettrodomestico in ["Other","Lampade", "Luci"]:
        return "Active"
    elif prediction == 0.0:
        return "Active"
    elif prediction == 1.0:
        return "Idle"
    elif prediction == 2.0:
        return "Strange"
    else:
        return "emh"


# Registriamo le funzioni UDF, sono delle funzioni (definite dall'utente) che si applicano alle colonne del dataframe
determine_state_udf = udf(determine_state, StringType())

probability_to_percentage_udf = udf(probability_to_percentage, StringType())


spark = SparkSession.builder \
    .appName("energy_stream_predict") \
    .config("spark.driver.host", "localhost")\
    .getOrCreate()

spark.sparkContext.setLogLevel("ERROR")

kafkaServer="broker:9999"
topic = "energy-monitor"

# Calcoliamo il timestamp all'inizio dello script e creiamo la cartella che conterrà i log
start_timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
log_directory = f"/opt/tap/log-files/{start_timestamp}"
os.makedirs(log_directory, exist_ok=True)


# Lettura file CSV per mappatura zone-elettrodomestici

#elettrodomestici
temp_mapping_df = spark.read.csv("/opt/tap/mapping/mapping_elettr_conn.csv", header=True)

mapping_df = temp_mapping_df.select("zona","prodotto_id","elettrodomestico","role")

#zone
zone_room_df = spark.read.csv("/opt/tap/mapping/zone_room.csv", header=True)


# Caricamento del modello addestrato
model = PipelineModel.load("/opt/tap/predict/model")


# Leggiamo il flusso di dati da Kafka
df = spark \
    .readStream \
    .format("kafka") \
    .option("kafka.bootstrap.servers", kafkaServer) \
    .option("subscribe", topic) \
    .load()

# Definiamo lo schema del messaggio pescato da Kafka
schema = StructType([
    StructField(name='timestamp', dataType=TimestampType(), nullable=True),
    StructField(name='zona', dataType=StringType(), nullable=True),
    StructField(name='prodotto_id', dataType=StringType(), nullable=True),
    StructField(name='value', dataType=StringType(), nullable=True),
])

# Decodificamo il messaggio JSON usando lo schema precedentemente definito
decoded_df = df.selectExpr("CAST(value AS STRING) as json") \
    .select(from_json(col("json"), schema).alias("data")) \
    .select("data.*")

# Convertiamo il campo 'value' in Double per rappresentare il consumo
decoded_df = decoded_df.withColumn("potenza_istantanea", col("value").cast(DoubleType()))

# Uniamo il DataFrame decodificato con i DataFrame utili per il mapping
joined_df = decoded_df.join(mapping_df, on=["zona", "prodotto_id"], how="left")

final_df = joined_df.join(zone_room_df, on="zona", how="left")

# Sostituiamo i valori nulli di 'elettrodomestico' con 'Other', solitamente sono le prese o altri elettrodomestici non segnati
final_df = final_df.withColumn("elettrodomestico", when(col("elettrodomestico").isNull(), "Other").otherwise(col("elettrodomestico")))

# Applichiamo il modello ai dati di streaming per fare previsioni sullo stato dell'elettrodomestico
predictions = model.transform(final_df)

# Applichiamo la funzione UDF cha dato il valore in output dalla predizione da in output la stringa con lo stato supposto
predictions_with_state = predictions.withColumn("state", determine_state_udf(predictions["prediction"], predictions["potenza_istantanea"], predictions["elettrodomestico"]))

# Applichiamo la funzione UDF che prende la percentuale più alta delle 3 probabilità
predictions_with_percentages = predictions_with_state.withColumn("probability_percentages", probability_to_percentage_udf(predictions["probability"]).cast(DoubleType()))

# Sostituiamo i valori di 'potenza_istantanea' uguali a -1 con 0
fix_consumo = predictions_with_percentages.withColumn("potenza_istantanea", when(col("potenza_istantanea") == -1, 0).otherwise(col("potenza_istantanea")))

# Selezioniamo le colonne di interesse
final = fix_consumo.select("timestamp", "nome_zona", "elettrodomestico", "potenza_istantanea", "probability_percentages", "state", "role")

def write_and_show(batch_df, batch_id):
    # Scriviamo i dati in formato json
    batch_df.write.mode("append").json(log_directory)


    # DEBUG x leggere tutti i dati salvati e mostrali nella console
    #all_data_df = spark.read.json(log_directory)
    
    #if all_data_df.rdd.isEmpty():
    #    print("No data available in the JSON files.")
    #else:
    #    all_data_df.show()

    #batch_df.show()


    # Scriviamo i dati in Elasticsearch
    batch_df.write \
        .format("org.elasticsearch.spark.sql") \
        .option("es.nodes", "elasticsearch") \
        .option("es.port", "9200") \
        .option("es.resource", "energy-stream") \
        .mode("append") \
        .save()

query = final.writeStream \
  .foreachBatch(write_and_show) \
  .start()

query.awaitTermination()