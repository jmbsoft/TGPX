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

$(function()
{
    $('#domain').bind('change', function()
                                {
                                    var data = $(':selected', this).data();
                                    
                                    $('#filename').val(data.document_root);
                                    $('#page_url').val(data.base_url);
                                }).trigger('change');
                                
});


<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>

function checkFilename()
{


    var path = $('#filename').val();


   
    var lastslash = path.lastIndexOf('/');
    var filename = path.substr(lastslash + 1);
    
    if( filename.match("[^a-zA-Z0-9\-\._]") )
    {
        alert('The page filename may only contain letters, numbers, dots, dashes, and underscores');
        return false;
    }

    if( filename.indexOf('.') == -1 )
    {
        return confirm("WARNING\r\n" +
                       "Adding pages without a file extension may cause\r\n" +
                       "the page to display incorrectly in your browser.\r\n" +
                       "Are you sure you want to add this page without a\r\n" +
                       "file extension?");
    }
    
    return true;
}
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form" onsubmit="return checkFilename()">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/pages-manage.html#add" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this TGP page by making changes to the information below
      <?php else: ?>
      Add a new TGP page by filling out the information below
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
          if( !$editing ):
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
          endif;
          ?>
          
          <div class="fieldgroup">
            <label for="filename">Path &amp; Filename:</label>
            <input type="text" name="filename" id="filename" size="80" value="<?php echo $_REQUEST['filename']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label for="page_url">Page URL:</label>
            <input type="text" name="page_url" id="page_url" size="80" value="<?php echo $_REQUEST['page_url']; ?>" />
          </div>


         
          <div class="fieldgroup">
            <label for="category_id">Category:</label>
            <select name="category_id">
              <option value="">MIXED</option>
            <?php
            echo OptionTagsAdv($categories, $_REQUEST['category_id'], 'category_id', 'name', 50);
            ?>
            </select>
          </div>
        
          <div class="fieldgroup">
            <label for="build_order">Build Order:</label>
            <input type="text" name="build_order" id="build_order" size="5" value="<?php echo $_REQUEST['build_order']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label for="tags">Tags:</label>
            <input type="text" name="tags" id="tags" size="80" value="<?php echo $_REQUEST['tags']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="locked" class="cblabel inline"><?php echo CheckBox('locked', 'checkbox', 1, $_REQUEST['locked']); ?> Lock this page</label>
          </div>
         
        </fieldset>
    
    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> TGP Page</button>
    </div>

    <input type="hidden" name="page_id" value="<?php echo $_REQUEST['page_id']; ?>" />
    <input type="hidden" name="r" value="<?php echo ($editing ? 'txPageEdit' : 'txPageAdd'); ?>">
    
    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
