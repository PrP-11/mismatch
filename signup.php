<?php
  // Start the session
  require_once('templates/startsession.php');

  //Insert the page header
  $page_title='Sign Up';
  require_once('templates/header.php');

  // Nav menu
  require_once('templates/navmenu.php');

  require_once('appvars.php');
  require_once('connectvars.php');

  //Connect to the database
  $dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

  if(isset($_POST['submit'])){
    // Verify captcha passphrase
    if(SHA1($_POST['captcha']) == $_SESSION['passphrase']){
      // Grab the sign in data from the post
      $username = mysqli_real_escape_string($dbc, trim($_POST['username']));
      $password = mysqli_real_escape_string($dbc, trim($_POST['password']));
      $verify_password = mysqli_real_escape_string($dbc, trim($_POST['verify_password']));
      if(!empty($username) && !empty($password) && !empty($verify_password) &&
        ($verify_password == $password)){
          // Check if username is available
          $query = "SELECT * FROM " . DB_USER_TABLE . " WHERE username = '$username'";
          $data = mysqli_query($dbc, $query);
          if(mysqli_num_rows($data) == 0){
            //username is available
            $query = "INSERT INTO mismatch_user (username, password, join_date) VALUES ".
              "('$username', SHA('$password'), NOW())";
            mysqli_query($dbc, $query);

            //Confirm success with the user
            echo '<p>You have been successfully registered. You can now log in and '.
              '<a href="editprofile.php">edit your profile</a>.</p>';
            mysqli_close($dbc);
            exit();
          }
          else{
            // username already exists
            echo '<p class="error">This username is not available</p>';
            $username = "";
          }
        }
        else{
          echo '<p class="error">Please enter all fields and make sure to enter same password twice</p>';
        }
    } else{
      echo '<p class="error">Please verify that you are human by entering the captcha code</p>';
    }

  }

  mysqli_close($dbc);
?>

<p>Please enter your username and create a password to signup to Mismatch.</p>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <fieldset>
    <legend>Registration Info</legend>
    <label for="username">Username:</label>
    <input type="text" id="username" name="username"
      value="<?php if(!empty($username)) echo $username; ?>" /><br />
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" /><br />
    <label for="verify_password">Verify password:</label>
    <input type="password" id="verify_password" name="verify_password" /><br /><br />
    <label for="captcha">Captcha:</label>
    <input type="text" id="captcha" name="captcha" /><br />
    <img id="captcha-image" src="captcha.php" alt="captcha verification">
    <label class="reload"><a href="<?php echo $_SERVER['PHP_SELF']; ?>">&#x21BB;</a></label>
  </fieldset>
  <input type="submit" name="submit" value="Sign Up" />
</form>
<?php
 // Insert the footer
 require_once('templates/footer.php');
 ?>
