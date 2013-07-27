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
        <a href="docs/blacklist.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this blacklist item by making changes to the information below
      <?php else: ?>
      Add a new blacklist item by filling out the information below
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
            <?php if( $editing ): ?>
            <label for="value">Value:</label>
            <input type="text" name="value" id="value" size="60" value="<?php echo $_REQUEST['value']; ?>" />
            <?php else: ?>
            <label for="value">Value(s):</label>
            <textarea type="text" name="value" id="value" rows="5" cols="80"><?php echo $_REQUEST['value']; ?></textarea>
            <?php endif; ?>
        </div>

        <div class="fieldgroup">
            <label></label>
            <label for="regex" class="cblabel inline"><?php echo CheckBox('regex', 'checkbox', 1, $_REQUEST['regex']); ?> Regular expression</label>
        </div>

        <div class="fieldgroup">
            <label for="type">Type:</label>
            <select name="type" id="type">
              <?php echo OptionTags($BLIST_TYPES, $_REQUEST['type']); ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="reason">Reason:</label>
            <input type="text" name="reason" id="reason" size="60" value="<?php echo $_REQUEST['reason']; ?>" />
        </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Blacklist Item</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txBlacklistEdit' : 'txBlacklistAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="blacklist_id" value="<?php echo $_REQUEST['blacklist_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
