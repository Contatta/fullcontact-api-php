<?php
require_once('../src/FullContact/FullContactAPIException.php');
require_once('../src/FullContact/FullContactAPI.php');

//initialize our FullContact API object
//get your api key here:  http://fullcontact.com/getkey
$fullcontact = new \FullContact\FullContactAPI('YOUR_API_KEY_HERE');

//do a lookup
$result = $fullcontact->doLookup('bart@fullcontact.com');

//dump our results
echo "<br/>----------------<br/><pre>";
var_dump($result, true);
echo "</pre><br/>----------------<br/>";
