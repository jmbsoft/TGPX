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
        <a href="docs/partner-fields.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this partner field by making changes to the information below
      <?php else: ?>
      Add a new partner field by filling out the information below
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
            <label for="name">Field Name:</label>
            <input type="text" name="name" id="name" size="20" value="<?php echo $_REQUEST['name']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="label">Label:</label>
            <input type="text" name="label" id="label" size="60" value="<?php echo $_REQUEST['label']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="type">Type:</label>
            <select name="type" id="type">
              <?php echo OptionTags($FIELD_TYPES, $_REQUEST['type']); ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="tag_attributes">Tag Attributes:</label>
            <input type="text" name="tag_attributes" id="tag_attributes" size="50" value="<?php echo $_REQUEST['tag_attributes']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="options">Options:</label>
            <input type="text" name="options" id="options" size="70" value="<?php echo $_REQUEST['options']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="validation">Validation:</label>
            <select name="validation" id="validation">
              <?php echo OptionTags($VALIDATION_TYPES, $_REQUEST['validation']); ?>
            </select>
            &nbsp;
            <input type="text" name="validation_extras" id="validation_extras" size="30" value="<?php echo $_REQUEST['validation_extras']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="validation_message">Validation Error:</label>
            <input type="text" name="validation_message" id="validation_message" size="70" value="<?php echo $_REQUEST['validation_message']; ?>" />
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="on_request" class="cblabel inline"><?php echo CheckBox('on_request', 'checkbox', 1, $_REQUEST['on_request']); ?> Show on partner account request form</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="request_only" class="cblabel inline"><?php echo CheckBox('request_only', 'checkbox', 1, $_REQUEST['request_only']); ?> Use this field only for partner account requests</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="required_request" class="cblabel inline"><?php echo CheckBox('required_request', 'checkbox', 1, $_REQUEST['required_request']); ?> Field is required for partner account request</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="on_edit" class="cblabel inline"><?php echo CheckBox('on_edit', 'checkbox', 1, $_REQUEST['on_edit']); ?> Show on partner account editing form</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="required_edit" class="cblabel inline"><?php echo CheckBox('required_edit', 'checkbox', 1, $_REQUEST['required_edit']); ?> Field is required for partner account editing</label>
        </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Partner Field</button>
    </div>

    <input type="hidden" name="field_id" value="<?php echo $_REQUEST['field_id']; ?>" />
    <input type="hidden" name="r" value="<?php echo ($editing ? 'txPartnerFieldEdit' : 'txPartnerFieldAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <input type="hidden" name="old_name" value="<?php echo $_REQUEST['old_name']; ?>" />
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
