#CALCOLO WH per minuto

from pyspark.sql import SparkSession
from pyspark.sql.functions import from_json, col, window, avg, count
from pyspark.sql.types import StructType, StructField, StringType, TimestampType, DoubleType

spark = SparkSession.builder \
    .appName("Energy_Consumption_imin") \
    .config("spark.driver.host", "localhost")\
    .getOrCreate()

spark.sparkContext.setLogLevel("ERROR")

kafkaServer = "broker:9999"
topic = "energy-monitor"


# Lettura file CSV per mappatura zone-elettrodomestici

#elettrodomestici
temp_mapping_df = spark.read.csv("/opt/tap/mapping_elettr_conn.csv", header=True)

mapping_df = temp_mapping_df.select("zona","prodotto_id","elettrodomestico","role")

#zone
zone_room_df = spark.read.csv("/opt/tap/zone_room.csv", header=True)


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

# Rinominiamo il campo 'timestamp' in 'timestamp', questo perchè usando @ da conflitto usando groupby 'errore di pyspark.sql'
#decoded_df = decoded_df.withColumnRenamed("timestamp", "timestamp")

# Convertiamo il campo 'value' in Double per rappresentare il consumo
decoded_df = decoded_df.withColumn("power", col("value").cast(DoubleType()))

filt_df = decoded_df.filter(col("power") >= 0)

# Calcoliamo il consumo del minuto appena passato per ogni elettrodomestico differenziando zona per zona
avg_consumo_df = filt_df \
    .withWatermark("timestamp", "1 minute") \
    .groupBy(
        window(col("timestamp"), "1 minute", "1 minute"),
        col("zona"),
        col("prodotto_id")
    ) \
    .agg(
        avg("power").alias("avg_consumo"),
        count("power").alias("num_values")
    )\
    .withColumn("f_timestamp", col("window.end"))   # Qui andiamo a creare una colonna che indica il timestamp di di chiusura della finestra temporale richiesta (1 min)



# Moltiplichiamo la media del consumo per 0,0167 per ottenere il consumo in Wh in un minuto
avg_consumo_df = avg_consumo_df.withColumn("consumo_Wm", col("avg_consumo") * 0.0167)

# Filtriamo i risultati per scartare quelli con avg_consumo minore di 5, utile se si vuole una misurazione più precisa, ma richiede alta affidabilità
#filtered_df = avg_consumo_df.filter(col("num_values") >= 5)


# Uniamo il DataFrame decodificato con i DataFrame utili per il mapping
joined_df = avg_consumo_df.join(mapping_df, on=["zona", "prodotto_id"], how="left")

final_df = joined_df.join(zone_room_df, on="zona", how="left")



# Selezioniamo solo le colonne utili
final = final_df.select("f_timestamp", "nome_zona", "elettrodomestico", "consumo_Wm")



def write_and_show(batch_df, batch_id):
    #batch_df.show()    # Utile per il debug

    # Scriviamo i dati in Elasticsearch
    batch_df.write \
        .format("org.elasticsearch.spark.sql") \
        .option("es.nodes", "elasticsearch") \
        .option("es.port", "9200") \
        .option("es.resource", "energy-consumption") \
        .mode("append") \
        .save()


query = final.writeStream \
  .foreachBatch(write_and_show) \
  .start()

query.awaitTermination()