<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<div id="main-content">
  <div id="centered-content" style="padding-left: 20px; padding-right: 20px;">
    <div class="alert margin-top">
      RESTRICTED ACCESS: The IP address you are connecting from (<?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?>) is not allowed to access this function
    </div>
  </div>
</div>

</body>
</html>