<?php

// Get an external image. Prevents the destination host from seeing
// a users IP information. Basically an external image proxy.
header("Content-type: image/gif");
//readfile("http://".$image_url);

//include "Snoopy.class.inc";

    $snoopy = new Snoopy;
    $snoopy->user = "joe";
    $snoopy->pass = "bloe";
    
//    if($snoopy->fetch("$image_urlhttp://pf.mailvault.com/pf.php?username=" .$username . "&systemname=" . $this->systemname . "&action=login&returnURL=" . $returnURL))

    if($snoopy->fetch($image_url)){
        print $snoopy->results;

      } else {

        echo "error fetching document: ".$snoopy->error."\n"; 
      }
//}

?>