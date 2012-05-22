<?php

  // an authlogic default sha512 hashing function
  function authlogic_hash($password, $salt) {
    $data = "$password$salt";
    // hash it 20 times
    for ($i=0; $i<20; $i++) {
      $data = hash('sha512',$data);
    }
    return $data;
  }
  
  // check if user's profile exists in phpbb database
  function profile_exists($username, $db_connection) {
    global $db, $config;
    $request = sprintf("select * from %s where username='%s'", USERS_TABLE, $username);
    $result = $db->sql_query($request);
    if ($result) {
      $row = $db->sql_fetchrow($result);
      if ($row) {
        return true;
      }
    }
    return false;
  }

  // main login function phpbb hooks to
  // refer to phpbb3 docs to learn how it works
  function login_rails($username, $password) {
    $config = parse_ini_file('auth_rails.ini.php');
    //print_r($config);
    $db_connection = mysql_pconnect($config['db_host'], $config['db_user'], $config['db_password']);
    if ($db_connection) {
      if (mysql_select_db($config['db_name'], $db_connection)) {
        $username_safe = mysql_real_escape_string($username, $db_connection);
        $query_str = sprintf("select id,crypted_password,password_salt,email from users where username='%s'", 
                              $username_safe);
        $query_result = mysql_query($query_str, $db_connection);
        if (!$query_result) {
          //echo "cannot execute query $query_str\n";
          return array(
            'status' => LOGIN_ERROR_EXTERNAL_AUTH,
            'error_msg' => 'Cannot execute query',
            'user_row' => array('user_id' => ANONYMOUS),
            );
        } else {
          $row = mysql_fetch_row($query_result);
          $user_id = 1000 + $row[0];
          $crypted_password = $row[1];
          $password_salt = $row[2];
          $email = $row[3];
          if ($crypted_password == authlogic_hash($password,$password_salt)) {
            if (profile_exists($username_safe, $db_connection)) {
              //echo "login ok\n";
              return array(
                'status' => LOGIN_SUCCESS,
                'error_msg' => false,
                'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email,),
                );                    
            } else {
              //echo "login ok, creating profile\n";
              return array(
                'status' => LOGIN_SUCCESS_CREATE_PROFILE,
                'error_msg' => false,
                'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email, 'user_type' => USER_NORMAL, 'group_id' => 2,),
                );                            
            }
          } else {
	    //echo 'auth failure';
	    return array(
	      'status' => LOGIN_ERROR_PASSWORD,
	      'error_msg' => 'LOGIN_ERROR_PASSWORD',
	      'user_row' => array('user_id' => ANONYMOUS),
	      );	    
          }
        }
      } else {
	$rails_db_name = $config['db_name'];
        //echo "cannot select db: $rails_db_name\n";
        return array(
          'status' => LOGIN_ERROR_EXTERNAL_AUTH,
          'error_msg' => 'LOGIN_ERROR_PASSWORD',
          'user_row' => array('user_id' => ANONYMOUS),
          );
      }
    } else {
      //echo "cannot connect to database\n";
      return array(
        'status' => LOGIN_ERROR_EXTERNAL_AUTH,
        'error_msg' => 'LOGIN_ERROR_PASSWORD',
        'user_row' => array('user_id' => ANONYMOUS),
        );
    }  
  }
  
/*  function autologin_rails() {
    $config = parse_ini_file('auth_rails.ini.php');
    print_r($config);
    $db_connection = mysql_pconnect($config['db_host'], $config['db_user'], $config['db_password']);
    if ($db_connection) {
      if (mysql_select_db($config['db_name'], $db_connection)) {
	$cookie = split("%3A%3A",$_COOKIE['user_credentials']);
	$persistence_token = $cookie[0];
	
        $query_str = sprintf("select id,email,username from users where persistence_token='%s'", 
                              $persistence_token);
        $query_result = mysql_query($query_str, $db_connection);
        if (!$query_result) {
          echo "cannot execute query $query_str\n";
          return array(
            'status' => LOGIN_ERROR_EXTERNAL_AUTH,
            'error_msg' => 'Cannot execute query',
            'user_row' => array('user_id' => ANONYMOUS),
            );
        } else {
          $row = mysql_fetch_row($query_result);
          $user_id = 1000 + $row[0];
          $email = $row[1];
	  $username = $row[2];
	  if (profile_exists($username, $db_connection)) {
	    echo "login ok\n";
	    return array(
	      'status' => LOGIN_SUCCESS,
	      'error_msg' => false,
	      'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email,),
	      );                    
	  } else {
	    echo "login ok, creating profile\n";
	    return array(
	      'status' => LOGIN_SUCCESS_CREATE_PROFILE,
	      'error_msg' => false,
	      'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email, 'user_type' => USER_NORMAL, 'group_id' => 2,),
	      );                            
	  }
	}
      } else {
	$rails_db_name = $config['db_name'];
        echo "cannot select db: $rails_db_name\n";
        return array(
          'status' => LOGIN_ERROR_EXTERNAL_AUTH,
          'error_msg' => 'Cannot select db: $rails_db_name',
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
*/
    
?>
