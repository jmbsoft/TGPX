<?php
if( !defined('TGPX') ) die("Access denied");         

$item_tags = array('' => 'SKIP',
                   'title' => '<title>', 
                   'link' => '<link>', 
                   'description' => '<description>', 
                   'author' => '<author>', 
                   'category' => '<category>', 
                   'comments' => '<comments>', 
                   'enclosure' => '<enclosure>', 
                   'guid' => '<guid>', 
                   'pubDate' => '<pubDate>', 
                   'source' => '<source>', 
                   'content:encoded' => '<content:encoded>');

                   
$defaults = array('settings' => array('gallery_url_from' => 'link',
                  'description_from' => 'description',
                  'date_added_from' => 'pubDate'));
                  
$_REQUEST = array_merge($defaults, $_REQUEST);

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#sponsor_id').bind('change', function()
                                        {
                                            if( this.selectedIndex == 1 )
                                            {
                                                $('#sponsor_name_div:hidden').SlideInLeft(300);
                                            }
                                            else
                                            {
                                                $('#sponsor_name_div:visible').SlideOutLeft(300);
                                            }
                                        }).trigger('change');
                                        
      $('#access_feed').bind('click', accessFeed);
  });
  
function accessFeed()
{
    $('#rss_access_error').hide();
    $('#rss_item').hide();
    $('#activity').show();
    
    $.ajax({type: 'POST',
            url: 'ajax.php',
            dataType: 'json',
            data: 'r=txRssFeedAccess&url='+escape($('#feed_url').val()),
            error: function(request, status, error)
                   {
                        $('#activity').hide();
                        $('#rss_access_error').html('The XmlHttpRequest failed: ' + error).show();
                   },
            success: function(json)
                     {
                         $('#activity').hide();
                         
                         if( json.status == JSON_SUCCESS )
                         {
                             $('#rss_item').html(json.html).show();
                         }
                         else
                         {
                             $('#rss_access_error').html(json.message).show();
                         }
                     }});
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
        <a href="docs/rss-feeds.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this RSS feed by making changes to the information below
      <?php else: ?>
      Add a new RSS feed by filling out the information below
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
                <label for="feed_url">RSS Feed URL:</label>
                <input type="text" name="feed_url" id="feed_url" size="90" value="<?php echo $_REQUEST['feed_url']; ?>" />
            </div>
            
            <?php
            $sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors` ORDER BY `name`', null);
            array_unshift($sponsors, array('sponsor_id' => '', 'name' => 'NONE'), array('sponsor_id' => '__NEW__', 'name' => 'NEW SPONSOR -->'))
            ?>
            <div class="fieldgroup">
                <label for="sponsor_id">Sponsor:</label>
                <div style="float: left;">
                <select name="sponsor_id" id="sponsor_id">
                  <?php echo OptionTagsAdv($sponsors, $_REQUEST['sponsor_id'], 'sponsor_id', 'name', 40); ?>
                </select>
                </div>
                <div id="sponsor_name_div" style="display: none; float: left;">
                <input type="text" name="sponsor_name" size="60" value="<?php echo $_REQUEST['sponsor_name']; ?>">
                </div>
            </div>
        </fieldset>
        
        <fieldset>
          <legend>Import Settings</legend>

          <div class="fieldgroup">
            <label for=""></label>
            <button type="button" id="access_feed">Access RSS Feed</button>
          </div>
          
          <div class="fieldgroup" id="activity" style="display: none">
            <img src="images/activity.gif" border="0"> Please wait while the RSS feed is accessed...
          </div>

          <div class="alert margin-bottom margin-top" id="rss_access_error" style="display: none">
          </div>
          
          <div class="margin-bottom margin-top" id="rss_item" style="display: none; background-color: #FFFFE1; padding: 5px;">
          </div>
          
          <div class="fieldgroup">
            <label for="gallery_url_from">Gallery URL From:</label>
            <select name="settings[gallery_url_from]" id="gallery_url_from">
            <?php
            echo OptionTags($item_tags, $_REQUEST['settings']['gallery_url_from']);                              
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="description_from">Description From:</label>
            <select name="settings[description_from]" id="description_from">
            <?php
            echo OptionTags($item_tags, $_REQUEST['settings']['description_from']);                              
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="date_added_from">Date Added From:</label>
            <select name="settings[date_added_from]" id="date_added_from">
            <?php
            echo OptionTags($item_tags, $_REQUEST['settings']['date_added_from']);                              
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="preview_from">Preview Thumb From:</label>
            <select name="settings[preview_from]" id="preview_from">
            <?php
            echo OptionTags($item_tags, $_REQUEST['settings']['preview_from']);                              
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="status">Status:</label>
            <select name="settings[status]" id="status">
            <?php
            $statuses = array('approved' => 'Approved', 'pending' => 'Pending');
                              
            echo OptionTags($statuses, $_REQUEST['settings']['status']);                              
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="type">Type:</label>
            <select name="settings[type]" id="type">
            <?php
            $types = array('submitted' => 'Submitted',
                           'permanent' => 'Permanent');
                              
            echo OptionTags($types, $_REQUEST['settings']['type']);                              
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="format">Format:</label>
            <select name="settings[format]" id="format">
            <?php
            $formats = array('pictures' => 'Pictures',
                             'movies' => 'Movies');
                              
            echo OptionTags($formats, $_REQUEST['settings']['format']);                              
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="category">Category:</label>
            <select name="settings[category]" id="category">
            <?php
            $categories =& $DB->FetchAll('SELECT `name`,`category_id` FROM `tx_categories` ORDER BY `name`');                              
            echo OptionTagsAdv($categories, $_REQUEST['settings']['category'], 'category_id', 'name', 60);                              
            ?>
            </select>
            </div>
          </div>
      
        </fieldset>
    
    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> RSS Feed</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txRssFeedEdit' : 'txRssFeedAdd'); ?>">
    
    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <input type="hidden" name="feed_id" value="<?php echo $_REQUEST['feed_id']; ?>">
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
