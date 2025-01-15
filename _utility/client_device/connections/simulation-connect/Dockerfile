FROM python:3.9.21-alpine3.21

# Install the dependencies
RUN pip install pytz && pip install requests

USER 1000

WORKDIR /app

# Copy the rest of the application code
COPY test.py .


# Command to run the Python script
CMD ["python", "test.py"]
