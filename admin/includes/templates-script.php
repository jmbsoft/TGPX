<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
function checkForm()
{
    if( $('#r').val() == 'txScriptTemplateSave' )
    {
        return confirm('Are you sure you want to save your changes?');
    }
    else
    {
        $('#code').val('');
    }

    return true;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/templates-script.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Script Page Templates
    </div>

    <form action="index.php" method="POST" onSubmit="return checkForm()">

    <div class="centered margin-top" style="font-weight: bold">
      <select name="template">
        <?php
        $templates =& DirRead("{$GLOBALS['BASE_DIR']}/templates", '^(?!email).*?(\.tpl$|\.css$)');
        asort($templates);
        echo OptionTags($templates, $_REQUEST['loaded_template'], TRUE);
        ?>
      </select>
      &nbsp;
      <button type="submit" onclick="$('#r').val('txScriptTemplateLoad')">Load Template</button>
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

    <?php if( !empty($GLOBALS['warnstr']) ): ?>
    <div class="warn margin-top">
      <?php echo $GLOBALS['warnstr']; ?>
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
    <button type="submit" onclick="$('#r').val('txScriptTemplateSave')">Save Template</button>

    <br />
    <br />

    <textarea name="code" id="code" rows="50" cols="150" wrap="off"><?PHP echo $_REQUEST['code']; ?></textarea>
    <input type="hidden" name="loaded_template" value="<?PHP echo $_REQUEST['loaded_template']; ?>" />
    </div>
    <?php endif; ?>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
