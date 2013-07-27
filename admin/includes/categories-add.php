<?php
if( !defined('TGPX') ) die("Access denied");
               
include_once('includes/header.php');
?>



<script language="JavaScript">
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>

$(function() 
  {
      $('#apply_all').bind('click', function() { if( this.checked ) { $('#apply_matched').attr({checked: false}); } } );
      $('#apply_matched').bind('click', function() { if( this.checked ) { $('#apply_all').attr({checked: false}); } } );
      
      $('#form').bind('submit', function() 
                                {
                                    if( $('#apply_matched').attr('checked') )
                                    {
                                        $('#apply_matched').val(window.parent.$('#search').formSerialize());
                                    }
                                });
  });
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/categories.html<?php if( $editing_default ) echo "#default"; ?>" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this category by making changes to the information below
      <?php elseif( $editing_default ): ?>
      Update the default category settings by making changes to the information below
      <?php else: ?>
      Add a new category by filling out the information below
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
 
            <?php if( !$editing_default ): ?>
            <div class="fieldgroup">
                <?php if( $editing ): ?>
                <label for="name">Category Name:</label>
                <input type="text" name="name" id="name" size="60" value="<?php echo $_REQUEST['name']; ?>" />
                <?php else: ?>
                <label for="name">Category Name(s):</label>
                <textarea name="name" id="name" rows="3" cols="80" wrap="off"><?php echo $_REQUEST['name']; ?></textarea>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="fieldgroup">
                <label for="meta_description">Meta Description:</label>
                <input type="text" name="meta_description" id="meta_description" size="80" value="<?php echo $_REQUEST['meta_description']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="meta_keywords">Meta Keywords:</label>
                <input type="text" name="meta_keywords" id="meta_keywords" size="80" value="<?php echo $_REQUEST['meta_keywords']; ?>" />
            </div>            
            
            <div class="fieldgroup">
                <label for="per_day">Submissions Per Day:</label>
                <input type="text" name="per_day" id="per_day" size="10" value="<?php echo $_REQUEST['per_day']; ?>" /> <span style="padding-left: 5px;">Use -1 for no limit</span>
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <label for="hidden" class="cblabel inline"><?php echo CheckBox('hidden', 'checkbox', 1, $_REQUEST['hidden']); ?> Make this category hidden</label>
            </div>
        </fieldset>
        
        
        <fieldset>
          <legend>Picture Gallery Settings</legend>
            
            <div class="fieldgroup">
                <label></label>
                <label for="pics_allowed" class="cblabel inline"><?php echo CheckBox('pics_allowed', 'checkbox', 1, $_REQUEST['pics_allowed']); ?> Allow picture galleries in this category</label>
            </div>
            
            <div class="fieldgroup">
                <label for="pics_extensions">File Extensions:</label>
                <input type="text" name="pics_extensions" id="pics_extensions" size="40" value="<?php echo $_REQUEST['pics_extensions']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="pics_minimum">Minimum Thumbs:</label>
                <input type="text" name="pics_minimum" id="pics_minimum" size="10" value="<?php echo $_REQUEST['pics_minimum']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="pics_maximum">Maximum Thumbs:</label>
                <input type="text" name="pics_maximum" id="pics_maximum" size="10" value="<?php echo $_REQUEST['pics_maximum']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="pics_file_size">Minimum Filesize:</label>
                <input type="text" name="pics_file_size" id="pics_file_size" size="10" value="<?php echo $_REQUEST['pics_file_size']; ?>" />
            </div>           
            
            <div class="fieldgroup">
                <label></label>
                <label for="pics_preview_allowed" class="cblabel inline"><?php echo CheckBox('pics_preview_allowed', 'checkbox', 1, $_REQUEST['pics_preview_allowed']); ?> Allow preview thumbnails for this format</label>
            </div>          
            
            <div class="fieldgroup">
                <label for="pics_preview_size">Preview Dimensions:</label>                
                <select name="pics_preview_size">
                  <option value="custom">Custom --&gt;</option>
                  <?php echo OptionTags($sizes, $_REQUEST['pics_preview_size'], TRUE); ?>
                </select>
                <input type="text" name="pics_preview_size_custom" id="pics_preview_size_custom" size="10" value="<?php echo $_REQUEST['pics_preview_size_custom']; ?>" />
                <span style="padding-left: 5px;">WxH</span>
            </div>
            
            <div class="fieldgroup">
                <label for="pics_annotation">Preview Annotation:</label>
                <?PHP if( !count($annotations) ): ?>
                <div style="float: left;" class="warn">No annotations have been configured</div>
                <div style="clear: both"></div>
                <?PHP else: ?>
                <select name="pics_annotation">
                  <option value="0">None</option>
                  <?PHP echo OptionTagsAdv($annotations, $_REQUEST['pics_annotation'], 'annotation_id', 'identifier', 50); ?>
                </select>
                <?PHP endif; ?>
            </div>
        </fieldset>
        
        
        <fieldset>
          <legend>Movie Gallery Settings</legend>
            
            <div class="fieldgroup">
                <label></label>
                <label for="movies_allowed" class="cblabel inline"><?php echo CheckBox('movies_allowed', 'checkbox', 1, $_REQUEST['movies_allowed']); ?> Allow movie galleries in this category</label>
            </div>
            
            <div class="fieldgroup">
                <label for="movies_extensions">File Extensions:</label>
                <input type="text" name="movies_extensions" id="movies_extensions" size="40" value="<?php echo $_REQUEST['movies_extensions']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="movies_minimum">Minimum Thumbs:</label>
                <input type="text" name="movies_minimum" id="movies_minimum" size="10" value="<?php echo $_REQUEST['movies_minimum']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="movies_maximum">Maximum Thumbs:</label>
                <input type="text" name="movies_maximum" id="movies_maximum" size="10" value="<?php echo $_REQUEST['movies_maximum']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="movies_file_size">Minimum Filesize:</label>
                <input type="text" name="movies_file_size" id="movies_file_size" size="10" value="<?php echo $_REQUEST['movies_file_size']; ?>" />
            </div>           
            
            <div class="fieldgroup">
                <label></label>
                <label for="movies_preview_allowed" class="cblabel inline"><?php echo CheckBox('movies_preview_allowed', 'checkbox', 1, $_REQUEST['movies_preview_allowed']); ?> Allow preview thumbnails for this format</label>
            </div>          
            
            <div class="fieldgroup">
                <label for="movies_preview_size">Preview Dimensions:</label>
                <select name="movies_preview_size">
                  <option value="custom">Custom --&gt;</option>
                  <?php echo OptionTags($sizes, $_REQUEST['movies_preview_size'], TRUE); ?>
                </select>
                <input type="text" name="movies_preview_size_custom" id="movies_preview_size_custom" size="10" value="<?php echo $_REQUEST['movies_preview_size_custom']; ?>" />
                <span style="padding-left: 5px;">WxH</span>
            </div>
            
            <div class="fieldgroup">
                <label for="movies_annotation">Preview Annotation:</label>
                <?PHP if( !count($annotations) ): ?>
                <div style="float: left;" class="warn">No annotations have been configured</div>
                <div style="clear: both"></div>
                <?PHP else: ?>
                <select name="movies_annotation">
                  <option value="0">None</option>
                  <?PHP echo OptionTagsAdv($annotations, $_REQUEST['movies_annotation'], 'annotation_id', 'identifier', 50); ?>
                </select>
                <?PHP endif; ?>
            </div>
        </fieldset>
        
        <?php if( $editing ): ?>
        <fieldset>
          <legend>Bulk Edit Options</legend>          
            <div class="fieldgroup">
              <label></label>
              <input type="checkbox" class="checkbox" name="apply_all" id="apply_all" value="1"> 
              <label for="apply_all" class="cblabel inline">Apply these settings to all categories</label><br />
            </div>
            
            <div class="fieldgroup">
              <label></label>
              <input type="checkbox" class="checkbox" name="apply_matched" id="apply_matched" value="1">
              <label for="apply_matched" class="cblabel inline">Apply these settings to matched categories</label><br />
            </div>
        </fieldset>
        <?php endif; ?>
    
    <div class="centered margin-top">
      <?php if( $editing_default ): ?>
      <button type="submit">Save Default Settings</button>
      <?php else: ?>
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Category</button>
      <?php endif; ?>
    </div>

    <?php if( $editing_default ): ?>
    <input type="hidden" name="r" value="txCategoryEditDefault">
    <input type="hidden" name="name" value="DEFAULT">
    <?php else: ?>
    <input type="hidden" name="r" value="<?php echo ($editing ? 'txCategoryEdit' : 'txCategoryAdd'); ?>">
    <?php endif; ?>
    
    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <input type="hidden" name="category_id" value="<?php echo $_REQUEST['category_id']; ?>">
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
