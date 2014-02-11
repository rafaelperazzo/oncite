<h2>Change publication type</h2><?php
// REQUIRED INFO:
// $p  =  Publication record

    require_once($config['homedir'] . 'query/formataref.php');
    echo "<p>" . formataref($p, $_SERVER['REMOTE_USER'], '', false, false, false) . "</p>";

	  $ps = getPubTypesAllData();
	  $cols = 3;
	  $rowspercol = ceil(sizeof($ps)/$cols);
	  $count = 1;
	  ?>

	  <div class="simplepaddedmessagebox">
	  <p><strong>Warning:</strong> Because the fields for each reference type are different, 
	  <strong>some of the data may 
	  not carry across</strong> when the publication type is changed.</p>
	  <p>Please choose the new publication type from the list below.
	  (Or to cancel just use your &quot;Back&quot; button.)</p>
	  </div>

	  <table border="0"><tr><td valign="top" align="left">
	  <ul>
		<?php
		foreach($ps as $k=>$v)
		{
		  if($k != $p['reftype'])
		    echo "\n  <li><a href=\"./?action=edit&pubid=$pubid&changepubtype=$k&oldpubtype=" . $p['reftype'] 
						  . (strlen($userid)>0?'&userid='.rawurlencode($userid):'')
						  . "\" onclick=\"return confirm('Are you sure you want to change the reference type for this entry?')\">" . htmlspecialchars($v['reftypename']) 
						  . "</a></li>";
		  else
		    echo "\n  <li><strong>" . htmlspecialchars($v['reftypename']) 
						  . "</strong>&nbsp;<i>(current&nbsp;type)</i></li>";

		  if(($count++ % $rowspercol) == 0)
			echo "\n </ul></td><td valign=\"top\" align=\"left\"><ul>";
		}
		?>
	  </ul>
	  </td></tr></table>
	  <?php



?>