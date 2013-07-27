<?php
if( !defined('TGPX') ) die("Access denied");            

$categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
array_unshift($categories, array('category_id' => '__ALL__', 'name' => 'ALL CATEGORIES'));

include_once('includes/header.php');
?>

<script language="JavaScript">
function domainChange()
{
    var domain = $('#domain').val().replace(/^www\./i, '');

    $('#domain').val(domain);
    
    if( $('#base_url').val() == '' )
    {
        var www = '';
        if( domain.search(/www\./) == -1 )
            www = 'www.';
        $('#base_url').val('http://'+www+domain);
    }
    
    if( $('#tags').val() == '' )
    {
        $('#tags').val(domain.replace(/[^a-z0-9_]/g, '_'));
    }
    
    if( $('#template_prefix').val() == '' )
    {
        $('#template_prefix').val(domain+'-');
    }
}
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/domains.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this domain by making changes to the information below
      <?php else: ?>
      Add a new domain by filling out the information below
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
                <label for="domain">Domain:</label>
                <input type="text" name="domain" id="domain" size="50" value="<?php echo $_REQUEST['domain']; ?>" onchange="domainChange()" />
            </div>
            
            <div class="fieldgroup">
                <label for="base_url">Base URL:</label>
                <input type="text" name="base_url" id="base_url" size="80" value="<?php echo $_REQUEST['base_url']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="document_root">Document Root:</label>
                <input type="text" name="document_root" id="document_root" size="80" value="<?php echo $_REQUEST['document_root']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="tags">Tags:</label>
                <input type="text" name="tags" id="tags" size="50" value="<?php echo $_REQUEST['tags']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="template_prefix">Template Prefix:</label>
                <input type="text" name="template_prefix" id="template_prefix" size="30" value="<?php echo $_REQUEST['template_prefix']; ?>" />
                <label for="create_templates" class="cblabel inline"><?php echo CheckBox('create_templates', 'checkbox', 1, $_REQUEST['create_templates']); ?> Create templates with this prefix</label>
            </div>
            
            <div class="fieldgroup">
                <label for="categories[]">Categories:</label>
                <div id="category_selects" style="float: left;">
                <?php
                if( is_array($_REQUEST['categories']) ):
                    foreach( $_REQUEST['categories'] as $category ):
                ?>
                
                <div style="margin-bottom: 3px;">
                <select name="categories[]">
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
                <select name="categories[]">
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
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="as_exclude" class="cblabel inline"><?php echo CheckBox('as_exclude', 'checkbox', 1, $_REQUEST['as_exclude']); ?> Use the above selected categories as an exclusion list</label>
            </div>
        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Domain</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txDomainEdit' : 'txDomainAdd'); ?>">
    
    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <input type="hidden" name="domain_id" value="<?php echo $_REQUEST['domain_id']; ?>" />
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
