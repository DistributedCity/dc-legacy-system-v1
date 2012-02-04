#!/usr/bin/env python
#
# Batch GPG Key Generation of Queue in www.distributedcity.com/gpg/work/
#
# Using the following file format
#
# #/websites/www.distributedcity.com/gpg/keyrings/don_juan_distributedcity.com/.gnupg/
# Key-Type: DSA
# Key-Length: 1024
# Subkey-Type: ELG-E
# Subkey-Length: 2048
# Name-Real: Don_Juan
# Name-Email: don_juan@distributedcity.com
# Expire-Date: 0
# Passphrase: passphrase
# %commit
#

import os, sys, shutil, linecache, GnuPGInterface, time, commands, pg
from xmlrpclib import Server
from Conf import ConfReader


# defines
error = 0

# Set the configuration filename, complete with path
config_file_name = '../conf/site_config.ini' 

"""
  Load and parse the configuration file using this handy ConfReader class
  the format of the file is the standard: name=value #comment
  use like this example: print config.host_root
  this will print the config parameter host_root
"""
config = ConfReader(config_file_name)
config.parse()


"""
 The file that contains the message sent to the user after the key has been generated.
 The format is:
 first line is the message subject
 second line onward is the body 
"""
success_message_filename = config.app_base + '/templates/general/key_gen_complete.txt'
log_file = config.machine_tmp_dir + '/logs/' + config.site_instance_name + '.gpg_activity.log'



# See if this command is already running, if so skip this process  if not, continue
running_copy_count = commands.getoutput("ps -eo args --cols 10000 | grep -c 'python.*gpg_generate_key_queue.py$'")


if running_copy_count > '1':
    error = 1
    status = 'ERROR: Found another key generation processing operating. Nothing done.' 
    print status

else:

    status = 'OK: No other key generation processing operating. Continuing.' 
    print status

    # IF the log file does not exist, create it an set perms to 770
    if os.access(log_file, os.F_OK) == 0:
        # Now Create it and set perms to 770
        log = open(log_file, "a")
        log.write('OK: ' +config.site_instance_name + ' Batch GPG Key Generation Log. Created: ' + time.strftime("%Y-%m-%d %H:%M:%S",time.localtime(time.time()))+'\n----------------------------------------\n\n')
        log.close()
        os.chmod(log_file, 0770)

    log = open(log_file, "a")


    # This is the query used to find new keys in the queue
    query = "select * from dc_gpg_keygen_queue"
    for keypair in pg.DB(config.db_name, config.db_host, -1, None, None, config.db_user, config.db_pass).query(query).dictresult():

        # Reset keyring dir: If Keyring directory allready exists, Kill it and make a new one and set perms to 770
        if os.access(keypair['keyring_directory'], os.F_OK) == 1:
            shutil.rmtree(keypair['keyring_directory'], 1) # recursively wipes out the existing dir and anything underneath it

        # Now Create it and set perms to 770
        os.mkdir(keypair['keyring_directory'], 0770) # Create the home directory
        os.chmod(keypair['keyring_directory'], 0770) # Force permissions on the directory; Sometimes the perms do not take on mkdir for certain environments

        # Set-group-ID flag
        #command = 'chmod g+s %s' % (keypair['keyring_directory'])
        #os.system(command)
        #print '%s set group ID flag set: g+s' % (keypair['keyring_directory'])



        # Print status
        status = '---\nOK: Key Gen Start: ' + time.strftime("%Y-%m-%d %H:%M:%S",time.localtime(time.time())) + '\nOK: Username: ' + keypair['notification_recipient'] + '\nOK: Home: ' + keypair['keyring_directory'] + '\n'
        print status
        log.write(status)

        status = 'OK: Batch Data:'	
        print status
        log.write(status+'\n')

        # Log batch data to the log, remove passphrase from being logged
        batch_data_list = keypair['batch_data'].splitlines()
	for x in batch_data_list:
            if x.find('Passphrase: '):
                print x
                log.write(x + '\n')
               


        try:
            # Do the GPG Key Generation
            gnupg = GnuPGInterface.GnuPG()

            # Set GPG Options
            gnupg.options.meta_interactive = 0
            gnupg.options.homedir = keypair['keyring_directory']

            proc = gnupg.run(['--gen-key'], create_fhs=['stdin', 'logger'])

            proc.handles['stdin'].write(keypair['batch_data'])
            proc.handles['stdin'].close()

            logger = proc.handles['logger'].read()
            proc.handles['logger'].close()

            status = 'OK: GPG Pipe Logger Output:' 
            log.write(status + '\n' + logger)
            print status
            print logger

            proc.wait()


            # If all is OK force the perms on the new files in the users keyring directory to 770
            new_files = os.listdir(keypair['keyring_directory'])
            for x in new_files:

                os.chmod(keypair['keyring_directory'] + '/' + x, 0770)
                status = 'OK: CHMOD 770: ' + keypair['keyring_directory'] + '/' + x
                log.write(status + '\n')
                print status

                # Change group to apache on site files
                os.system('chgrp -R apache %s' % (keypair['keyring_directory'] + '/' + x))
                status =  'OK: CHGRP APACHE: %s' % (keypair['keyring_directory'] + '/' + x )
                log.write(status + '\n')
                print status

                #os.chgrp(keypair['keyring_directory'] + '/' + x, 'websites', 'apache')
                #status = 'OK: CHGRP APACHE: ' + keypair['keyring_directory'] + '/' + x
                



            status = "OK: GPG Finished Generating Keypair.\n"
            log.write(status)
            print status


        except IOError:
            error = 1 
            status = 'ERROR: GPG Pipe Logger Output:'
            log.write(status + '\n' + logger)
            print status
            print logger

            # Rollback Keypair Creation - delete directory and keyrings and data
            if os.access(keypair['keyring_directory'], os.F_OK) == 1:
                shutil.rmtree(keypair['keyring_directory'], 1) # recursively wipes out the keyring dir files inside it

            status = 'OK: Rolling Back Keypair Creation (Deleting the New Keyring directory since the process did not complete correctly.\nOK: Leaving the keypair generation request in the queue.'
            log.write(status)
            print status
            



        if(error == 0):
            status = 'OK: Attempting to send private message notification now.\n'
            log.write(status)
            print status


            # Send the user a message via the xmlrpc dc private messaging system
            # Get the recipient usernames from the incoming args
            #recipient_usernames = dc_username

            # set subject and body 
            message_file = open(success_message_filename)
            subject = message_file.readline()
            body = message_file.read()
            message_file.close()

            # Send the xmlrpc message

            try:
                # If user input from the config does not have a trailing slash, add one for them
                if(config.xmlrpc_host[-1] != '/'):
                    config.xmlrpc_host = config.xmlrpc_host + '/'

                dc = Server(config.xmlrpc_host + 'xmlrpc/')
                send_result = dc.send_message('Crypto_Engine', config.crypto_engine_password, keypair['notification_recipient'], subject, body)

                if send_result[0] == '0':
                    error = 1
                    status = "Hey dumbfuck, I couldn't send the message because CE user can't login. Make sure the password is set in the cong/site_config.ini file."
                    log.write(status + '\n')
                    print(status)

                else:
                    status = 'OK: Distributed City Private Message regarding Keygeneration Completion sent to user: ' + keypair['notification_recipient']
                    log.write(status + '\n')
                    print(status)
  
                    # Delete the entry from the queue
                    query = ("delete from dc_gpg_keygen_queue where user_id=%s" % keypair['user_id'])
                    result = pg.DB(config.db_name, config.db_host, -1, None, None, config.db_user, config.db_pass).query(query)
                
                    status = 'OK: Deleted Keypair entry from the Key Generation Queue'
                    log.write(status + '\n')
                    print(status)

            except:
                error = 1
                status = 'ERROR: XMLRPCLIB ProtocolError: communicating with XMLRPC host.\nPlease check that the XMLRPC host settings are correct\nand that the XMLRPC host is operating correctly\nCurrent Settings:\nxmlrpc_host: ' + config.xmlrpc_host + '\npath: '+ 'xmlrpc/\n\n'
                log.write(status)
                print status

    if(error == 1):
        status = 'ERROR: Critical Errors Encountered Above: Aborting\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n'
    else:
        status = 'OK: All operations appear to have completed successfully. Process Completed. Have a nice day!\n___\n\n'


    log.write(status)
    print status
    log.close()
    sys.exit()


