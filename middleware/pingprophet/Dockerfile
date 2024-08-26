# Use the official Python base image
FROM python:3.9

# Set the working directory in the container
WORKDIR /app
COPY . /app

ARG APP_PORT=8080

# Install the required dependencies
RUN apt-get update && apt-get install -y git gnupg2 wget vim curl lsb-release

RUN pip3 install --no-cache-dir -r requirements.txt

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "${APP_PORT}"]
