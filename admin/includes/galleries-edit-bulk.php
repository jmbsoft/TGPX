<?php
if( !defined('TGPX') ) die("Access denied");

$defaults = array('submit_ip' => $_SERVER['REMOTE_ADDR'],
                  'status' => 'active',
                  'date_added' => gmdate(DF_DATETIME, TimeWithTz()),
                  'date_modified' => '',
                  'clicks' => 0,
                  'ratings' => 0,
                  'rating_total' => 0,
                  'weight' => $C['gallery_weight'],
                  'allow_scan' => true,
                  'allow_preview' => true);

if( !$editing )
{
    $_REQUEST = array_merge($defaults, $_REQUEST);
}

$categories =& $DB->FetchAll('SELECT `name`,`category_id` FROM `tx_categories` ORDER BY `name`');
$sponsors =& $DB->FetchAll('SELECT `sponsor_id`,`name` FROM `tx_sponsors` ORDER BY `name`');
$icons =& $DB->FetchAll('SELECT * FROM `tx_icons` ORDER BY `identifier`');

$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');
include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#date_scheduled').datePicker({startDate:'2000-01-01'});
      $('#date_displayed').datePicker({startDate:'2000-01-01'});
      $('#date_deletion').datePicker({startDate:'2000-01-01'});

      $('#to').html(window.parent.$('#which [@selected]').text());
      $('#search_form').val(window.parent.$('#search').formSerialize());
      $('#results').val(window.parent.$('#results').formSerialize());

      $('label')
      .css({backgroundColor: '#FEE7E8'})
      .bind('click', function()
                     {
                         $(this).css({backgroundColor: '#EFF8E0'});
                     });

      $('input, select, checkbox, textarea')
      .bind('change', function()
                      {
                          $('label[@for='+this.id+']').css({backgroundColor: '#EFF8E0'});
                      });
  });

function addCategorySelect(img)
{
    $(img.parentNode).clone().hide().appendTo('#category_selects').slideDown(200);
}

function removeCategorySelect(img)
{
    if( $('#category_selects select').length == 1 )
    {
        alert('There must be at least one category defined');
        return false;
    }

    if( confirm('Are you sure you want to remove this category?') )
        $(img.parentNode).slideUp(200, function() { $(this).remove(); });
}


function searchUsers()
{
    var term = $('#partner').val();

    if( !term )
    {
        alert('Please enter an e-mail address or username to search for');
        return;
    }

    $('#user_search').html('<img src="images/activity-small.gif">').show();

    $.ajax({type: 'POST',
            url: 'ajax.php',
            dataType: 'json',
            data: 'r=txPartnerSearchQuick&s=' + escape($('#partner').val()),
            error: function(request, error)
                  {
                      alert('The XMLHttpRequest failed; check your internet connection and make sure your server is online');
                  },
            success: function(json)
                     {
                         if( json.status == JSON_SUCCESS )
                         {
                             $('#user_search').html((json.matches > 0 ?
                                                    '<select onfocus="$(\'#partner\').val($(this).val())">' +
                                                    json.html +
                                                    '</select>' :
                                                    '<span style="color: red; font-weight: bold;">No Matches<span>'));
                         }
                         else
                         {
                             $('#user_search').html('<span class="alert">' + json.message + '</span>');
                         }
                     }
           });
}

<?PHP if( $GLOBALS['added'] && empty($_REQUEST['nosearch']) ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/galleries.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Update <span id="to"></span> by making changes to the information below
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

    <?php if( is_array($GLOBALS['warn']) ): ?>
    <div class="warn margin-bottom">
      <?php echo join('<br />', $GLOBALS['warn']); ?>
    </div>
    <?php endif; ?>

    <form action="index.php" method="POST" id="form">
      <fieldset>
        <legend>General Information</legend>

        <div class="fieldgroup">
            <label for="email">E-mail:</label>
            <input type="text" name="email" id="email" size="30" value="<?php echo $_REQUEST['email']; ?>" />
            <img src="images/check.png" border="0">
        </div>

        <div class="fieldgroup">
            <label for="nickname">Submitter Name:</label>
            <input type="text" name="nickname" id="nickname" size="30" value="<?php echo $_REQUEST['nickname']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="submit_ip">Submitter IP:</label>
            <input type="text" name="submit_ip" id="submit_ip" size="16" value="<?php echo $_REQUEST['submit_ip']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="gallery_url">Gallery URL:</label>
            <input type="text" name="gallery_url" id="gallery_url" size="90" value="<?php echo $_REQUEST['gallery_url']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="description">Description:</label>
            <input type="text" name="description" id="description" size="90" value="<?php echo $_REQUEST['description']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="tags">Tags:</label>
            <input type="text" name="tags" id="tags" size="80" value="<?php echo $_REQUEST['tags']; ?>"/>
        </div>

        <div class="fieldgroup">
            <label for="keywords">Keywords:</label>
            <input type="text" name="keywords" id="keywords" size="80" value="<?php echo $_REQUEST['keywords']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="thumbnails">Thumbnails:</label>
            <input type="text" name="thumbnails" id="thumbnails" size="10" value="<?php echo $_REQUEST['thumbnails']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="categories">Categories:</label>
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
            <label for="sponsor_id">Sponsor:</label>
            <select name="sponsor_id">
              <option value=""></option>
            <?php
            echo OptionTagsAdv($sponsors, $_REQUEST['sponsor_id'], 'sponsor_id', 'name', 50);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="status">Status:</label>
            <select name="status">
            <?php
            $statuses = array('pending' => 'Pending',
                              'approved' => 'Approved',
                              'used' => 'Used',
                              'holding' => 'Holding',
                              'disabled' => 'Disabled');

            echo OptionTags($statuses, $_REQUEST['status']);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="type">Type:</label>
            <select name="type">
            <?php
            $types = array('submitted' => 'Submitted',
                           'permanent' => 'Permanent');

            echo OptionTags($types, $_REQUEST['type']);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="format">Format:</label>
            <select name="format">
            <?php if( !$editing ): ?>
              <option value="">Auto-detect</option>
            <?php
            endif;
            $formats = array('pictures' => 'Pictures',
                             'movies' => 'Movies');

            echo OptionTags($formats, $_REQUEST['format']);
            ?>
            </select>
        </div>

        <div class="fieldgroup">
            <label for="username">Partner:</label>
            <input type="text" name="partner" id="partner" size="30" value="<?php echo $_REQUEST['partner']; ?>" />
            <img src="images/search.png" height="12" width="12" alt="Search" onclick="searchUsers()" class="click-image"> &nbsp;
            <span id="user_search" style="display: none;"></span>
        </div>

        <div class="fieldgroup">
            <label for="clicks">Clicks:</label>
            <input type="text" name="clicks" id="clicks" size="10" value="<?php echo $_REQUEST['clicks']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="date_scheduled">Scheduled Date:</label>
            <input type="text" name="date_scheduled" id="date_scheduled" size="20" value="<?php echo $_REQUEST['date_scheduled']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="date_displayed">Displayed Date:</label>
            <input type="text" name="date_displayed" id="date_displayed" size="20" value="<?php echo $_REQUEST['date_displayed']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="date_deletion">Delete Date:</label>
            <input type="text" name="date_deletion" id="date_deletion" size="20" value="<?php echo $_REQUEST['date_deletion']; ?>" />
        </div>

        <div class="fieldgroup">
            <label for="weight">Weight:</label>
            <input type="text" name="weight" id="weight" size="10" value="<?php echo $_REQUEST['weight']; ?>"/>
        </div>

        <?php foreach($icons as $icon): ?>
          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="icons[<?php echo $icon['icon_id']; ?>]" class="cblabel inline">
            <?php echo CheckBox("icons[{$icon['icon_id']}]", 'checkbox', $icon['icon_id'], $_REQUEST['icons'][$icon['icon_id']]) . " " . $icon['icon_html']; ?></label>
          </div>
        <?php endforeach; ?>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_scan" class="cblabel inline">
            <?php echo CheckBox('allow_scan', 'checkbox', 1, $_REQUEST['allow_scan']); ?> Allow the gallery scanner to scan this gallery</label>
        </div>

        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_preview" class="cblabel inline">
            <?php echo CheckBox('allow_preview', 'checkbox', 1, $_REQUEST['allow_preview']); ?> Allow the gallery scanner to create a thumbnail for this gallery</label>
        </div>
      </fieldset>

      <?php
      $result = $DB->Query('SELECT * FROM `tx_gallery_field_defs` ORDER BY `field_id`');
      ?>
      <fieldset<?php if( $DB->NumRows($result) < 1 ) echo ' style="display: none;"'; ?>>
        <legend>User Defined Fields</legend>

        <?php
        while( $field = $DB->NextRow($result) ):
            ArrayHSC($field);
            AdminFormField($field);
        ?>

        <div class="fieldgroup">
            <?php if( $field['type'] != FT_CHECKBOX ): ?>
              <label for="<?php echo $field['name']; ?>"><?php echo $field['label']; ?>:</label>
              <?php echo FormField($field, $_REQUEST[$field['name']]); ?>
            <?php else: ?>
              <label class="lesspad"></label>
              <label for="<?php echo $field['name']; ?>" class="cblabel inline">
              <?php echo FormField($field, $_REQUEST[$field['name']]); ?> <?php echo $field['label']; ?></label>
            <?php endif; ?>
        </div>

        <?php
        endwhile;
        $DB->Free($result);
        ?>
      </fieldset>

      <div class="centered margin-top">
      <button type="submit">Bulk Edit Galleries</button>
      </div>

    <input type="hidden" name="r" value="txGalleryEditBulk">
    <input type="hidden" name="search_form" id="search_form" value="">
    <input type="hidden" name="results" id="results" value="">
    </form>
</div>

</body>
</html>
