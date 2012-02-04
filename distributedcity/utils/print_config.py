#!/usr/bin/env python

# Parse and Print the configuration information seen for this site

import os, linecache, GnuPGInterface, time, commands, string
from Conf import ConfReader


config_dir       = '/home/websites/www.distributedcity.com/conf/'

# Get the simple hostname, so we can use it to load the proper config file of this host
fd = os.popen('hostname -s')
data = fd.readline()
hostname = data[:-1]
fd.close()


# Set the configuration filename, complete with path
config_file_name = config_dir + 'site_config.%s' % (hostname) 

# Load and parse the configuration file using this handy ConfReader class
# the format of the file is the standard: name=value #comment
config = ConfReader(config_file_name)
config.parse()
# example: print config.host_root
print config.xmlrpc_host