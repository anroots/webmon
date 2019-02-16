FROM python:3.6-slim


WORKDIR /usr/src/app

COPY requirements.txt ./

RUN apt-get update &&\
    DEBIAN_FRONTEND=noninteractive apt-get install -y gcc default-libmysqlclient-dev && \
    pip install --no-cache-dir -r requirements.txt && \
    apt-get purge -y gcc && \
    rm -rf /var/lib/apt/lists/*

COPY . .

CMD [ "python", "worker.py" ]
