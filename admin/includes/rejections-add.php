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
        <a href="docs/rejections.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this rejection e-mail by making changes to the information below
      <?php else: ?>
      Add a new rejection e-mail by filling out the information below
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
          <legend>General Information</legend>

        <div class="fieldgroup">
            <label for="identifier">Identifier:</label>
            <input type="text" name="identifier" id="identifier" size="40" value="<?php echo $_REQUEST['identifier']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="subject">Subject:</label>
            <input type="text" name="subject" id="subject" size="70" value="<?php echo $_REQUEST['subject']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="plain">Text Body:<br />
            <img src="images/html.png" border="0" width="16" height="16" alt="To HTML" onclick="return textToHtml('#plain', '#html')" style="cursor: pointer; margin-top: 5px;">
            </label>
            <textarea name="plain" id="plain" rows="15" cols="90" wrap="off"><?php echo $_REQUEST['plain']; ?></textarea>
        </div>

        <div class="fieldgroup">
            <label for="html">HTML Body:</label>
            <textarea name="html" id="html" rows="15" cols="90" wrap="off"><?php echo $_REQUEST['html']; ?></textarea>
        </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Rejection E-mail</button>
    </div>

    <input type="hidden" name="email_id" value="<?php echo $_REQUEST['email_id']; ?>" />
    <input type="hidden" name="r" value="<?php echo ($editing ? 'txRejectionTemplateEdit' : 'txRejectionTemplateAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
