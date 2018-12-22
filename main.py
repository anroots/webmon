import argparse
import logging
import sys

import certstream
import tldextract
from redis import Redis
from redis.exceptions import ConnectionError
from rq import Queue

from monitor import observe

logging.basicConfig(format='[%(levelname)s:%(name)s] %(asctime)s - %(message)s', level=logging.INFO)


parser = argparse.ArgumentParser(description='Monitor domains for interesting security states')

parser.add_argument('--tld', action='append', nargs='?', default=[],
                    help='Monitor this TLD (.com)')
parser.add_argument('--rport',type=int,default=6379,help='Redis port')
parser.add_argument('--rhost',default='localhost',help='Redis hostname')
args = parser.parse_args()

redis = Redis(host=args.rhost, port=args.rport)

try:
    redis.ping()
except ConnectionError as e:
    logging.fatal('Unable to connect to Redis - {}'.format(e))
    sys.exit(1)

q = Queue(connection=redis)

def new_cert(message, context):

    if message['message_type'] != "certificate_update":
        return

    all_domains = message['data']['leaf_cert']['all_domains']

    for domain in all_domains:
        domain_parts = tldextract.extract(domain)

        if len(args.tld) and domain_parts.suffix not in args.tld:
            continue

        if domain_parts.subdomain == '*':
            domain = '{}.{}'.format(domain_parts.domain,domain_parts.suffix)

        logging.info('New domain to check - {}'.format(domain))
        q.enqueue(observe, domain)





if __name__ == '__main__':
    certstream.listen_for_events(new_cert, "wss://certstream.calidog.io")
