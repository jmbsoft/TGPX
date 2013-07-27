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
        <a href="docs/sponsors.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this sponsor by making changes to the information below
      <?php else: ?>
      Add a new sponsor by filling out the information below
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
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" size="60" value="<?php echo $_REQUEST['name']; ?>" />
                <?php else: ?>
                <label for="value">Name(s):</label>
                <textarea type="text" name="name" id="name" rows="5" cols="80" wrap="off"><?php echo $_REQUEST['name']; ?></textarea>
                <?php endif; ?>
            </div>

            <div class="fieldgroup">
                <label for="url">URL:</label>
                <input type="text" name="url" id="url" size="80" value="<?php echo $_REQUEST['url']; ?>" />
            </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Sponsor</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txSponsorEdit' : 'txSponsorAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="sponsor_id" value="<?php echo $_REQUEST['sponsor_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
