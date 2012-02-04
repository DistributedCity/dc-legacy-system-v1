<?php

// GenericVersion - a class for interpreting LP VERSION files.


/* Here is a sample VERSION file, self-documenting.

# LP Versioning, in short:
#
# Released Package Version Format:
# PACKAGE_NAME.MAJOR.MINOR.TIMESTAMP[.SYMBOLIC]
#
#  PACKAGE_NAME = name of package, duh
#  
#  MAJOR        = major version.  This is always human edited.
#  
#  MINOR        = minor version.  This is always auto-incremented.
#                 It auto-resets to 0 if MAJOR is bumped.
#  
#  TIMESTAMP    = unix timestamp. This is always auto-generated.
#
#  SYMBOLIC     = optional symbolic identifier. This must be human edited.
#                 If previous version contains same identifier, it is erased.
#
# Other variables used in this file:
#
#  LAST_VERSION = the previous version. used for comparisons.
#  

# pick a name for ourselves
PACKAGE_NAME = distributedcity

# We aren't even 1.0 yet.
MAJOR = 4 

#### DO NOT TOUCH THIS LINE OR BELOW ###

# actually, you can edit this value, but it will get blow away next time.
SYMBOLIC = 

# leave this alone!
MINOR = 30

# leave this alone!
LAST_MAJOR = 4

# leave this alone!
TIMESTAMP = 1024411420

*/


include_once( 'class_GenericObject.php' );

class GenericVersion extends GenericObject {

/* Public Methods
 */
   function GenericVersion($config) {
      $this->GenericObject($config);

      $this->initAttr('major', null );
      $this->initAttr('minor', null );
      $this->initAttr('timestamp', null );
      $this->initAttr('package_name', null );
      $this->initAttr('symbolic', null );
   }

   function getFullVersion() {
      $pkg = $this->getPackageName();
      $ver = $this->getVersion();

      return "$pkg-$ver";
   }

   function getVersion() {
      $mjr = $this->getMajor();
      $mnr = $this->getMinor();
      $tim = $this->getTimestamp();
      $sym = $this->getSymbolic();

      return $sym ? 
         "$mjr.$mnr.$tim.$sym" :
         "$mjr.$mnr.$tim";
   }

   function getPackageName() {
      return $this->getAttr('package_name');
   }

   function getMajor() {
      return $this->getAttr('major');
   }

   function getMinor() {
      return $this->getAttr('minor');
   }

   function getTimestamp() {
      return $this->getAttr('timestamp');
   }

   function getSymbolic() {
      return $this->getAttr('sybolic');
   }

   function parseVersionFile($file) {
      $lines = file( $file );

      foreach( $lines as $line) {
         if( $line[0] != '#' && strlen( $line ) ) {
            list( $key, $val ) = explode('=', $line);

            $this->setAttr( strtolower(trim($key)), trim($val));
         }
      }
   }

   function parseVersionString($str) {
      $parts = explode( $str, '.');
      $sym = $pkg = null;

      if( strstr( $parts[0], '-' ) ) {
         list( $pkg, $mjr ) = explode( $parts[0], '-' );
      }
      else {
         $mjr = $parts[0];
      }
      $mnr = $parts[1];
      $tim = $parts[2];
      if( count( $parts ) > 3 ) {
         $sym = $parts[3];
      }
      $this->setAttr('major', $mjr );
      $this->setAttr('minor', $min );
      $this->setAttr('timestamp', $tim );
      $this->setAttr('package_name', $pkg );
      $this->setAttr('symbolic', $sym );
   }

/* Pure Virtual (Protected) Methods
 */

};


?>
