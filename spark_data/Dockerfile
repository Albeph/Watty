FROM apache/spark

USER 0
RUN pip install numpy

RUN  mkdir -p /opt/tap/log-files && chmod -R 777 /opt/tap/log-files 

USER spark