<?php

// $action==user

switch($_REQUEST['subaction']){
  case 'edit':

    // Store info if submitted
    if(strlen($firstname) && strlen($lastname) && strlen($userid) && strlen($origuserid)){
    
      $q = "UPDATE USERS SET userid='".htmlspecialchars($userid)
	               ."', title='".htmlspecialchars($title)
	               ."', firstname='".htmlspecialchars($firstname)
	               ."', lastname='".htmlspecialchars($lastname)
	               ."', honorifics='".htmlspecialchars($honorifics)
	               ."', email='".htmlspecialchars($email)
	               ."', userhasleft='".htmlspecialchars($userhasleft)
	               ."', deptid='".htmlspecialchars($deptid)
	               ."', otherdepts='' WHERE userid='".htmlspecialchars($origuserid)
	               ."' LIMIT 1";
      
	 mysql_query($q, connectpubsdb());
	 if(is_array($otherdepts))
        addUserOtherDepts($userid, $otherdepts);
    }


    // Fetch and display info
    
    $q = 'SELECT * FROM USERS WHERE userid="'.mysql_real_escape_string($userid).'" LIMIT 1';
    $res = mysql_query($q, connectpubsdb());
    if($res && mysql_num_rows($res)!=0){
    $u = mysql_fetch_assoc($res);
    
    ?>
    <h3>Editing user <?php echo htmlspecialchars($u['userid']) ?></h3>
    <form method="post" action="./" style="border: 1px solid gray; padding: 10px; margin: 10px;">
  <label>User ID:<input type="text" name="userid" id="userid" size="7" maxlength="7" value="<?php echo htmlspecialchars($u['userid']) ?>" /></label>
  <br />
  <label>Title:<input type="text" name="title" id="title" size="20" maxlength="40" value="<?php echo htmlspecialchars($u['title']) ?>" /></label>
  <br />
  <label>First name:<input type="text" name="firstname" id="firstname" size="20" maxlength="40" value="<?php echo htmlspecialchars($u['firstname']) ?>" /></label>
  <br />
  <label>Last name:<input type="text" name="lastname" id="lastname" size="20" maxlength="40" value="<?php echo htmlspecialchars($u['lastname']) ?>" /></label>
  <br />
  <label>Honorofics:<input type="text" name="honorifics" id="honorifics" size="20" maxlength="40" value="<?php echo htmlspecialchars($u['honorifics']) ?>" /></label>
  <br />
  <label>Email:<input type="text" name="email" id="email" size="20" maxlength="40" value="<?php echo htmlspecialchars($u['email']) ?>" /></label>
  <br />
  <label>Marked as having left (i.e. hidden):<input type="checkbox" name="userhasleft" id="userhasleft" value="1" <?php if($u['userhasleft']) echo ' checked="checked" ' ?>" /></label>
  <br />
  <label>Home department:     <select name="deptid" id="deptid">
  <?php
    $depts = getdepts();
      foreach($depts as $dept){
	   ?>
	   <option value="<?php echo $dept['DEPTID'] ?>" <?php if($dept['DEPTID']==$u['deptid']) echo ' selected="selected" ' ?>><?php echo $dept['NAME'] ?></option>
	   <?php
	 }
  ?>
  </select>
  </label>
  <br />
  <label>Other department(s) (if jointly appointed):     <select name="otherdepts[]" id="otherdepts[]" multiple="multiple" size="5">
  <?php
    $depts = getdepts();
      foreach($depts as $dept){
	   ?>
	   <option value="<?php echo $dept['DEPTID'] ?>" <?php if(strpos($u['otherdepts'], ','.$dept['DEPTID'].',')!==false) echo ' selected="selected" ' ?>><?php echo $dept['NAME'] ?></option>
	   <?php
	 }
  ?>
  </select>
  </label>
  <br />
  <input type="hidden" name="origuserid" value="<?php echo htmlspecialchars($u['userid']) ?>"/>
  <input type="hidden" name="action" value="user"/>
  <input type="hidden" name="subaction" value="edit"/>
  <input type="submit" value="Store"/>
    </form>
    <?php
    }else{
       ?><p>Nothing found to match query.</p><?php
    }
    break;
  case 'search':
  
    $q = 'SELECT * FROM USERS WHERE userid LIKE "%'.mysql_real_escape_string($userid)
                .'%" AND firstname LIKE "%'.mysql_real_escape_string($firstname)
			 .'%" AND lastname LIKE "%'.mysql_real_escape_string($lastname)
			 .'%"  ';
    // Departments
    if(!is_array($deptid) || implode('', $deptid)==''){
    }else{
      $q .= ' AND (0 ';
	 foreach($deptid as $id){
        $q .= ' OR deptid="'.mysql_real_escape_string($id) .'" ';
	 }
      $q .= ')';
    }
    
    $q .= ' ORDER BY LASTNAME, FIRSTNAME';
    
    $res = mysql_query($q, connectpubsdb());
    
    if($res && mysql_num_rows($res)!=0){
      ?><ul><?php
	 while($u = mysql_fetch_assoc($res)){
	   echo "\n       <li style='margin-bottom: 10px;'>$u[title] $u[firstname] <strong>$u[lastname]</strong> ($u[userid])";
	   echo " &middot; [<a href='./?action=user&userid=$u[userid]&subaction=edit'>Edit details</a>]";
	   echo " &middot; [<a href='./?action=deluser&userid=$u[userid]'>Delete</a>]";
	   echo "</li>";
	 }
      ?></ul><?php
    }else{
       ?><p>Nothing found to match query.</p><?php
    }
    
    break;
  default:
  ?><form method="post" action="./" style="border: 1px solid gray; padding: 10px; margin: 10px;">
  <p>Search for a user(s) to manage - fill in as many search options as you wish:</p>
  <label>User ID:<input type="text" name="userid" id="userid" size="7" maxlength="7"/></label>
  <label>First name:<input type="text" name="firstname" id="firstname" size="20" maxlength="40"/></label>
  <label>Last name:<input type="text" name="lastname" id="lastname" size="20" maxlength="40"/></label>
  <label>Department(s):     <select name="deptid[]" size="15" multiple="multiple" id="deptid[]">
  <option value=''>Any</option>
  <?php
    $depts = getdepts();
      foreach($depts as $dept){
	   ?>
	   <option value="<?php echo $dept['DEPTID'] ?>"><?php echo $dept['NAME'] ?></option>
	   <?php
	 }
  ?>
  </select>
  </label>
  <input type="hidden" name="action" value="user"/>
  <input type="hidden" name="subaction" value="search"/>
  <input type="submit" value="Search"/>
  </form><?php
    break;
}






?>