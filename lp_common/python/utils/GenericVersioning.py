import GenericConfReaderWriter, time, string, sys

# class to make dealing with an LP VERSION conf file easy.
#
# Specifically, this class can read in a VERSION file so you can
# query the various parts of the version and give a canonical
# string, and it will also auto increment the VERSION file.
#
# A VERSION file looks something like this:
#
#  # LP Versioning, in short:
#  #
#  # Released Package Version Format:
#  # PACKAGE_NAME.MAJOR.MINOR.TIMESTAMP[.SYMBOLIC]
#  #
#  #  PACKAGE_NAME = name of package, duh
#  #  
#  #  MAJOR        = major version.  This is always human edited.
#  #  
#  #  MINOR        = minor version.  This is always auto-incremented.
#  #                 It auto-resets to 0 if MAJOR is bumped.
#  #  
#  #  TIMESTAMP    = unix timestamp. This is always auto-generated.
#  #
#  #  SYMBOLIC     = optional symbolic identifier. This must be human edited.
#  #                 If previous version contains same identifier, it is erased.
#  #
#  # Other variables used in this file:
#  #
#  #  LAST_VERSION = the previous version. used for comparisons.
#  #  
#  
#  # pick a name for ourselves
#  PACKAGE_NAME = distributedcity
#  
#  # Okay, we are version 2.0 already
#  MAJOR = 2
#  
#  #### DO NOT TOUCH THIS LINE OR BELOW ###
#  
#  # actually, you can edit this value, but it will get blow away next time.
#  SYMBOLIC = 
#  
#  # leave this alone!
#  MINOR = 19
#  
#  # leave this alone!
#  LAST_MAJOR = 2
#  
#  # leave this alone!
#  TIMESTAMP = 1024373078


class GenericVersioning:
	def __init__(self, file):
		self.conf_file = file
		self.conf = GenericConfReaderWriter.GenericConfReaderWriter(file)

		# set(name, default=None, post=None, required=0, list=0):
		self.conf.set('PACKAGE_NAME', None, None, 1, None)
		self.conf.set('MAJOR', None, None, 1, None)
		self.conf.set('MINOR', '0')
		self.conf.set('TIMESTAMP', time.time())
		self.conf.set('SYMBOLIC', '')
		self.conf.set('LAST_MAJOR', '0')

		try:
			self.conf.parse()								# parse the file
		except GenericConfReaderWriter.GenericConfReader.ConfigMissingError, why:
			print file, "Missing config", why

	# does everything. reads old version, determines new one, writes it.
	def process(self):
		version = self.readVersion()
		self.updateAndWriteVersion(version)

	# reads version from conf file
	def readVersion(self):
		return GenericVersion(self.conf.PACKAGE_NAME, 
									 self.conf.MAJOR,
									 self.conf.MINOR, 
									 self.conf.TIMESTAMP,
									 self.conf.LAST_MAJOR,
									 self.conf.SYMBOLIC)

	# writes version to conf file
	def writeVersion(self, version):
		self.conf.set_attr('MINOR', version.minor)
		self.conf.set_attr('TIMESTAMP', version.point)
		self.conf.set_attr('SYMBOLIC', version.symbolic)
		self.conf.set_attr('LAST_MAJOR', version.last_major)

		self.conf.write(self.conf_file)

	# updates version and writes to conf file
	def updateAndWriteVersion(self, version):
		new_version = self.updateVersion(version)

		self.writeVersion(new_version)

	# updates version
	def updateVersion(self, version):
		self.updateTimeStamp(version)
		self.updateMinor(version)
		self.updateSymbolic(version)

		return version

	# updates time stamp
	def updateTimeStamp(self, version):
		version.point = str(int(time.time()))
		return version

	# updates minor version
	def updateMinor(self, version):
		if(version.last_major != version.major):
			version.minor = '0'
			version.last_major = version.major
		else:
			version.minor = str(string.atoi(version.minor) + 1)

		return version

	# updates symbolic name
	def updateSymbolic(self, version):
		version.symbolic = ''



class GenericVersion:
	def __init__(self, name, major, minor, point, last_major, symbolic=''):
		# public attrs.
		self.name = name
		self.major = major
		self.minor = minor
		self.point = point
		self.symbolic = symbolic
		self.last_major = last_major

	def __repr__(self):
		return self.getFullVersion()

	def getVersion(self):
		if self.symbolic:
			return "%s.%s.%s.%s" % (self.last_major, self.minor, self.point, self.symbolic)
		else:
			return "%s.%s.%s" % (self.last_major, self.minor, self.point)

	def getFullVersion(self):
		return self.name + '-' + self.getVersion()
	

def print_help():
	print 'Usage: python GenericVersioning.py <VERSION> [nightly]'
	print ' - VERSION = path to VERSION file'
	print ' - nightly = increments VERSION timestamp only, not minor'

if __name__ == '__main__':
	if( len(sys.argv) < 2 ):
		print_help()
	else:
		file = sys.argv[1]
		versioning = GenericVersioning(file)
		version = versioning.readVersion()
		
		print 'old version:', version;
		
		if len(sys.argv) > 2 and sys.argv[2] == 'nightly':
			versioning.updateTimeStamp(version)
			versioning.writeVersion(version)
		else:
			versioning.process()
		
		print 'new version:', versioning.readVersion()

	
