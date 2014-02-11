<h1>Frequently Asked Questions about administering publications</h1>
<?php

$f = implode('', file('content.php',1));

if(preg_match_all('{<h\d>(.+?)</h\d>(.+?)((?=<h\d)|$)}is', $f, $matches, PREG_SET_ORDER))
{
  echo "\n<hr />\n<ol>";
  foreach($matches as $entry)
    echo "\n  <li><a href=\"#" . rawurlencode($entry[1]) . "\">$entry[1]</a></li>";
  echo "\n</ol>\n\n<hr />\n\n";
  foreach($matches as $entry)
    echo "\n<a name=\"" . rawurlencode($entry[1]) . "\"></a><h3>$entry[1]</h3><div style=\"margin-left: 30px;\">$entry[2]<p>&nbsp;</p></div>";
}
else
  echo $f; // Simple fallback in case of error

?>