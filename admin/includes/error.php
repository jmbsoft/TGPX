<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<div id="main-content">
  <div id="centered-content" style="padding-left: 20px; padding-right: 20px;">
    <div class="heading">Error Encountered</div>
    <div class="alert margin-top">
      <?php echo $error; ?>
    </div>
  </div>
</div>

</body>
</html>