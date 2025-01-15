from pyspark.sql import SparkSession
from pyspark.ml.feature import StringIndexer, VectorAssembler
from pyspark.ml.classification import RandomForestClassifier
from pyspark.ml.evaluation import MulticlassClassificationEvaluator
from pyspark.ml import Pipeline
import os

spark = SparkSession.builder.appName("ElettrodomesticiML").getOrCreate()

data = spark.read.csv("./dataset/*.csv", header=True, inferSchema=True)

indexer = StringIndexer(inputCol="elettrodomestico", outputCol="elettrodomesticoIndex", handleInvalid="keep")
assembler = VectorAssembler(inputCols=["elettrodomesticoIndex", "potenza_istantanea"], outputCol="features")
labelIndexer = StringIndexer(inputCol="stato", outputCol="label")

labelIndexerModel = labelIndexer.fit(data)
labels = labelIndexerModel.labels

rf = RandomForestClassifier(featuresCol="features", labelCol="label", numTrees=10)

pipeline = Pipeline(stages=[indexer, assembler, labelIndexer, rf])

model = pipeline.fit(data)

predictions = model.transform(data)
evaluator = MulticlassClassificationEvaluator(labelCol="label", predictionCol="prediction", metricName="accuracy")
accuracy = evaluator.evaluate(predictions)
print(f"Accuracy: {accuracy}")
print("Labels: ", labels)

model.save("./out/model")

os.system("chmod 777 -R ./out")