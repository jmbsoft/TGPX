<?php
if( !defined('TGPX') ) die("Access denied");

$defaults = array('status' => 'active',
                  'type' => 'regular');
                  
if( !isset($_REQUEST['analyzed']) )
{
    $_REQUEST = array_merge($_REQUEST, $defaults);
}

// Get settings from last import
if( !is_array($_REQUEST['fields']) )
{
    $last_import = GetValue('last_import');
    
    if( $last_import != null )
    {
        $_REQUEST['fields'] = unserialize($last_import);
    }
}               

$fields = explode('|', FileReadLine("{$GLOBALS['BASE_DIR']}/data/$filename"));
$user_fields =& GetUserGalleryFields();


$field_options = array('IGNORE' => 'IGNORE',
                       'gallery_url' => 'Gallery URL',
                       'description' => 'Description',
                       'keywords' => 'Keywords',
                       'tags' => 'Tags',
                       'categories' => 'Categories',
                       'thumbnails' => 'Thumbnails',
                       'email' => 'E-mail Address',
                       'nickname' => 'Nickname',
                       'weight' => 'Weight',
                       'clicks' => 'Clicks',
                       'submit_ip' => 'Submit IP',
                       'sponsor_id' => 'Sponsor',
                       'type' => 'Type',
                       'format' => 'Format',                   
                       'date_scheduled' => 'Date Scheduled',
                       'date_deletion' => 'Date of Deletion',
                       'partner' => 'Partner',
                       'icons' => 'Icons',                       
                       'preview_url' => 'Preview URL',
                       'dimensions' => 'Preview Size');
                       
foreach($user_fields as $user_field)
{
    $field_options[$user_field['name']] = StringChop($user_field['label'], 25);
}
            
include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#bad_category').bind('change', function()
                                        {
                                            if( this.options[this.selectedIndex].value == 'force' )
                                            {
                                                $('#category_select').SlideInLeft(300);
                                            }
                                            else
                                            {
                                                if( $('#category_select').css('display') != 'none' )
                                                    $('#category_select').SlideOutLeft(300);
                                            }
                                        });
                                        
      $('#sponsor').bind('change', function()
                                    {
                                        if( $(this).val() == '' )
                                        {
                                            $('#div_add_sponsor:hidden').slideDown();
                                        }
                                        else
                                        {
                                            $('#div_add_sponsor:visible').slideUp();
                                        }
                                    });
  });
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/galleries-import.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Import Galleries
    </div>
    
    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-top">
      <?php echo $GLOBALS['message']; ?>
    </div>        
    <?php endif; ?>
    
    <?php if( $GLOBALS['errstr'] ): ?>
    <div class="alert margin-top">
      <?php echo $GLOBALS['errstr']; ?>
    </div>        
    <?php endif; ?>

    <form action="index.php" method="POST">
    <div class="margin-top">
    Below is the analysis of your gallery import data.  Select the correct field type for each of the values extracted from the import data, select one or
    more categories for the galleries to be imported to, and then press the Import Galleries button.
    
    <fieldset style="padding-left: 10px">
      <legend>Import Data</legend>
        <table width="100%" border="0">
        <?php for( $i = 0; $i < count($fields); $i++ ): ?>
        <tr>
          <td width="120" valign="top">
            <select name="fields[<?php echo $i; ?>]">
            <?php            
            echo OptionTags($field_options, is_array($_REQUEST['fields']) ? $_REQUEST['fields'][$i] : null);
            ?>
          </select>
          </td>
          <td>
            <?php echo htmlspecialchars($fields[$i]); ?>
          </td>
        </tr>
        <?php endfor; ?>
        </table>
    </fieldset>
    
    <fieldset style="padding-left: 10px">
      <legend>Other Settings</legend>     
      <div class="fieldgroup">
        <label for="status">Status:</label>
        <select name="status" id="status">
        <?php
        $statuses = array('pending' => 'Pending',
                          'approved' => 'Approved');
                          
        echo OptionTags($statuses, $_REQUEST['status']);                              
        ?>
        </select>
      </div>
      
      <div class="fieldgroup">
        <label for="type">Type:</label>
        <select name="type" id="type">
        <?php
        $types = array('' => 'From Import Data',
                       'submitted' => 'Submitted',
                       'permanent' => 'Permanent');
                          
        echo OptionTags($types, $_REQUEST['type']);                              
        ?>
        </select>
      </div>
      
      <div class="fieldgroup">
        <label for="format">Format:</label>
        <select name="format" id="format">
        <?php
        $formats = array('' => 'From Import Data',
                       'pictures' => 'Pictures',
                       'movies' => 'Movies');
                          
        echo OptionTags($formats, $_REQUEST['format']);                              
        ?>
        </select>
      </div>
      
      <div class="fieldgroup">
        <label for="sponsor">Sponsor:</label>
        <select name="sponsor" id="sponsor">
        <?php
        $sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors` ORDER BY `name`');
        array_unshift($sponsors, array('sponsor_id' => '', 'name' => 'From Import Data')); 
                          
        echo OptionTagsAdv($sponsors, $_REQUEST['sponsor'], 'sponsor_id', 'name', 60);                              
        ?>
        </select>
        
        <div id="div_add_sponsor" style="margin-top: 3px;">
        <label for="add_sponsor" class="cblabel inline"><?php echo CheckBox('add_sponsor', 'checkbox', 1, $_REQUEST['add_sponsor']); ?> Add new sponsor if imported sponsor name does not already exist</label>
        </div>
      </div>
      
      <div class="fieldgroup">
        <label for="bad_category">Bad Category:</label>
        <div style="float: left;">
        <select name="bad_category" id="bad_category">
        <?php
        $bc_options = array('skip' => 'Skip over gallery',
                            'create' => 'Create new category',
                            'force' => 'Place in this category --->');
                          
        echo OptionTags($bc_options, $_REQUEST['bad_category']);                              
        ?>
        </select>
        </div>
        <div id="category_select" style="display: none; float: left;">
        <select name="forced_category" id="forced_category">
        <?php
        $categories =& $DB->FetchAll('SELECT `name`,`tag` FROM `tx_categories` ORDER BY `name`');
                          
        echo OptionTagsAdv($categories, $_REQUEST['forced_category'], 'tag', 'name', 60);                              
        ?>
        </select>
        </div>
      </div>
      
      <div class="fieldgroup">
        <label for="sponsor">Duplicates:</label>
        <select name="duplicates" id="duplicates">
        <?php
        $duplicates = array('skip' => 'Skip over gallery',
                            'replace' => 'Replace old gallery data with new gallery data',
                            'allow' => 'Add duplicates to database');
                          
        echo OptionTags($duplicates, $_REQUEST['duplicates']);                             
        ?>
        </select>
      </div>
    </fieldset>
    
    <div class="centered margin-top">
    <button type="submit">Import Galleries</button>
    </div>
    </div>
    <input type="hidden" name="r" value="txGalleryImport">
    <input type="hidden" name="analyzed" value="1">
    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($filename); ?>">
    </form>
    
    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
