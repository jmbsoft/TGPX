<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/language.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Edit Language File
    </div>

    <?php if( !is_writable("{$GLOBALS['BASE_DIR']}/includes/language.php") ): ?>
    <div class="alert margin-top">
      The language file <?php echo "{$GLOBALS['BASE_DIR']}/includes/language.php"; ?> is not writeable and needs to have it's permissions changed to 666.
    </div>
    <?php endif; ?>

    <?php if( isset($GLOBALS['message']) ): ?>
    <div class="notice margin-top">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>

    <form action="index.php" method="POST">

    <div class="centered margin-top" style="font-weight: bold">
      <button type="submit">Save Language File</button>
    </div>

    <br />

    <?php foreach( $L as $key => $value ): ?>
    <div class="fieldgroup">
      <label for="<?php echo $key; ?>" style="width: 225px;"><?php echo $key; ?>:</label>
      <input type="text" name="<?php echo $key; ?>" id="<?php echo $key; ?>" size="120" value="<?php echo htmlspecialchars($value); ?>" />
    </div>
    <?php endforeach; ?>

    <input type="hidden" id="r" name="r" value="txLanguageFileSave">

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
