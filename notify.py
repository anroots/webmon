import requests
import os

def alert(message):
    url = 'https://fleep.io/hook/{0}'.format(os.environ('FLEEP_HOOK'))
    requests.post(url,json={message: message})
