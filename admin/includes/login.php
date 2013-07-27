<?php
if( !defined('TGPX') ) die("Access denied");

require_once("includes/header.php");
?>

<script language="JavaScript">
// Don't display login screen in a frame
if( window.top.document != document )
{
    window.top.document.location = 'index.php';
}
</script>

<div id="main-content">
  <div id="centered-content" style="width: 350px;">

    <form action="index.php" method="POST">
      <div class="heading">TGPX Login</div>

      <div class="margin-bottom margin-top">
        Please enter your administrative login information
      </div>

      <?php if( $error ): ?>
      <div class="alert margin-bottom">
        <?php echo $error; ?>
      </div>
      <?php endif; ?>

      <div class="fieldgroup">
        <label for="login_username">Username:</label>
        <input type="text" name="login_username" id="login_username" size="20" />
      </div>

      <div class="fieldgroup">
        <label for="login_password">Password:</label>
        <input type="password" name="login_password" id="login_password" size="20" />
      </div>

      <div class="fieldgroup">
        <label for=""></label>
        <button type="submit">Log In</button>
      </div>

    <?php if( $_SERVER['QUERY_STRING'] ): ?>
    <input type="hidden" name="ref_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <?php endif; ?>
    </form>
  </div>
</div>


</body>
</html>
