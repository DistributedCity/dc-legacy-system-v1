import sys, os
import string, sys

project_name = 'www.distributedcity.com'
path = string.split( os.getcwd(), '/')
if( path[len(path)-1] != 'utils' or path[len(path)-2] != project_name):
	raise 'Sorry, this script must be run from %s/utils' % (project_name)
else:
	sys.path.append ("../../lp_common/python/utils")
	import GenericVersioning

class ManageDCRelease:
	def __init__(self):
		self.conf_file = 'VERSION'

		os.chdir( os.getcwd() + '/../../' )
		self.root_dir = os.getcwd()

		self.dc_dir = self.root_dir + "/www.distributedcity.com"
		self.lp_dir = self.root_dir + "/lp_common"
		self.base_dir = self.dc_dir + '/utils'
		self.tar_dest = self.dc_dir + '/htdocs/downloads'
		self.cvsroot = ":ext:gente_libre@cvs.distributedcity.sourceforge.net:/cvsroot/distributedcity"
		
		self.versioning = GenericVersioning.GenericVersioning(self.dc_dir + '/' + self.conf_file)

	def siteRelease(self):
		tag_prefix = 'release'
		
		self.versioning.process()
		self.commitVersion(tag_prefix)
		self.packageBuild(tag_prefix)

	def nightlyBuild(self):
		tag_prefix = 'nightly'
		
		version = self.versioning.readVersion()
		self.versioning.updateTimeStamp(version)
		self.versioning.writeVersion(version)
		self.commitVersion(tag_prefix)
		self.packageBuild(tag_prefix)

	def siteAll(self):
		self.cvsUpdate()
		self.siteRelease()
		self.sfUpRelease()

	def nightlyAll(self):
		self.cvsUpdate()
		self.nightlyBuild()
		self.sfUpNightly()

	def getCurrentVerString(self):		
		#return "distributedcity-0.3.1024418509"
		return str(self.versioning.readVersion())

	def currentVer(self):
		print self.getCurrentVerString()

	def getTag(self, prefix):
		trans = string.maketrans("$,.:;@", "______")
		return string.translate( prefix + '_' + self.getCurrentVerString(), trans)
		
	def tagBuild(self, prefix):
		# return
		tag = self.getTag(prefix)
		print "tagging cvs repository with", tag
		os.chdir(self.dc_dir)
		self.syscmd('cvs -d %s tag %s' % (self.cvsroot, tag) )
		os.chdir(self.lp_dir)
		self.syscmd('cvs -d %s tag %s' % (self.cvsroot, tag) )
		
	def syscmd(self, command):
		print "executing: " + command
		return os.system(command)
			
	def getTarFileName(self):
		return self.getCurrentVerString() + '.tar.gz'

	def packageBuild(self, tag_prefix):
		BASE_DIR = '/tmp'
		PACKAGE_NAME = self.getCurrentVerString()
		PACKAGE_DIR = BASE_DIR + '/' + PACKAGE_NAME
		
		DC_CODE_TAG = self.getTag(tag_prefix)
		DC_DIR = 'distributedcity'
		LP_DIR = 'lp_common'
		DC_CODE_DIR= PACKAGE_DIR + '/' + DC_DIR
		LP_CODE_DIR= PACKAGE_DIR + '/' + LP_DIR
		
		DC_TAR_FILENAME= self.getTarFileName()
		
		TAR_DEST_DIR= self.tar_dest + '/' + tag_prefix
		
		self.syscmd('mkdir ' + PACKAGE_DIR)
		
		# Create the tarball directory
		self.syscmd('mkdir ' + self.tar_dest)
		self.syscmd('mkdir ' + TAR_DEST_DIR)
		
		os.chdir(PACKAGE_DIR)
		self.syscmd('cvs -d ' + self.cvsroot + ' export -d ' + DC_DIR + ' -r ' + DC_CODE_TAG + ' dcsf/www.distributedcity.com')
		self.syscmd('cvs -d ' + self.cvsroot + ' export -d ' + LP_DIR + ' -r ' + DC_CODE_TAG + ' dcsf/lp_common')
		
		# Kill any of our config files from the conf/sites/dir
		os.chdir(DC_CODE_DIR + '/conf/sites')
		
		self.syscmd('rm -rf *' + 'estaban' + '*')
		self.syscmd('rm -rf *' + 'hayek' + '*')
		self.syscmd('rm -rf *' + 'lp1' + '*')
		self.syscmd('rm -rf *' + 'prod' + '*')
		
		# Kill any of our config files from the conf/sites/dir
		os.chdir(DC_CODE_DIR)
		
		# kill other test files
		self.syscmd('rm -rf test')
		
		# Tar up the dc source
		os.chdir(BASE_DIR)
		self.syscmd('tar cfz ' + TAR_DEST_DIR + '/' + DC_TAR_FILENAME + ' ' + PACKAGE_NAME)
		
		self.syscmd('chmod -R 770 ' + self.tar_dest)
		#self.syscmd('chgrp -R apache ' + self.tar_dest)
		#self.syscmd('chown -R websites ' + self.tar_dest)
		
		#clean up
		self.syscmd('rm -rf  ' + PACKAGE_DIR)
		
		print 'tarballs are located in directory ' + TAR_DEST_DIR

	def commitVersion(self, tag_prefix):
		# return
		os.chdir(self.dc_dir)
		new = self.getCurrentVerString()
		cmd = "cvs -d %s commit -m 'site release, new version: %s' %s" % (self.cvsroot, new, self.conf_file)
		self.syscmd( cmd )
		self.tagBuild(tag_prefix)
		
	def cvsUpdate(self):
		# return
		os.chdir(self.dc_dir)
		cmd = "cvs -d %s update -dP" % (self.cvsroot)
		self.syscmd( cmd )
		os.chdir(self.lp_dir)
		cmd = "cvs -d %s update -dP" % (self.cvsroot)
		self.syscmd( cmd )

	def sfUpNightly(self):
		self.sourceforgeUpload('nightly')

	def sfUpRelease(self):
		self.sourceforgeUpload('release')

	def sourceforgeUpload(self, dir_prefix):
#	   1. FTP to upload.sourceforge.net
#	   2. Login as "anonymous"
#	   3. Use your e-mail address as the password for this login
#	   4. Set your client to binary mode ("bin" on command-line clients)
#	   5. Change your current directory to /incoming ("cd /incoming")
#	   6. Upload the desired files for the release ("put filename")

		self.syscmd( "ncftpput -u anonymous -p anonymous@anonymous.org upload.sourceforge.net /incoming %s/%s/%s" % (self.tar_dest, dir_prefix, self.getTarFileName() ) )

		print "\n\n *** don't forget to visit Soureforge and actully release the packages ***\n\n"

            

def print_help():
	print "Usage: python manage_release.py [option]"
	print "where option can be:"
	print " - currentver    -- display current version info"
	print " - cvs_up        -- update site, using current tag"
	print " - nightly       -- package nightly build, bump version timestamp"
	print " - site_release  -- package build, bump minor version, timestamp"
	print " - sf_up_release -- upload new site release to sourceforge"
	print " - sf_up_nightly -- upload nightly build to sourceforge"
	print " - site_all      -- update + site_release + sf_upload"
	print " - nightly_all   -- update + nightly + sf_upload"

if __name__ == '__main__':
	if( len(sys.argv) < 2 ):
		print_help()
	else:
		option = sys.argv[1]
		
		manager = ManageDCRelease()
		
		if option == 'currentver':
			manager.currentVer()
		elif option == 'site_release':
			manager.siteRelease()
		elif option == 'nightly':
			manager.nightlyBuild()
		elif option == 'sf_up_nightly':
			manager.sfUpNightly()
		elif option == 'sf_up_release':
			manager.sfUpRelease()
		elif option == 'site_all':
			manager.siteAll()
		elif option == 'nightly_all':
			manager.nightlyAll()
		elif option == 'cvs_up':
			manager.cvsUpdate()
		else:
			print_help()
		                        
