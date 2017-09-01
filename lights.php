<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
//we will test with simple RGB values delimited by colons
if ($_REQUEST['board']==1) {
    print "255:255:255";
}
else if ($_REQUEST['board']==2) {
    print "128:128:128";
}
else if ($_REQUEST['board']==3) {
    print "255:255:255";
}

else print "255:255:255";