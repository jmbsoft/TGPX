<?php
if( !defined('TGPX') ) die("Access denied");         

$categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');

$defaults = array('weight' => 1,
                  'raw_clicks' => 0,
                  'unique_clicks' => 0);
                  
$_REQUEST = array_merge($defaults, $_REQUEST);

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
        <a href="docs/advertisements.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this advertisement by making changes to the information below
      <?php else: ?>
      Add a new advertisement by filling out the information below
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
                <label for="weight">Weight:</label>
                <input type="text" name="weight" id="weight" size="5" value="<?php echo $_REQUEST['weight']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="raw_clicks">Raw Clicks:</label>
                <input type="text" name="raw_clicks" id="raw_clicks" size="8" value="<?php echo $_REQUEST['raw_clicks']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="unique_clicks">Unique Clicks:</label>
                <input type="text" name="unique_clicks" id="unique_clicks" size="8" value="<?php echo $_REQUEST['unique_clicks']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="tags">Tags:</label>
                <input type="text" name="tags" id="tags" size="80" value="<?php echo $_REQUEST['tags']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="categories">Categories:</label>
                <div id="category_selects" style="float: left;">
                <?php 
                
                if( is_array($_REQUEST['categories']) ):                        
                    foreach( $_REQUEST['categories'] as $category ):
                ?>
                
                <div style="margin-bottom: 3px;">
                <select name="categories[]" id="categories">
                <?php
                echo OptionTagsAdv($categories, $category, 'category_id', 'name', 50);
                ?>
                </select>
                <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
                <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
                </div>
                
                <?php
                    endforeach;
                else: 
                ?>
                <div style="margin-bottom: 3px;">
                <select name="categories[]" id="categories">
                <?php            
                echo OptionTagsAdv($categories, null, 'category_id', 'name', 50);
                ?>
                </select>
                <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
                <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
                </div>
                <?php endif; ?>            
                </div>
            </div>
            
            <div class="fieldgroup" style="clear: both">
                <label for="ad_url">Ad URL:</label>
                <input type="text" name="ad_url" id="ad_url" size="100" value="<?php echo $_REQUEST['ad_url']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="ad_html_raw">Ad HTML:</label>
                <textarea name="ad_html_raw" rows="10" cols="100" wrap="off"><?php echo $_REQUEST['ad_html_raw']; ?></textarea>
            </div>
        </div>
        </fieldset>
    
    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Advertisement</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txAdEdit' : 'txAdAdd'); ?>">
    
    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <input type="hidden" name="ad_id" value="<?php echo $_REQUEST['ad_id']; ?>">
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
