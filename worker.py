import argparse
from redis import Redis
from rq import Worker, Queue, Connection

listen = ['default']

parser = argparse.ArgumentParser(description='Monitor domains for interesting security states')

parser.add_argument('--tld', action='append', nargs='?', default=[],
                    help='Monitor this TLD (.com)')
parser.add_argument('--rport',type=int,default=6379,help='Redis port')
parser.add_argument('--rhost',default='localhost',help='Redis hostname')
args = parser.parse_args()

redis = Redis(host=args.rhost, port=args.rport)


if __name__ == '__main__':
    with Connection(redis):
        worker = Worker(list(map(Queue, listen)))
        worker.work()