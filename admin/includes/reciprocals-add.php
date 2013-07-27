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
        <a href="docs/reciprocals.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this reciprocal link by making changes to the information below
      <?php else: ?>
      Add a new reciprocal link by filling out the information below
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
            <label for="identifier">Identifier:</label>
            <input type="text" name="identifier" id="identifier" size="60" value="<?php echo $_REQUEST['identifier']; ?>" />
        </div>

        <div class="fieldgroup">
            <label></label>
            <label for="regex" class="cblabel inline"><?php echo CheckBox('regex', 'checkbox', 1, $_REQUEST['regex']); ?> Regular expression</label>
        </div>

        <div class="fieldgroup">
            <label for="code">Link Code:</label>
            <textarea type="text" name="code" id="code" rows="5" cols="80"><?php echo $_REQUEST['code']; ?></textarea>
        </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Reciprocal Link</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txReciprocalEdit' : 'txReciprocalAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="recip_id" value="<?php echo $_REQUEST['recip_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>

</body>
</html>
