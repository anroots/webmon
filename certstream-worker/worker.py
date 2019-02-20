import sys
import argparse
import logging
import sys
import certstream
import tldextract
import mysql.connector
from mysql.connector import Error
from mysql.connector import pooling
import collections
from queue import Queue
from domainprocessor import DomainProcessor

logging.basicConfig(format='[%(levelname)s:%(name)s] %(asctime)s - %(message)s', level=logging.INFO)

# CLI args
parser = argparse.ArgumentParser(description='Monitor domains for interesting security states')

parser.add_argument('--tld', action='append', nargs='?', default=[],
                    help='Monitor this TLD (.com)')
parser.add_argument('--mysql-port', type=int, default=3306, help='MySQL port')
parser.add_argument('--mysql-host', default='app-db', help='MySQL hostname')
parser.add_argument('--mysql-username', default='webmon', help='MySQL username')
parser.add_argument('--mysql-password', default='', help='MySQL password')
parser.add_argument('--mysql-database', default='webmon', help='MySQL database')
parser.add_argument('-c', dest='concurrency_count', action='store', default=2, type=int, help="The number of concurrent threads to run at a time")
args = parser.parse_args()

# Data structures for threaded operations
buffer = collections.deque(maxlen=500)
domain_queue = Queue(maxsize=0)
worker_threads = []

# MySQL connection pool
try:
    mysql_pool = mysql.connector.pooling.MySQLConnectionPool(pool_reset_session=True,
                                                             pool_name='backend',
                                                             pool_size=args.concurrency_count,
                                                             host=args.mysql_host,
                                                             database=args.mysql_database,
                                                             user=args.mysql_username,
                                                             password=args.mysql_password,
                                                             port=args.mysql_port)
    logging.info('MySQL database connected')
except Error as e:
    logging.fatal('Unable to connect to MySQL - {}'.format(e))
    sys.exit(1)

# Create worker threads
for i in range(args.concurrency_count):
    thread = DomainProcessor(i, logging, mysql_pool.get_connection(), domain_queue)
    worker_threads.append(thread)
    thread.start()


def new_cert(message, context):
    if message['message_type'] != "certificate_update":
        return

    # Set removes duplicate domains
    all_domains = set(message['data']['leaf_cert']['all_domains'])

    for domain in all_domains:
        domain_parts = tldextract.extract(domain)

        if len(args.tld) and domain_parts.suffix not in args.tld:
            continue

        if domain_parts.subdomain == '*':
            domain = '{}.{}'.format(domain_parts.domain, domain_parts.suffix)

        # Do not insert duplicates
        if domain in buffer:
            continue
        domain_queue.put(domain)
        buffer.append(domain)


if __name__ == '__main__':
    logging.info('Starting certstream listener...')
    certstream.listen_for_events(new_cert, "wss://certstream.calidog.io")
