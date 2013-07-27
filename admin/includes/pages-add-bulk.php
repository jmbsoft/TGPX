<?php
if( !defined('TGPX') ) die("Access denied");

$categories =& $DB->FetchAll('SELECT `name`,`category_id` FROM `tx_categories` ORDER BY `name`');

if( !isset($_REQUEST['build_order']) )
{
    $_REQUEST['build_order'] = $DB->Count('SELECT MAX(build_order) FROM `tx_pages`') + 1;
}


include_once('includes/header.php');
?>

<script language="JavaScript">
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>

$(function()
{
    categoryChange();

    $('#domain').bind('change', function()
                                {
                                    var data = $(':selected', this).data();
                                    
                                    $('#base_dir').val(data.document_root);
                                    $('#base_url').val(data.base_url);
                                }).trigger('change');

});


function checkFilename()
{
    if( $('#category_id').val() == '' && $('#prefix').val().match("[^a-zA-Z0-9\-\._]") )
    {
        alert('The filename prefix may only contain letters, numbers, dots, dashes, and underscores');
        return false;
    }
    
    if( $('#ext').val().match("[^a-zA-Z0-9]") )
    {
        alert('The file extension may only contain letters and numbers');
        return false;
    }
    
    return true;
}

function categoryChange()
{
    if( $('#category_id').val() == '' )
    {
        $('#filename_prefix:hidden').slideDown(300);
    }
    else
    {
        $('#filename_prefix:visible').slideUp(300);
    }
}
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form" onsubmit="return checkFilename()">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/pages-manage.html#add-bulk" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Add TGP pages in bulk by filling out the information below
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
        
        <?php if( $GLOBALS['warn'] ): ?>
        <div class="warn margin-bottom">
          <?php 
          foreach( $GLOBALS['warn'] as $warning ):
              echo "$warning<br />";
          endforeach;
          ?>
        </div>        
        <?php endif; ?>

        <fieldset>
          <legend>General Information</legend>


          <?php
          $domains =& $DB->FetchAll('SELECT * FROM `tx_domains` ORDER BY `domain`');
          
          if( count($domains) ):
              ArrayHSC($domains);
          ?>
          <div class="fieldgroup">
            <label for="domain">Domain:</label>
            <select id="domain">
              <?php foreach( $domains as $domain ): ?>
              <option class="{base_url: '<?php echo $domain['base_url']; ?>', document_root: '<?php echo $domain['document_root']; ?>'}"><?php echo $domain['domain']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php
          endif;
          ?>
          
          <div class="fieldgroup">
            <label for="base_dir">Base Directory:</label>
            <input type="text" name="base_dir" id="base_dir" size="80" value="<?php echo $_REQUEST['base_dir']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label for="base_url">Base URL:</label>
            <input type="text" name="base_url" id="base_url" size="80" value="<?php echo $_REQUEST['base_url']; ?>" />
          </div>


         
          <div class="fieldgroup">
            <label for="category_id">Category:</label>
            <select name="category_id" id="category_id" onchange="categoryChange()">
              <option value="">MIXED</option>
                <?php
                array_unshift($categories, array('category_id' => '__all__', 'name' => 'ALL CATEGORIES'));
                echo OptionTagsAdv($categories, $_REQUEST['category_id'], 'category_id', 'name', 50);
                ?>
            </select>
          </div>
        
          <div id="filename_prefix" style="clear: both">
          <div class="fieldgroup">
            <label for="prefix">Filename Prefix:</label>
            <input type="text" name="prefix" id="prefix" size="20" value="<?php echo $_REQUEST['prefix']; ?>" />
          </div>
          </div>
          
          <div class="fieldgroup">
            <label for="ext">File Extension:</label>
            <input type="text" name="ext" id="ext" size="5" value="<?php echo $_REQUEST['ext']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label for="tags">Tags:</label>
            <input type="text" name="tags" id="tags" size="80" value="<?php echo $_REQUEST['tags']; ?>" />
          </div>
        
          <div class="fieldgroup">
            <label for="num_pages">Number of Pages:</label>
            <input type="text" name="num_pages" id="num_pages" size="5" value="<?php echo $_REQUEST['num_pages']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label>Conversions:</label>
            <div style="float: left;">
              <select name="characters">
                <?php
                $characters = array('remove' => 'Remove all non-alphanumeric characters',
                                    'dash' => 'Replace all non-alphanumeric characters with a dash',
                                    'underscore' => 'Replace all non-alphanumeric characters with an underscore');
                                    
                echo OptionTags($characters, $_REQUEST['characters']);
                ?>
              </select>
              <br />
              <select name="case">
                <?php
                $cases = array('lower' => 'All letters lower case',
                               'nochange' => 'No change to text case');
                                    
                echo OptionTags($cases, $_REQUEST['case']);
                ?>
            </select>
            </div>
          </div>
          
          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="locked" class="cblabel inline"><?php echo CheckBox('locked', 'checkbox', 1, $_REQUEST['locked']); ?> Lock these pages</label>
          </div>

        </fieldset>
    
    <div class="centered margin-top">
      <button type="submit">Add TGP Pages</button>
    </div>

    <input type="hidden" name="r" value="txPageAddBulk">    
    </form>
</div>

    

</body>
</html>
