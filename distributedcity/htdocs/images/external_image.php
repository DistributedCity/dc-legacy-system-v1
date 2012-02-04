<?php

// get image util function - pass imagePath and imageName in name/value pairs in url
header("Content-type: image/jpeg");
readfile("/websites/lfc_common/images/progress_bar_skins/" . $imageName);

?>