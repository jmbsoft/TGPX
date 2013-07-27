<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function() { });
</script>

<div style="padding: 10px;">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/partners.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Process inactive partner accounts that have not submitted galleries recently
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

        <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to do this?')">

        <fieldset style="text-align: center;">
          <legend>E-mail Inactive Accounts</legend>

                E-mail accounts that have been inactive for <input type="text" name="inactive" value="30" size="3"> or more days<br />
                The contents of the email-partner-inactive.tpl template will be sent to each account

                <br />
                <br />

                <button type="submit">E-mail Accounts</button>

        </fieldset>

        <input type="hidden" name="r" value="txPartnerMailInactive">
        </form>



        <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to do this?')">

        <fieldset style="text-align: center;">
          <legend>Delete Inactive Accounts</legend>

            Delete accounts that have been inactive for <input type="text" name="inactive" value="30" size="3"> or more days

            <br />
            <br />

            <button type="submit">Delete Accounts</button>

        </fieldset>

        <input type="hidden" name="r" value="txPartnerDeleteInactive">
        </form>
</div>



</body>
</html>
