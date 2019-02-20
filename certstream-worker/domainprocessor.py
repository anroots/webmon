import threading
import time
import datetime


class DomainProcessor(threading.Thread):
    def __init__(self, thread_id, logger, db, queue):
        super(DomainProcessor, self).__init__()
        self.thread_id = thread_id
        self.db = db
        self.queue = queue
        self.logger = logger

    def insert_domain(self, domain):
        sql_insert_query = """ INSERT INTO `domains`
                                 (`domain`, `created_at`, `updated_at`) VALUES (%s,%s,%s)"""

        ts = time.time()
        now = datetime.datetime.fromtimestamp(ts).strftime('%Y-%m-%d %H:%M:%S')
        insert_tuple = (domain, now, '2019-01-01 00:00:00')

        cursor = self.db.cursor()
        cursor.execute(sql_insert_query, insert_tuple)
        self.db.commit()
        cursor.close()

        self.logger.info('#{} Insert {}'.format(self.thread_id, domain))

    def process_domain(self,domain):
        try:
            if self.already_exists(domain):
                return

            self.insert_domain(domain)
        except Exception as e:
            self.logger.error('#{} Unable to insert domain {}, MySQl error - {}'.format(self.thread_id, domain, e))

    def run(self):

        while True:
            while self.queue.empty():
                time.sleep(0.2)
            domain = self.queue.get()
            self.process_domain(domain)
            self.queue.task_done()

    def already_exists(self,domain):
        cursor = self.db.cursor()
        cursor.execute("SELECT id FROM domains WHERE domain = %(domain)s", {'domain': domain})
        rowcount = cursor.fetchall()
        cursor.close()
        return len(rowcount)
