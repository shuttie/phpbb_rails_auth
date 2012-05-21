<?php

  define("LOGIN_ERROR_EXTERNAL_AUTH", 1);
  define("LOGIN_SUCCESS", 1);
  define("ANONYMOUS", 1);
  define("USERS_TABLE", "users");
  
  function authlogic_hash($password, $salt) {
    $data = "$password$salt";
    for ($i=0; $i<20; $i++) {
      $data = hash('sha512',$data);
    }
    return $data;
  }
  
  function profile_exists($username, $db_connection) {
    $request = sprintf("select * from %s where username='%s'", USERS_TABLE, $username);
    $result = mysql_query($request, $db_connection);
    if ($result) {
      $row = mysql_fetch_row($result);
      if ($row) {
	return true;
      }
    }
    return false;
  }

  function login_rails($username, $password) {
    $db_user = "root";
    $db_password = "";
    $db_name = "bookworm2";
    $db_connection = mysql_pconnect("localhost", $db_user, $db_password);
    if ($db_connection) {
      echo "connected to db\n";
      if (mysql_select_db($db_name, $db_connection)) {
	$username_safe = mysql_real_escape_string($username, $db_connection);
	$query_str = sprintf("select id,crypted_password,password_salt,email from users where username='%s'", 
			      $username_safe);
	$query_result = mysql_query($query_str, $db_connection);
	if (!$query_result) {
	  echo "cannot execute query\n";
	  return array(
	    'status' => LOGIN_ERROR_EXTERNAL_AUTH,
	    'error_msg' => 'Cannot execute query',
	    'user_row' => array('user_id' => ANONYMOUS),
	    );
	} else {
	  $row = mysql_fetch_row($query_result);
	  $user_id = $row[0];
	  $crypted_password = $row[1];
	  $password_salt = $row[2];
	  $email = $row[3];
	  if ($crypted_password == authlogic_hash($password,$password_salt)) {
	    if (profile_exists($username_safe, $db_connection)) {
	      echo "login ok\n";
	      return array(
		'status' => LOGIN_SUCCESS,
		'error_msg' => false,
		'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email),
		);	    	      
	    } else {
	      echo "login ok, create profile $email\n";
	      return array(
		'status' => LOGIN_SUCCESS_CREATE_PROFILE,
		'error_msg' => false,
		'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email),
		);	    	      	      
	    }
	  }
	}
      } else {
	echo "cannot select db\n";
	return array(
	  'status' => LOGIN_ERROR_EXTERNAL_AUTH,
	  'error_msg' => 'Cannot select db',
	  'user_row' => array('user_id' => ANONYMOUS),
	  );
      }
    } else {
      echo "cannot connect to database\n";
      return array(
	'status' => LOGIN_ERROR_EXTERNAL_AUTH,
	'error_msg' => 'Cannot connect to database',
	'user_row' => array('user_id' => ANONYMOUS),
	);
    }  
  }
  
  login_rails("shutty", "zlo4menow");
  //authlogic_hash("zlo4menow","8jrTngyVTanTQbhDNOoJ");
  
?>