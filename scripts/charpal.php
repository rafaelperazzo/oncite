<?php

$ranges = array( 
             "Punctuation" => array("Punctuation", 128, 191)
             ,"Latin" => array("Latin", 192, 383)
         //    ,"Foreign characters I" => array("Foreign characters I", 384, 512)
         //    ,"Cyrillic" => array("Cyrillic", 592, 683)
         //    ,"Diacritics" => array("Diacritics", 768, 846)
             ,"Greek" => array("Greek", 900, 974)
             ,"Cyrillic uppercase" => array("Cyrillic uppercase", 1024, 1119)
             ,"Cyrillic lowercase" => array("Cyrillic lowercase", 1328, 1415)
             ,"Latin diacritics" => array("Latin diacritics", 7840, 7930)
         //    ,"Greek diacritics I" => array("Greek diacritics I", 7936, 8064)
         //    ,"Greek diacritics II" => array("Greek diacritics II", 8064, 8192)
              );

$categories = array(
     "Greek and latin" => array("Greek","Latin"),
     "Further characters" => array(//"Foreign characters I","Cyrillic",
	                              "Cyrillic uppercase","Cyrillic lowercase"),
     "Punctuation and diacritics" => array("Punctuation" //,"Diacritics"
	                                     ),
     "Latin/greek diacritics" => array("Latin diacritics"   // ,"Greek diacritics I","Greek diacritics II"
	                                   ),
	 );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Character palette</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
body {margin: 0px; padding: 0px;}
h1 {font-size: 10pt; float: left;  padding: 5px; margin: 2px;
      font-weight: normal;
	  border-top: 2px solid silver;
	  border-right: 2px solid black;
	  border-bottom: 2px solid black;
	  border-left: 2px solid silver;
	  background: rgb(153,153,153);
	  color: white;
	  }
h1 a{ text-decoration: none; color: white; }
td {text-align: center;}
input.charbutton {width: 30px;}
table {clear: both; }
</style>
<script type="text/javascript">
var fieldref;
var myInputBox;

if(window.opener.document.getElementById)
  fieldref = window.opener.document.getElementById("<?php echo $_REQUEST['fieldname'] ?>");
if((!fieldref) && window.opener.<?php echo $_REQUEST['formname'] ?>)
  fieldref = window.opener.<?php echo $_REQUEST['formname'] ?>.<?php echo $_REQUEST['fieldname'] ?>;

function checksize(t) {
   if(t.value.length>511) {
      t.value=t.value.substr(0,511);
      alert("You've exceeded the limit of 511 characters");
   }
}

function doThisOnLoad()
{
  showPanel('Greekandlatin');

  if(document.getElementById && document.getElementById('thecontentbox'))
    myInputBox = document.getElementById('thecontentbox');
  else
    myInputBox = document.forms[0].thecontent;
}

// var fieldref = window.opener.forms["<?php echo $_REQUEST['formname'] ?>"].elements["<?php echo $_REQUEST['fieldname'] ?>"];

function insertchr(chr)
{
  // fieldref.value += chr;
  myInputBox.value += chr;
  myInputBox.focus();
  checksize(myInputBox);
}

function insertAllAndClose()
{
//  alert(document.getElementById('thecontentbox').value);
  fieldref.value = myInputBox.value;
  window.close();
}

function tagThis(tagName, tagDescription)
{
  var val = prompt("Enter text to be "+tagDescription+". The appropriate HTML tags will be added to the text.", "");
  if(val && val!="")
    insertchr("<"+tagName+">"+val+"</"+tagName+">");
}

function showPanel(panelname)
{
  <?php 
    foreach($categories as $catname=>$catdata)
	{
      echo "\n   document.getElementById(\"" . preg_replace('/\W+/', '', $catname) . "\").style.display = \"none\";";
      echo "\n   document.getElementById(\"headfor" . preg_replace('/\W+/', '', $catname) . "\").style.background = \"rgb(153,153,153)\";";
	}
  ?>
  
  document.getElementById(panelname).style.display = "block";
  document.getElementById('headfor'+panelname).style.background = "rgb(204,204,204)";
}
</script>
</head>

<body onload="doThisOnLoad();">

<form onsubmit="insertAllAndClose()" action=".">
<input type="button" value="Superscript" onclick="tagThis('sup', 'superscripted')" />
<input type="button" value="Subscript" onclick="tagThis('sub', 'subscripted')" />
<input type="button" value="Bold" onclick="tagThis('b', 'made bold')" />
<input type="button" value="Italic" onclick="tagThis('i', 'made italic')" />
<br />
  <textarea name="thecontent" cols="50" rows="3" id="thecontentbox" onkeyup="checksize(this)" onpaste="checksize(this)"><?php echo htmlspecialchars(stripslashes($_REQUEST['content'])); ?></textarea>
<input type="button" value="OK" onclick="insertAllAndClose()" />

<?php
foreach($categories as $catname=>$cat)
{
  echo "\n<h1 id='headfor" . preg_replace('/\W+/', '', $catname) . "'><a href='javascript:showPanel(\"" . preg_replace('/\W+/', '', $catname) . "\")'>$catname</a></h1>";
}

foreach($categories as $catname=>$cat)
{
  echo "\n\n<div id='" . preg_replace('/\W+/', '', $catname) . "'>";
  echo "\n<table><tr>\n";
	$count = 0;
  foreach($cat as $rangename)
  {
    $range = $ranges[$rangename];
	// echo "\n<td>Range $range[0]: $range[1] to $range[2]</td>";
	for($i=$range[1]; $i<=$range[2]; $i++)
	{
      $chr = "&#" . ($i) . ";";
	  echo "<td><input type='button' class='charbutton' value=' $chr ' onclick='javascript:insertchr(\"$chr\")' /></td>";
	  if((++$count % 16 == 0) && ($i < $range[2]))
		 echo "</tr>\n  <tr>";
	}
	
  }
  echo "\n</tr>
  </table></div>";
}
/*
for($ii=128; $ii<8366; $ii+=128)
{
	$count = 0;
	echo "\nRange $ii to " . ($ii+128);
	echo "\n<table><tr>\n";
	for($i=0; $i<128; $i++)
	{
      $chr = "&#" . ($i+$ii) . ";";
	  echo "<td><input type='button' value=' $chr ' onclick='javascript:insertchr(\"$chr\")' /></td>";
	  if(++$count % 16 == 0)
		 echo "</tr>\n  <tr>";
	}
	
	echo "\n</tr>
	</table><hr />";
}
*/
?>
</form>

</body>
</html>
