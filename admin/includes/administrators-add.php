<?php
if( !defined('TGPX') ) die("Access denied");

$type_options = array(ACCOUNT_ADMINISTRATOR => 'Administrator',
                      ACCOUNT_EDITOR => 'Editor');

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#type').bind('change', function()
                                {
                                    if( this.value == 'administrator' )
                                        $('#privileges').BlindUp(500);
                                    else
                                        $('#privileges').BlindDown(500);
                                });
  });
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/administrators.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this administrator account by making changes to the information below
      <?php else: ?>
      Add a new administrator account by filling out the information below
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
                <label for="username">Username:</label>
                <?php if( $editing ): ?>
                <div style="padding: 3px 0px 0px 0px; margin: 0;"><?php echo $_REQUEST['username']; ?></div>
                <input type="hidden" name="username" value="<?php echo $_REQUEST['username']; ?>" />
                <?php else: ?>
                <input type="text" name="username" id="username" size="20" value="<?php echo $_REQUEST['username']; ?>" />
                <?php endif; ?>
            </div>

            <div class="fieldgroup">
                <label for="password">Password:</label>
                <input type="text" name="password" id="password" size="20" value="<?php echo $_REQUEST['password']; ?>" />
                <?php if( $editing ): ?>
                <br /> Leave blank unless you want to change this account's password
                <?php endif; ?>
            </div>

            <div class="fieldgroup">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" size="30" value="<?php echo $_REQUEST['name']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="email">E-mail Address:</label>
                <input type="text" name="email" id="email" size="40" value="<?php echo $_REQUEST['email']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="type">Account Type:</label>
                <select name="type" id="type">
                  <?php echo OptionTags($type_options, $_REQUEST['type']); ?>
                </select>
            </div>
        </fieldset>

        <div id="privileges" style="width: 100%<?php if( $_REQUEST['type'] != ACCOUNT_EDITOR ) echo "; display: none;"; ?>">
        <fieldset>
          <legend>Privileges</legend>

          <div class="fieldgroup">
            <label class="lesspad">Categories:</label>
            <label for="p_cat_a" class="cblabel inline">
            <?php echo CheckBox('p_cat_a', 'checkbox', P_CATEGORY_ADD, $_REQUEST['p_cat_a'], $_REQUEST['rights']); ?> Add/Approve &nbsp;</label>
            <label for="p_cat_m" class="cblabel inline">
            <?php echo CheckBox('p_cat_m', 'checkbox', P_CATEGORY_MODIFY, $_REQUEST['p_cat_m'], $_REQUEST['rights']); ?> Modify &nbsp;</label>
            <label for="p_cat_r" class="cblabel inline">
            <?php echo CheckBox('p_cat_r', 'checkbox', P_CATEGORY_REMOVE, $_REQUEST['p_cat_r'], $_REQUEST['rights']); ?> Remove &nbsp;</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad">Partners:</label>
            <label for="p_partner_a" class="cblabel inline">
            <?php echo CheckBox('p_partner_a', 'checkbox', P_PARTNER_ADD, $_REQUEST['p_partner_a'], $_REQUEST['rights']); ?> Add/Approve &nbsp;</label>
            <label for="p_partner_m" class="cblabel inline">
            <?php echo CheckBox('p_partner_m', 'checkbox', P_PARTNER_MODIFY, $_REQUEST['p_partner_m'], $_REQUEST['rights']); ?> Modify &nbsp;</label>
            <label for="p_partner_r" class="cblabel inline">
            <?php echo CheckBox('p_partner_r', 'checkbox', P_PARTNER_REMOVE, $_REQUEST['p_partner_r'], $_REQUEST['rights']); ?> Remove &nbsp;</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad">Galleries:</label>
            <label for="p_gallery_a" class="cblabel inline">
            <?php echo CheckBox('p_gallery_a', 'checkbox', P_GALLERY_ADD, $_REQUEST['p_gallery_a'], $_REQUEST['rights']); ?> Add/Approve &nbsp;</label>
            <label for="p_gallery_m" class="cblabel inline">
            <?php echo CheckBox('p_gallery_m', 'checkbox', P_GALLERY_MODIFY, $_REQUEST['p_gallery_m'], $_REQUEST['rights']); ?> Modify &nbsp;</label>
            <label for="p_gallery_r" class="cblabel inline">
            <?php echo CheckBox('p_gallery_r', 'checkbox', P_GALLERY_REMOVE, $_REQUEST['p_gallery_r'], $_REQUEST['rights']); ?> Remove &nbsp;</label>
          </div>
        </fieldset>
        </div>


        <fieldset>
          <legend>E-mail Settings</legend>

            <div class="fieldgroup">
              <label></label>
              <label for="e_scanner_complete" class="cblabel inline">
              <?php echo CheckBox('e_scanner_complete', 'checkbox', E_SCANNER_COMPLETE, $_REQUEST['e_scanner_complete'], $_REQUEST['notifications']); ?> Send e-mail when gallery scanner finishes</label>
            </div>

            <div class="fieldgroup">
              <label></label>
              <label for="e_cheat_report" class="cblabel inline">
              <?php echo CheckBox('e_cheat_report', 'checkbox', E_CHEAT_REPORT, $_REQUEST['e_cheat_report'], $_REQUEST['notifications']); ?>
              Send e-mail when there are</label> <input type="text" name="reports_waiting" id="reports_waiting" size="5" value="<?php echo $_REQUEST['reports_waiting']; ?>" /> cheat reports waiting to be processed
            </div>

            <div class="fieldgroup">
              <label></label>
              <label for="e_partner_request" class="cblabel inline">
              <?php echo CheckBox('e_partner_request', 'checkbox', E_PARTNER_REQUEST, $_REQUEST['e_partner_request'], $_REQUEST['notifications']); ?>
              Send e-mail when there are</label> <input type="text" name="requests_waiting" id="requests_waiting" size="5" value="<?php echo $_REQUEST['requests_waiting']; ?>" /> partner account requests waiting to be processed
            </div>
        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Account</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txAdministratorEdit' : 'txAdministratorAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
