<?php

//DELETEME $elements = $stack = array();
$internalxmlelementstack = array(); // Use push and pop to maintain a context list in this array

class xmlElement
{
  var $name = '';
  var $attributes = array();
  var $content = array();

  function xmlElement($myname = '', $myattributes = array(), $mycontent = array())
  {
    $this->name = $myname;
	$this->attributes = $myattributes;
	$this->content = $mycontent;
  }
}


// The function which pushes all the data through the Expat parser
function parseOaiXmlFromString($parser, $data)
{
  return xml_parse($parser, $data, true);
}

// Expat calls this when it finds an opening tag
function xmlparser_start_element($parser, $name, $attrs)
{
  global $internalxmlelementstack;
  
  $element = new xmlElement($name, $attrs);
//DELETEME  $element->name = $name;
//DELETEME  $element->attributes = $attrs;
  
  // Add it to the context list
  array_push($internalxmlelementstack, $element);
}

// Expat calls this when it finds a closing tag
function xmlparser_stop_element($parser, $name)
{
  global $internalxmlelementstack;

  if(sizeof($internalxmlelementstack)>1)
  {
    // Remove it from the context list
    array_pop($internalxmlelementstack);
  }
}

// Expat calls this when it finds character data
function xmlparser_char_data($parser, $data)
{
  global $internalxmlelementstack;
  
  $data = $data;
  
  if(trim($data)!='')
  {
    $internalxmlelementstack[sizeof($internalxmlelementstack)-1]->content[] = $data;
    $name = $internalxmlelementstack[sizeof($internalxmlelementstack)-1]->name;
  }
}
?>