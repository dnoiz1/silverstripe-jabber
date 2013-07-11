#!/usr/bin/env python

# External auth script for ejabberd to auth against
# Silverstripe 3.1 MySQL db, using blowfish
# Supports token auth for jappix web client

# Released under GNU GPLv3

# based on work by iltl. Contact: iltl@free.fr
# Inspired by Lukas Kolbe script.

# Silverstripe version by Tim Noise (tim@drkns.net)
# Version: 11-7-2013

# dont forget to move this file out of your www -_-

# requirements: py-bcrypt, python-MySQLdb

# %% configure your ejabberd auth as so
# {auth_method, external}.
# {extauth_program, "python /path/to/this/ejabberd-silverstripe-auth.py"}.

# silverstripe db details
db_name = ""
db_user = ""
db_pass = ""
db_host = ""

# this should be @ + JabberPage::Domain from your silverstripe config
domain_suffix = "@mydomain.com"

import sys, logging, struct, bcrypt, MySQLdb
from struct import *

sys.stderr = open('/var/log/ejabberd/extauth_err.log', 'a')
logging.basicConfig(level = logging.INFO,
            format   = '%(asctime)s %(levelname)s %(message)s',
            filename = '/var/log/ejabberd/extauth.log',
            filemode = 'a')

def blowfish(password, method_and_salt):
    try:
        return bcrypt.hashpw(password, method_and_salt)
    except:
        return ''

def encrypt_a(password, salt):
    method_and_salt = '$2a$' + salt
    encrypted_password = blowfish(password, method_and_salt)

    if encrypted_password.find('$2a$') == 0:
        return '$2y$' +  encrypted_password[4:]    
    return encrypted_password

def encryption_check(hash, password, salt):
#    if hash.find('$2y$') == 0:
#        return hash == encrypt_y(password, salt)
#    elif hash.find('$2a$') == 0:
    #logging.info(hash +' '+ password+ ' '+salt)    
    if hash.find('$2y$') == 0:
        return hash == encrypt_a(password, salt)
#    elif hash.find('$2x$') == 0:
#        return hash == encrypt_x(password, salt)
    return False

def db_connect():
    global db
    try:
        db = MySQLdb.connect(db_host, db_user, db_pass, db_name)
        db.autocommit(True)
        return db
    except:
        logging.debug("Unable to initialize database, check settings!")

db = db_connect()

def db_check():
    global db
    try:
        db.ping()
    except MySQLdb.OperationalError, message:
        db = None
        db_connect()

class EjabberdInputError(Exception):
    def __init__(self, value):
        self.value = value
    def __str__(self):
        return repr(self.value)

def ejabberd_in():
        logging.debug("trying to read 2 bytes from ejabberd:")
        try:
            input_length = sys.stdin.read(2)
        except IOError:
            logging.debug("ioerror")
        if len(input_length) is not 2:
            logging.debug("ejabberd sent us wrong things!")
            raise EjabberdInputError('Wrong input from ejabberd!')
        logging.debug('got 2 bytes via stdin: {0}'.format(input_length))
        (size,) = unpack('>h', input_length)
        logging.debug('size of data: {0}'.format(size))
        income=sys.stdin.read(size).split(':')
        logging.debug("incoming data: {0}".format(income))
        return income

def ejabberd_out(bool):
        logging.debug("Ejabberd gets: {0}".format(bool))
        token = genanswer(bool)
        logging.debug("sent bytes: %#x %#x %#x %#x" % (ord(token[0]), ord(token[1]), ord(token[2]), ord(token[3])))
        sys.stdout.write(token)
        sys.stdout.flush()

def genanswer(bool):
        answer = 0
        if bool:
            answer = 1
        token = pack('>hh', 2, answer)
        return token

def db_entry(in_user):
    db_check()

    # below query means the user must directly belong to a top
    # level or second level silverstripe group
    # TODO: sleep, rewrite this mess
    query = """
        SELECT
            Member.ID,
            Member.JabberUser,
            Member.Password,
            Member.salt,
            Member.JabberToken
        FROM `Group_Members` 
        LEFT JOIN `Group` ON Group_Members.GroupID = `Group`.ID
        LEFT JOIN Permission ON `Group`.ID = Permission.GroupID
        LEFT JOIN Permission as PPermission ON `Group`.ParentID = PPermission.GroupID
        LEFT JOIN Member ON Group_Members.MemberID = Member.ID
        WHERE (PPermission.Code = 'JABBER' OR Permission.Code = 'JABBER')
        AND Member.JabberUser = %s
        GROUP BY Member.ID LIMIT 1
    """

    sql = db.cursor()
    sql.execute(query, (in_user))

    return sql.fetchone()

def isuser(in_user, in_host):
    data = db_entry(in_user)
    out = False #defaut to O preventing mistake
    if data == None:
        out = False
        logging.debug("Wrong username: {0}".format(in_user))
    elif in_user + "@" + in_host == data[1] + domain_suffix:
        out = True
    return out

def auth(in_user, in_host, password):
    data = db_entry(in_user)
    out = False #defaut to O preventing mistake
    if data == None:
        out = False
        logging.debug("Wrong username: {0}".format(in_user))
    elif in_user + "@" +  in_host == data[1] + domain_suffix:
        # if hashlib.sha1(password + data[3]).hexdigest()==data[2]:
        #    hash passsword salt
        if encryption_check(data[2], password, data[3]):
            logging.debug("Successful Password Auth for user: {0}".format(in_user))
            out = True
        elif password == data[4]:
            logging.debug("Successful Token Auth for user: {0}".format(in_user))
            out = True
        else:
            logging.debug("Wrong password for user: {0}".format(in_user))
            out = False
    else:
        out = False
    return out

def log_result(op, in_user, bool):
    if bool:
        logging.info("{0} successful for {1}".format(op, in_user))
    else:
        logging.info("{0} unsuccessful for {1}".format(op, in_user))


if __name__ == '__main__':
    logging.info('extauth script started, waiting for ejabberd requests')
    while True:
        logging.debug("main loop starting")
        try: 
            ejab_request = ejabberd_in()
        except EjabberdInputError, inst:
            logging.info("Exception occured: {0}".format(inst))
            break
        logging.debug('operation: {0}'.format(ejab_request[0]))
        op_result = False
        if ejab_request[0] == "auth":
            op_result = auth(ejab_request[1], ejab_request[2], ejab_request[3])
            ejabberd_out(op_result)
            log_result(ejab_request[0], ejab_request[1], op_result)
        elif ejab_request[0] == "isuser":
            op_result = isuser(ejab_request[1], ejab_request[2])
            ejabberd_out(op_result)
            log_result(ejab_request[0], ejab_request[1], op_result)
        elif ejab_request[0] == "setpass":
            op_result=False
            ejabberd_out(op_result)
            log_result(ejab_request[0], ejab_request[1], op_result)
    logging.debug("ending main loop")
    logging.info('extauth script terminating')
    db.close()
