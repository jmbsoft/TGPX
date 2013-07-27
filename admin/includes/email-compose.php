<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<?php if( $_REQUEST['which'] ): ?>
<script language="JavaScript">
$(function()
  {
      $('#to').html(window.parent.$('#which [@selected]').text());
      $('#search_form').val(window.parent.$('#search').formSerialize());
      $('#results').val(window.parent.$('#results').formSerialize());
  });
</script>
<?php endif; ?>

<div style="padding: 10px;">
    <div class="margin-bottom">
      Send an e-mail message to the selected recipients by filling out the information below
    </div>

    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-bottom">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>

  <form action="index.php" method="POST" id="form" onsubmit="return confirm('Are you sure you want to send this message?')">
    <fieldset>
      <legend>E-mail Message</legend>

      <div class="fieldgroup">
        <label>To:</label>
        <?php if( $_REQUEST['which'] ): ?>
        <div style="padding: 3px 0px 0px 0px; margin: 0;" id="to"></div>
        <?php else: ?>
        <div style="padding: 3px 0px 0px 0px; margin: 0;"><?php echo $_REQUEST['to']; ?></div>
        <input type="hidden" name="to" value="<?php echo $_REQUEST['to_list']; ?>" />
        <?php endif; ?>
      </div>

      <div class="fieldgroup">
        <label for="subject">Subject:</label>
        <input type="text" name="subject" id="subject" size="60" value="<?PHP echo $_REQUEST['subject']; ?>" />
      </div>

      <div class="fieldgroup">
        <label for="plain">Text Body:<br />
        <img src="images/html.png" border="0" width="16" height="16" alt="To HTML" onclick="textToHtml('#plain', '#html')" style="cursor: pointer; margin-top: 5px;">
        </label>
        <textarea name="plain" id="plain" rows="15" cols="100" wrap="off"><?php echo $_REQUEST['plain']; ?></textarea>
    </div>

    <div class="fieldgroup">
        <label for="html">HTML Body:</label>
        <textarea name="html" id="html" rows="15" cols="100" wrap="off"><?php echo $_REQUEST['html']; ?></textarea>
    </div>

    </fieldset>

    <div class="centered margin-top"><button type="submit">Send</button></div>
    <input type="hidden" name="r" value="<?PHP echo $function; ?>">
    <input type="hidden" name="search_form" id="search_form" value="">
    <input type="hidden" name="results" id="results" value="">
  </form>
</div>


</body>
</html>