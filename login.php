<?php
  // Start the session
  require_once('templates/startsession.php');

  require_once('connectvars.php');

  // Clear the error message
  $error_msg = "";

  // If the user isn't logged in, try to log them in
  if(!isset($_SESSION['user_id'])){
    if(isset($_POST['submit'])){
      // Connect to the db
      $dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

      // Grab the login data
      $user_username = mysqli_real_escape_string($dbc, trim($_POST['username']));
      $user_password = mysqli_real_escape_string($dbc, trim($_POST['password']));

      if(!empty($user_username) && !empty($user_password)){
        // Look for data in the database
        $query = "SELECT user_id, username FROM mismatch_user WHERE username='$user_username' AND password=SHA('$user_password')";
        $data = mysqli_query($dbc, $query);

        if(mysqli_num_rows($data) == 1){
          // login is verified
          $row = mysqli_fetch_array($data);
          $_SESSION['user_id'] = $row['user_id'];
          $_SESSION['username'] = $row['username'];
          setcookie('user_id', $row['user_id'], time() + (60*60*24*30)); //expires in 30 days
          setcookie('username', $row['username'], time() + (60*60*24*30)); //expires in 30 days
          $home_url = 'http://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']).'/index.php';
          header('Location: ', $home_url);
        }
        else{
          // incorrect credentials
          $error_msg = "You must enter a correct username and password";
        }
      }
      else{
        // enter both username and password
        $error_msg = "ENter both username and password";
      }
    }
  }
  //Insert the page header
  $page_title='Log In';
  require_once('templates/header.php');

  // Nav menu
  require_once('templates/navmenu.php');
?>
    <?php
      // If the cookie is empty, show any error message and the login form; otherise confirm the login
      if(empty($_SESSION['user_id'])){
        echo '<p class="error">' . $error_msg . '</p>';
    ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
          <fieldset>
            <legend>Log In</legend>
            <label for="username">Username: </label>
            <input type="text" id="username" name="username" value="<?php if(!empty($user_username)) echo $user_username; ?>" /><br />
            <label for="password">Password: </label>
            <input type="password" id="password" name="password" />
          </fieldset>
          <input type="submit" name="submit" value="Log In" />
        </form>
    <?php
      }
      else{
        // COnfirm the login
        echo('<p class="login">You are logged in as '. $_SESSION['username'] .'.</p>');

          // Redirect to the home page
          $home_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/index.php';
          header('Location: ' . $home_url);
      }
    ?>
<?php
 // Insert the footer
 require_once('templates/footer.php');
 ?>
