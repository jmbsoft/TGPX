<?php
if( !defined('TGPX') ) die("Access denied");

if( $_REQUEST['gallery_id'] )
{
    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));

    $parsed_url = parse_url($gallery['gallery_url']);
    $_REQUEST['value'] = $parsed_url['host'];
    $_REQUEST['type'] = 'url';
    $_REQUEST['allow_redirect'] = 1;
    $_REQUEST['allow_norecip'] = 1;
    $_REQUEST['allow_autoapprove'] = 1;
    $_REQUEST['allow_noconfirm'] = 1;
}

include_once('includes/header.php');
?>

<script language="JavaScript">
<?PHP if( $GLOBALS['added'] && !$GLOBALS['from_search'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/whitelist.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this whitelist item by making changes to the information below
      <?php else: ?>
      Add a new whitelist item by filling out the information below
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
              <?php echo OptionTags($WLIST_TYPES, $_REQUEST['type']); ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="reason">Reason:</label>
            <input type="text" name="reason" id="reason" size="60" value="<?php echo $_REQUEST['reason']; ?>" />
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_redirect" class="cblabel inline"><?php echo CheckBox('allow_redirect', 'checkbox', 1, $_REQUEST['allow_redirect']); ?> Allow URL redirection</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_norecip" class="cblabel inline"><?php echo CheckBox('allow_norecip', 'checkbox', 1, $_REQUEST['allow_norecip']); ?> No reciprocal link required</label><br />
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_autoapprove" class="cblabel inline"><?php echo CheckBox('allow_autoapprove', 'checkbox', 1, $_REQUEST['allow_autoapprove']); ?> Auto-approve galleries</label><br />
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_noconfirm" class="cblabel inline"><?php echo CheckBox('allow_noconfirm', 'checkbox', 1, $_REQUEST['allow_noconfirm']); ?> No confirmation e-mail required</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_blacklist" class="cblabel inline"><?php echo CheckBox('allow_blacklist', 'checkbox', 1, $_REQUEST['allow_blacklist']); ?> Allow blacklisted items</label>
        </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Whitelist Item</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txWhitelistEdit' : 'txWhitelistAdd'); ?>">

    <?php if( $_REQUEST['gallery_id'] ): ?>
    <input type="hidden" name="from_search" value="1">
    <?php endif; ?>

    <?php if( $editing ): ?>
    <input type="hidden" name="whitelist_id" value="<?php echo $_REQUEST['whitelist_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
