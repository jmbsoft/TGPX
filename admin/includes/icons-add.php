<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<script language="JavaScript">
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/icons.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this icon by making changes to the information below
      <?php else: ?>
      Add a new icon by filling out the information below
      <?php endif; ?>
    </div>

        <?php if( $GLOBALS['message'] ): ?>
        <div class="notice margin-bottom">
          <?php echo $GLOBALS['message']; ?>
        </div>
        <?php endif; ?>

        <?php if( $GLOBALS['errstr'] ): ?>
        <div class="alert margin-bottom">
          <?php echo $GLOBALS['errstr']; ?>
        </div>
        <?php endif; ?>

        <fieldset>
          <legend>General Settings</legend>

            <div class="fieldgroup">
              <label for="value">Identifier:</label>
              <input type="text" name="identifier" id="identifier" size="60" value="<?php echo $_REQUEST['identifier']; ?>" />
            </div>

            <div class="fieldgroup">
              <label for="icon_html">Icon HTML:</label>
              <textarea name="icon_html" id="icon_html" rows="7" cols="90" wrap="off"><?php echo $_REQUEST['icon_html']; ?></textarea>
            </div>
        </fieldset>


    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Icon</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txIconEdit' : 'txIconAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="icon_id" value="<?php echo $_REQUEST['icon_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
