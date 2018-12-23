import requests
import os

def alert(message):
    url = 'https://fleep.io/hook/{0}'.format(os.environ.get('FLEEP_HOOK'))
    requests.post(url,json={message: message})
