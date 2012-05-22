<?php

  include 'auth_rails.conf.php';
  
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
    global $rails_db_user, $rails_db_password, $rails_db_host, $rails_db_name;
    $db_connection = mysql_pconnect($rails_db_host, $rails_db_user, $rails_db_password);
    if ($db_connection) {
      if (mysql_select_db($rails_db_name, $db_connection)) {
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
          $user_id = 1000 + $row[0];
          $crypted_password = $row[1];
          $password_salt = $row[2];
          $email = $row[3];
          if ($crypted_password == authlogic_hash($password,$password_salt)) {
            if (profile_exists($username_safe, $db_connection)) {
              echo "login ok\n";
              return array(
                'status' => LOGIN_SUCCESS,
                'error_msg' => false,
                'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email,),
                );                    
            } else {
              echo "login ok, create profile\n";
              return array(
                'status' => LOGIN_SUCCESS_CREATE_PROFILE,
                'error_msg' => false,
                'user_row' => array('user_id' => $user_id, 'username' => $username, 'user_email' => $email, 'user_type' => USER_NORMAL, 'group_id' => 1,),
                );                            
            }
          } else {
	    return array(
	      'status' => LOGIN_ERROR_EXTERNAL_AUTH,
	      'error_msg' => 'Authentication failure',
	      'user_row' => array('user_id' => ANONYMOUS),
	      );	    
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
    
?>
