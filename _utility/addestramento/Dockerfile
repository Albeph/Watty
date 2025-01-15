FROM spark:python3

USER 0

RUN pip install pyspark && pip install numpy

#USER spark

WORKDIR /app

COPY ./code/create_d.py .
COPY ./code/training_d.py .


CMD ["sh", "-c", "python3 create_d.py && python3 training_d.py"]