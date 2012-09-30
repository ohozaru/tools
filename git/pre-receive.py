#!/usr/bin/python
# Author Roman Nowicki (http://nowicki.cjb.net)
# A server-side git hook script for checking PHP syntax

import os
import sys
oldrev, newrev, ref = sys.stdin.read().strip().split(' ')
test_file = os.popen('mktemp').read().strip()

for line in os.popen('git diff --name-only %s %s' % (oldrev, newrev)).readlines():
    extension = line.split('.')[-1].strip()
    file_name = line.strip()

    if(extension == 'php'):
        cmd = "git ls-tree --full-name -r %s | grep \"%s$\" | awk '{ print $3 }'" % (newrev, file_name)
        blob = os.popen(cmd).read().strip()
        os.system("git cat-file blob %s > %s" % (blob, test_file))
        result = os.system('php -l ' + test_file + ' > /dev/null')
        if(result != 0): 
            print "PHP Syntax error in file %s" % (file_name)
            sys.exit(1)

        result = os.system("phpcs --warning-severity=0 --standard=/var/lib/gitosis/coding_standards.xml %s" % (test_file))
        if(result != 0): 
            print "Coding standards fail in file %s" % (file_name)
            sys.exit(2)

