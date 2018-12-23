import logging
import requests
from notify import alert
from  requests.exceptions import RequestException
logging.basicConfig(format='[%(levelname)s:%(name)s] %(asctime)s - %(message)s', level=logging.INFO)


def check_git(domain):
    head_url = 'http://{}/.git/HEAD'.format(domain)
    dir_url = 'http://{}/.git/'.format(domain)
    logging.info('Scanning {} for .git/HEAD'.format(domain))

    try:
        head_response = requests.get(head_url, timeout=7, headers={'User-Agent':'Webmon Research Agent (https://github.com/anroots/webmon)'})
        dir_response = requests.get(dir_url, timeout=7, headers={'User-Agent':'Webmon Research Agent (https://github.com/anroots/webmon)'})
    except RequestException:
        return

    if head_response.status_code == 200 and 'refs' in head_response.text and dir_response == 200 and 'config' in dir_response.text:
        message = 'Found Git folder at {0}'.format(domain)
        logging.info(message)
        alert(message)


def observe(domain):
    check_git(domain)
