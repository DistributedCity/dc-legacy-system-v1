#!/usr/bin/python

import os, re, sys


class GnuPGChangePassphrase:
    
    def __init__(self, key_id, user_homedir):
        self.key_id = key_id
        self.user_homedir = user_homedir

    def gpg_change_passphrase(self, old_passphrase, new_passphrase):

        "Change the passphrase for a users key"
        pipein, pipeout, pipeerr = self.get_new_gpg_pipe()
    
        pipeerr.close()
    
        pipein.write("passwd\n")
        pipein.write(old_passphrase + "\n")
        pipein.close()
        result = pipeout.read()
        pipeout.close()

        if re.compile("GOOD_PASSPHRASE").search(result, 1):
    
            print "OK: GOOD_PASSHRASE - PROCEEDING TO CHANGE TO NEW PASSPHRASE"
    
            # Finish off passphrase change
            pipein, pipeout, pipeerr = self.get_new_gpg_pipe()
            pipeerr.close()
    
            pipein.write("passwd\n")
            pipein.write(old_passphrase + "\n")
            pipein.write(new_passphrase + "\n")
            pipein.write("save\n")
            pipein.close()
        
            result = pipeout.read()
            pipeout.close()
        
            if re.compile("BAD_PASSPHRASE").search(result, 1):
                print 'ERROR: BAD_PASSPHRASE - NO CHANGES DONE, ABORTING'

        print result


    def get_new_gpg_pipe(self):
        " Return a pipe and handles for comms "
        return os.popen3('gpg --no-tty --command-fd=0 --status-fd=1 --verbose --homedir ' + self.user_homedir +' --edit-key '+ self.key_id )




old_passphrase = sys.argv[1];
new_passphrase = sys.argv[2];
key_id = 'estaban_hill'
user_homedir = '/websites/dc/gpg/keyrings/estaban_hill/'

gpg = GnuPGChangePassphrase(key_id, user_homedir)
gpg.gpg_change_passphrase(old_passphrase, new_passphrase)
