import logging
import requests
from notify import alert
from  requests.exceptions import RequestException

logging.basicConfig(format='[%(levelname)s:%(name)s] %(asctime)s - %(message)s', level=logging.INFO)

headers = {
    'User-Agent': 'Webmon Research Agent (https://github.com/anroots/webmon)'
}


def _has_git_dir(domain):
    head_url = 'http://{}/.git/HEAD'.format(domain)

    try:
        head_response = requests.get(head_url, timeout=7, headers=headers)
        return head_response.status_code == 200 and 'refs' in head_response.text
    except RequestException:
        return False


def _has_dir_index(domain):
    dir_url = 'http://{}/.git/'.format(domain)

    try:
        dir_response = requests.get(dir_url, timeout=7, headers=headers)
        return dir_response.status_code == 200 and 'config' in dir_response.text

    except RequestException:
        return False


def check_git(domain):
    logging.info('Scanning {} for .git/HEAD'.format(domain))

    if _has_dir_index(domain) and _has_git_dir(domain):
        message = 'Found Git folder at {0}'.format(domain)
        logging.info(message)
        alert(message)


def observe(domain):
    check_git(domain)
