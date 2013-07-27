<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>
<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form" enctype="multipart/form-data">
    <div class="margin-bottom">
      Upload a preview thumbnail for this gallery
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
            <label for="type">Select Image:</label>
            <input type="file" name="upload" id="upload" size="40">
        </div>

        <div class="fieldgroup">
            <label for="type">Resize Image:</label>
            <select name="action" id="action">
            <?php
            $actions = array('' => 'NO',
                             'auto' => 'Automatically',
                             'manual' => 'Manually');

            echo OptionTags($actions, $_REQUEST['action']);
            ?>
            </select>
        </div>
        </fieldset>

    <div class="centered margin-top">
      <button type="submit">Upload Preview Thumb</button>
    </div>

    <input type="hidden" name="r" value="txPreviewUpload">
    <input type="hidden" name="gallery_id" value="<?php echo $_REQUEST['gallery_id']; ?>">
    </form>
</div>



</body>
</html>
