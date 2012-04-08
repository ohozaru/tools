#!/usr/bin/env python
# daemon part taken from http://www.jejik.com/articles/2007/02/a_simple_unix_linux_daemon_in_python/
import sys, os, time, atexit, signal, subprocess, hashlib, datetime, shlex
import mpr

DEBUG = False

pidfile = 'croni.pid'
tmpdir = '/tmp'
homedir = '/home'
procdir = '/proc'
crontable_file = "croni.conf"
taskpoll = {}

class Task:
    def __init__(self, params):
        self.id = hashlib.md5(str(params)).hexdigest()
        self.cmd = params[5]
        self.months = params[4]  
        self.days = params[3]    
        self.hours = params[2]   
        self.minutes = params[1] 
        self.seconds = params[0] 
        self.execution_time = 0
        self.calculate_next_execution_time()
        self.process = None

    def calculate_next_execution_time(self):
        #todo
        self.next_execution_time = time.time()

    def is_running(self):
        if self.process is None:
            return False

        if self.process.returncode is None:
            return True
        self.process = None
        return False

    def is_expired(self):
        if time.time() > self.next_execution_time:
            return True
        return False

    def run(self):
        self.execution_time = time.time()
        self.next_execution_time = self.execution_time + 5
        args = shlex.split(self.cmd)
        self.process = subprocess.Popen(args, shell=False, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)

def daemonize():
    """Deamonize class. UNIX double fork mechanism."""
    try: 
        pid = os.fork() 
        if pid > 0:
            # exit first parent
            sys.exit(0) 
    except OSError as err: 
        sys.stderr.write('fork #1 failed: {0}\n'.format(err))
        sys.exit(1)

    # decouple from parent environment
    os.chdir('/') 
    os.setsid() 
    os.umask(0) 

    # do second fork
    # http://stackoverflow.com/questions/881388/what-is-the-reason-for-performing-a-double-fork-when-creating-a-daemon
    # Fork a second child and exit immediately to prevent zombies.  This
    # causes the second child process to be orphaned, making the init
    # process responsible for its cleanup.  And, since the first child is
    # a session leader without a controlling terminal, it's possible for
    # it to acquire one by opening a terminal in the future (System V-
    # based systems).  This second fork guarantees that the child is no
    # longer a session leader, preventing the daemon from ever acquiring
    # a controlling terminal.
    try: 
        pid = os.fork() 
        if pid > 0:

            # exit from second parent
            sys.exit(0) 
    except OSError as err: 
        sys.stderr.write('fork #2 failed: {0}\n'.format(err))
        sys.exit(1) 

    # redirect standard file descriptors
    sys.stdout.flush()
    sys.stderr.flush()
    si = open(os.devnull, 'r')
    so = open(os.devnull, 'a+')
    se = open(os.devnull, 'a+')

    os.dup2(si.fileno(), sys.stdin.fileno())
    os.dup2(so.fileno(), sys.stdout.fileno())
    os.dup2(se.fileno(), sys.stderr.fileno())

    # write pidfile
    atexit.register(delpid)

    pid = str(os.getpid())
    with open(lockfilename(),'w+') as f:
        f.write(pid + '\n')
	
def delpid():
    os.remove(lockfilename())

def lockfilename():
    return tmpdir + '/' + pidfile

def parse_crontable():
    try:
        config = []
        for line in open(crontable_file):
            """ommit commented empty lines"""
            if line[0] == "#" or line[0].strip() == "":
                continue
            else:
                buf = ""
                whitespace = 0
                task = []
                for char in line:
                    if char == "\n":
                        task.append(buf.strip())
                        config.append(task)
                    elif whitespace > 4:
                        buf += char 
                    elif char in ["\t", " "]:
                        if buf:
                            whitespace += 1
                            task.append(buf)
                        buf = ""
                    else:
                        buf += char

        return config
    except IOError as (errno, strerror):
        print "I/O error({0}): {1} {2}".format(errno, strerror, config)

def debug(taskpool):
    print 'pid\tlast_time\t\tnext_time\t\tprocess'
    for task in taskpoll.itervalues():
        if task.is_running():
            print "{0}\t{2}\t{3}\t{1}".format(
                task.process.pid,task.cmd, 
                datetime.datetime.fromtimestamp(task.execution_time).strftime('%Y-%m-%d %H:%M:%S'),
                datetime.datetime.fromtimestamp(task.next_execution_time).strftime('%Y-%m-%d %H:%M:%S')
            )
        print '-'


def restart():
    stop()
    start()

def stop():
    # Get the pid from the pidfile
    try:
        with open(lockfilename(),'r') as pf:
            pid = int(pf.read().strip())
    except IOError:
        pid = None

    if not pid:
        sys.stderr.write("pidfile {0} does not exist. Daemon not running?\n".format(lockfilename()))
        return # not an error in a restart

    # Try killing the daemon process	
    try:
        while 1:
            os.kill(pid, signal.SIGTERM)
            time.sleep(0.1)
    except OSError as err:
        e = str(err.args)
        if e.find("No such process") > 0:
            if os.path.exists(lockfilename()):
                os.remove(lockfilename())
        else:
            print (str(err.args))
            sys.exit(1)

def start():
    # Check for a pidfile to see if already runs
    try:
        with open(lockfilename(),'r') as pf:
            pid = int(pf.read().strip())
    except IOError:
        pid = None

    if pid:
        sys.stderr.write("pidfile {0} already exist. Daemon already running PID:{1}\n".format(lockfilename(), pid))
        sys.exit(1)

    crontable = parse_crontable()
    
    # Start the daemon
    if not DEBUG:
        daemonize()

    try:
        while True:
            for config in crontable:
                task = Task(config)
                if task.id not in taskpoll:
                    taskpoll[task.id] = task

            for task in taskpoll.itervalues():
                if task.is_running():
                    continue
                elif task.is_expired():
                    task.run()

            if DEBUG:
                debug(taskpoll)

            time.sleep(3)
    except KeyboardInterrupt:
        exit(1)
    except Exception, e:
        log()

def log():
    import traceback
    pf = open('/tmp/croni.error.log', 'a+')
    pf.write('\n' + traceback.format_exc())


if __name__ == "__main__":
    if len(sys.argv) >= 2:
        if 'start' == sys.argv[1]:
            if '--debug' in sys.argv:
                DEBUG = True
            start()
        elif 'stop' == sys.argv[1]:
            stop()
        elif 'restart' == sys.argv[1]:
            restart()
        else:
            print "Unknown command"
            sys.exit(2)

        sys.exit(0)
    else:
        print "usage: %s start [--debug|--daemon]|stop|restart" % sys.argv[0]
        sys.exit(2)
