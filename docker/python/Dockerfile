FROM ubuntu:latest
RUN ln -snf /usr/share/zoneinfo/$CONTAINER_TIMEZONE /etc/localtime && echo $CONTAINER_TIMEZONE > /etc/timezone
RUN apt-get update -qq && apt-get install ffmpeg -y
# RUN apt install software-properties-common -y
RUN apt install python3 -y
RUN apt install python3-pip -y
RUN apt install python3-tk -y
RUN pip3 install pathlib mysql-connector-python python-dotenv pytube PySimpleGUI
RUN pip install fastapi
RUN pip install uvicorn

# RUN cd /scripts && uvicorn apiTest:app --host 0.0.0.0 --port 353