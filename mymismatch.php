<?php
  // Start the session
  require_once('templates/startsession.php');

  //Insert the page header
  $page_title='My Mismatch';
  require_once('templates/header.php');

  require_once('appvars.php');
  require_once('connectvars.php');

  // Make sure the user is logged in
  if(!isset($_SESSION['user_id'])){
    echo '<p class="login">Please <a href="login.php">log in</a> to access this page.</p>';
    exit();
  }

  // Nav menu
  require_once('templates/navmenu.php');

  // Connect to the database
  $dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

  // Make sure the user has filled the questionnaire form
  $query = "SELECT * FROM mismatch_response WHERE user_id='" .
    $_SESSION['user_id'] . "'";
  $data = mysqli_query($dbc, $query);
  if(mysqli_num_rows($data) != 0){
    // store user's response in an array
    $query = "SELECT mr.response_id, mr.topic_id, mr.response, mt.name AS topic_name, mc.name AS category_name " .
      "FROM mismatch_response AS mr " .
      "INNER JOIN mismatch_topic AS mt USING (topic_id) " .
      "INNER JOIN mismatch_category AS mc USING (category_id)" .
      "WHERE mr.user_id = '" . $_SESSION['user_id'] . "'";
    $data = mysqli_query($dbc, $query);
    $user_responses = array();
    while ($row = mysqli_fetch_array($data)) {
      array_push($user_responses, $row);
    }

    $mismatch_score = 0;
    $mismatch_user_id = -1;
    $mismatch_topics = array();

    // Get user's gender preference
    $query = "SELECT gender_pref FROM mismatch_user WHERE user_id='" . $_SESSION['user_id'] . "'";
    $data = mysqli_query($dbc, $query);
    $row = mysqli_fetch_array($data);
    $gender = $row['gender_pref'];

    // Loop through the user table comparing other people's response
    if(!empty($gender)){
      $query = "SELECT user_id FROM mismatch_user WHERE user_id != '" .
        $_SESSION['user_id'] . "' AND gender = '$gender'";
    } else {
      $query = "SELECT user_id FROM mismatch_user WHERE user_id != '" .
        $_SESSION['user_id'] . "'";
    }

    $data = mysqli_query($dbc, $query);
    while($row = mysqli_fetch_array($data)){
      // Grab the reponse data from the current user
      $query2 = "SELECT response_id, topic_id, response FROM mismatch_response WHERE user_id = '" . $row['user_id'] . "'";
      $data2 = mysqli_query($dbc, $query2);
      $mismatch_responses = array();
      while($row2 = mysqli_fetch_array($data2)){
        array_push($mismatch_responses, $row2);
      }

      // compare responses and calculate total mismatch
      $score = 0;
      $topics = array();
      $categories = array();
      for($i=0;$i<count($user_responses); $i++){
        if($user_responses[$i]['response'] + $mismatch_responses[$i]['response'] == 3){
          $score += 1;
          array_push($topics, $user_responses[$i]['topic_name']);
          array_push($categories, $user_responses[$i]['category_name']);
        }
      }

      // Check if the current user is better than previous mismatch
      if($score > $mismatch_score){
        // update the mismatch since current user has better score
        $mismatch_score = $score;
        $mismatch_user_id = $row['user_id'];
        $mismatch_topics = array_slice($topics, 0);
        $mismatch_categories = array_slice($categories, 0);
      }
    }

    // Create the categories 2D array
    $category_stats = array(array($mismatch_categories[0], 0));
    foreach ($mismatch_categories as $category) {
      if($category_stats[count($category_stats)-1][0] != $category){
        array_push($category_stats, array($category, 1));
      }
      else{
        ++$category_stats[count($category_stats)-1][1];
      }
    }

    // Function to generate bar graph
    function draw_bar_graph($width, $height, $data, $max_value, $filename){
      // Create an empty image
      $img = imagecreatetruecolor($width, $height);

      // Set graphics color
      $bg_color = imagecolorallocate($img, 255, 255, 255);// white
      $text_color = imagecolorallocate($img, 255, 255, 255);// white
      $bar_color = imagecolorallocate($img, 0, 0, 0); //black
      $border_color = imagecolorallocate($img, 192, 192, 192); //light grey

      //Fill the background
      imagefilledrectangle($img, 0, 0, $width, $height, $bg_color);

      // Draw the bars
      $bar_width = $width/((count($data)*2)+1);
      for($i=0; $i<count($data); $i++){
        imagefilledrectangle($img, $bar_width*(2*$i + 1), $height,
          ($bar_width*2)*($i+1), $height - (($height / $max_value)*$data[$i][1]), $bar_color);
        imagestringup($img, 5, $bar_width*(2*$i + 1), $height - 5, $data[$i][0], $text_color);
      }

      // Draw borders
      imagerectangle($img, 0, 0, $width - 1, $height - 1, $border_color);

      // Draw the range
      for($i=0; $i<=$max_value; $i++){
        imagestring($img, 5, 0, $height - ($i*($height/$max_value)), $i, $bar_color);
      }

      // Write the graph image to a file
      imagepng($img, $filename, 5);// compression level medium
      imagedestroy($img);
    }
    // Check if we found a mismatch
    if($mismatch_user_id!=-1){
      $query = "SELECT username, first_name, last_name, city, state, picture FROM mismatch_user WHERE user_id = '$mismatch_user_id'";
      $data = mysqli_query($dbc, $query);
      if(mysqli_num_rows($data) == 1){
        // display the user data
        $row = mysqli_fetch_array($data);
        echo '<table><tr><td class="label">';
        if(!empty($row['first_name']) && !empty($row['last_name'])){
          echo $row['first_name'] . ' ' . $row['last_name'] . '<br />';
        }
        if(!empty($row['city']) && !empty($row['state'])){
          echo $row['city'] . ', ' . $row['state'] . '<br />';
        }
        echo '</td><td>';
        if (!empty($row['picture'])) {
          echo '<img src="' . MM_UPLOADPATH . $row['picture'] . '" alt="Profile Picture" /><br />';
        }
        echo '</td></tr></table>';

        // Display the mismatched topics
        echo '<h4>You are mismatched on the following ' . count($mismatch_topics) . ' topics:</h4>';
        foreach($mismatch_topics as $topic){
          echo $topic . '<br />';
        }

        // Display category stats as a bar graph
        echo '<h4>Mismatched category stats:</h4>';
        draw_bar_graph(480, 240, $category_stats, 5, MM_UPLOADPATH . $_SESSION['user_id'] . '-mymismatchgraph.png');
        echo '<img src="' . MM_UPLOADPATH . $_SESSION['user_id'] . '-mymismatchgraph.png" alt="Mismatch category graph" /><br />';

        // Provide a link to the mismatch user's profile
        echo '<h4>View <a href=viewprofile.php?user_id=' . $mismatch_user_id . '>'
          . $row['first_name'] . '\'s profile</a>.</h4>';
      }
    } else{
      echo '<p>No mismatch found.</p>';
    }

  } else {
    echo '<p>You must first <a href="questionnaire.php">answer the questionnaire</a> before you can be mismatched.</p>';
  }

  mysqli_close($dbc);
?>

<?php
  // Insert the footer
  require_once('templates/footer.php');
 ?>
