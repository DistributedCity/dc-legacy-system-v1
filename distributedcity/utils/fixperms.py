#!/usr/bin/python

# Fix perms etc

import os
from Conf import ConfReader

# Get the configuration from the conf dir relative to here:  ../conf/site_config.ini

# change these if need be.
user = 'websites'
group = 'apache'

# Set the configuration filename, complete with path
config_file_name = '../conf/site_config.ini' 

# Load and parse the configuration file using this handy ConfReader class
# the format of the file is the standard: name=value #comment
# use like this example: print config.host_root
# this will print the config parameter host_root
config = ConfReader(config_file_name)
config.parse()

# Do these dirs exist? If not, create them with proper perms, if yes,
# then force 0770 perms

paths = [config.host_root + 'sessions',
         config.machine_tmp_dir,
	 config.machine_tmp_dir + 'cache',
         config.machine_tmp_dir + 'cache/' + config.site_instance_name,
	 config.machine_tmp_dir + 'logs',
	 config.machine_data_dir,
         config.machine_data_dir + 'gpg_keyrings',
         config.machine_data_dir + 'gpg_keyrings/' + config.site_instance_name]

for path_name in paths:

    if os.access(path_name, 1) == 0:

        # NO, lets create it with correct perms
        print '%s does not exist, creating with perms 0770...' % (path_name)
        os.mkdir(path_name, 0770)
    
    # Force perms to: 0770
    os.chmod(path_name, 0770)
    print '%s perms forced to chmod 0770' % (path_name)
    os.system('chown -R ' + user + ' ' + path_name)
    os.system('chgrp -R ' + group + ' ' + path_name)



# Force user to: websites
os.system('chown -R ' + user + ' ' + config.host_root + 'tmp')
os.system('chown -R ' + user + ' ' + config.host_root + 'sessions')
os.system('chown -R ' + user + ' ' + config.host_root + 'data')

# Force group to: apache
os.system('chgrp -R ' + group + ' ' + config.host_root + 'tmp')
os.system('chgrp -R ' + group + ' ' + config.host_root + 'sessions')
os.system('chgrp -R ' + group + ' ' + config.host_root + 'data')





# Change perms to 777 on site files
os.system('chmod -R 770 ' + config.app_base)
print  '%s perms recursively forced to chmod 0770' % (config.app_base)

# Change owner to websites on site files
os.system('chown -R ' + user + ' ' + config.app_base)
print  '%s chown recursively forced to %s' % (config.app_base, user)

# Change group to apache on site files
os.system('chgrp -R ' + group + ' ' + config.app_base)
print  '%s chgrp recursively forced to %s' % (config.app_base, group)



