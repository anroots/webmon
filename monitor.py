import logging
import requests
from notify import alert

logging.basicConfig(format='[%(levelname)s:%(name)s] %(asctime)s - %(message)s', level=logging.INFO)


def check_git(domain):
    url = 'http://{}/.git/HEAD'.format(domain)
    response = requests.head(url, timeout=7, headers={'User-Agent':'Webmon Research Agent (https://github.com/anroots/webmon)'})
    logging.info('Scanning {} for .git/HEAD'.format(domain))

    if response.status_code == 200:
        message = 'Found Git folder at {0}'.format(domain)
        logging.info(message)
        alert(message)


def observe(domain):
    check_git(domain)
