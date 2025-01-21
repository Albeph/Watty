FROM python:3.9.21-alpine3.21

# Install the dependencies
RUN pip install meross_iot && pip install pytz && pip install requests && apk add iputils

USER 1000

WORKDIR /app

# Copy the rest of the application code
COPY test.py .

# Copy the rest of the application code
COPY pass.csv .

# Command to run the Python script
CMD ["python", "test.py"]
