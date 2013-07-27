<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
function checkForm()
{
    if( $('#r').val() == 'txEmailTemplateLoad' )
    {
        $('#subject').val('');
        $('#plain').val('');
        $('#html').val('');

        return true;
    }
    else
    {
        return confirm('Are you sure you want to save your changes?');
    }
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/templates-email.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      E-mail Templates
    </div>

    <form action="index.php" method="POST" onSubmit="return checkForm()">

    <div class="centered margin-top" style="font-weight: bold">
      <select name="template">
        <?php
        $templates =& DirRead("{$GLOBALS['BASE_DIR']}/templates", '^email[^\.]+\.tpl$');
        asort($templates);
        echo OptionTags($templates, $_REQUEST['loaded_template'], TRUE);
        ?>
      </select>
      &nbsp;
      <button type="submit" onclick="$('#r').val('txEmailTemplateLoad')">Load Template</button>
    </div>

    <input type="hidden" id="r" name="r" value="">

    <?php if( $_REQUEST['loaded_template'] ): ?>
    <br />

    <div class="heading">
      Editing Template <?php echo $_REQUEST['loaded_template']; ?>
    </div>

    <?php if( !is_writable("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['loaded_template']}") ): ?>
    <div class="alert margin-top">
      The template file <?php echo $_REQUEST['loaded_template']; ?> is not writeable and needs to have it's permissions changed to 666.
    </div>
    <?php endif; ?>

    <?php if( isset($GLOBALS['errstr']) ): ?>
    <div class="alert margin-top">
      <?php echo $GLOBALS['errstr']; ?>
    </div>
    <?php endif; ?>

    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-top">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>

    <div class="centered margin-top" style="font-weight: bold">
    <button type="submit" onclick="$('#r').val('txEmailTemplateSave')">Save Template</button>
    <input type="hidden" name="loaded_template" value="<?PHP echo $_REQUEST['loaded_template']; ?>" />
    </div>

    <br />

    <div class="fieldgroup">
      <label for="subject">Subject:</label>
      <input type="text" name="subject" id="subject" size="110" value="<?php echo $_REQUEST['subject']; ?>" />
    </div>

    <div class="fieldgroup">
      <label for="plain">Text Body:<br />
      <img src="images/html.png" border="0" width="16" height="16" alt="To HTML" onclick="return textToHtml('#plain', '#html')" style="cursor: pointer; margin-top: 5px;">
      </label>
      <textarea name="plain" id="plain" rows="15" cols="130" wrap="off"><?php echo $_REQUEST['plain']; ?></textarea>
    </div>

    <div class="fieldgroup">
      <label for="html">HTML Body:</label>
      <textarea name="html" id="html" rows="15" cols="130" wrap="off"><?php echo $_REQUEST['html']; ?></textarea>
    </div>

    <?php endif; ?>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
