<?php
if( !defined('TGPX') ) die("Access denied");

$types = array('text' => 'Text', 'image' => 'Image');

include_once('includes/header.php');
?>

<script language="JavaScript">
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>

$(function()
  {
      $('#type').bind('change', function()
                                {
                                    if( $(this).val() == 'text' )
                                    {
                                        $('#image_settings').hide();
                                        $('#text_settings').show();
                                    }
                                    else
                                    {
                                        $('#text_settings').hide();
                                        $('#image_settings').show();
                                    }
                                });

      $('#use_category').bind('click', function()
                                       {
                                           if( this.checked )
                                           {
                                               $('#string').attr({readonly: true}).toggleClass('readonly');
                                           }
                                           else
                                           {
                                               $('#string').attr({readonly: false}).toggleClass('readonly');
                                           }
                                       });
  });
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/annotations.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this annotation by making changes to the information below
      <?php else: ?>
      Add a new annotation by filling out the information below
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
                <label for="value">Identifier:</label>
                <input type="text" name="identifier" id="identifier" size="60" value="<?php echo $_REQUEST['identifier']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="value">Location:</label>
                <select name="location">
                  <?php echo OptionTags($ANN_LOCATIONS, $_REQUEST['location']); ?>
                </select>
            </div>

            <div class="fieldgroup">
                <label for="type">Type:</label>
                <select name="type" id="type">
                  <?php echo OptionTags($types, $_REQUEST['type']); ?>
                </select>
            </div>
        </fieldset>


        <div id="text_settings"<?php if( isset($_REQUEST['type']) && $_REQUEST['type'] != 'text' ) echo ' style="display: none;"'; ?>>
        <fieldset>
          <legend>Text Annotation</legend>

            <div class="fieldgroup">
                <label for="string">String:</label>
                <input type="text" name="string" id="string" size="40" value="<?php echo $_REQUEST['string']; ?>" <?php if( $_REQUEST['use_category'] ) echo ' class="readonly" readonly="readonly"'; ?>  />
                <label for="use_category" class="cblabel inline"><?php echo CheckBox('use_category', 'checkbox', 1, $_REQUEST['use_category']); ?> Use category name</label>
            </div>

            <div class="fieldgroup">
                <label for="font_file">Font File:</label>
                <input type="text" name="font_file" id="font_file" size="20" value="<?php echo $_REQUEST['font_file']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="text_size">Text Size:</label>
                <input type="text" name="text_size" id="text_size" size="10" value="<?php echo $_REQUEST['text_size']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="text_color">Text Color:</label>
                <input type="text" name="text_color" id="text_color" size="10" value="<?php echo $_REQUEST['text_color']; ?>" />
            </div>

            <div class="fieldgroup">
                <label for="shadow_color">Shadow Color:</label>
                <input type="text" name="shadow_color" id="shadow_color" size="10" value="<?php echo $_REQUEST['shadow_color']; ?>" />
            </div>
        </fieldset>
        </div>

        <div id="image_settings"<?php if($_REQUEST['type'] != 'image' ) echo ' style="display: none;"'; ?>>
        <fieldset>
          <legend>Image Annotation</legend>

            <div class="fieldgroup">
                <label for="image_file">Image File:</label>
                <input type="text" name="image_file" id="image_file" size="40" value="<?php echo $_REQUEST['image_file']; ?>" />
            </div>

            <!--<div class="fieldgroup">
                <label for="transparency">Transparent Color:</label>
                <input type="text" name="transparency" id="transparency" size="10" value="<?php echo $_REQUEST['transparency']; ?>" />
            </div>-->

        </fieldset>
        </div>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Annotation</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txAnnotationEdit' : 'txAnnotationAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="annotation_id" value="<?php echo $_REQUEST['annotation_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
