<?php
if( !defined('TGPX') ) die("Access denied");

$jscripts = array('includes/calendar.js', 'includes/galleries-search.js');
$csses = array('includes/calendar.css');

// Load saved searches
$searches =& $DB->FetchAll('SELECT `search_id`,`identifier` FROM `tx_saved_searches` ORDER BY `identifier`');

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<style>
form div.fieldgroup {
  margin: 2px 0px 0px 0px;
  padding: 1px 3px;
}

.lesspad {
  margin: 0;
  width: 90px;
}

.preview-holder {
  margin-top: 36px;
  width: 225px;
  text-align: center;
}

.crop-upload {
  background-color: #fafafa;
  margin-top: 10px;
  border: 1px solid #777;
  height: 150px;
  width: 120px;
  margin-left: auto;
  margin-right: auto;
}

.crop-icon-big {
  position: relative;
  top: 65px;
}

.upload-icon-big {
  position: relative;
  top: 20px;
}

#ip_text_div,
#ip_cal_div,
#ip_ty_div,
#ip_fo_div,
#ip_st_div,
#ip_sp_div,
#ip_ct_div,
#ip_ic_div {
  position: absolute;
  display: none;
  background-color: #fff;
  border: 1px solid #afafaf;
  top: 0;
  left: 0;
  padding: 3px;
}

.ipedit-spacer {
  padding-left: 100px;
}

#filters {
  z-index: 505;
  border: 1px solid #afafaf;
  background-color: #ececec;
  padding: 5px 10px 5px 10px;
  width: 300px;
  display: none;
  position: absolute;
}

#load-search-div,
#save-search-div {
  z-index: 505;
  border: 1px solid #afafaf;
  background-color: #ececec;
  padding: 5px 10px 5px 10px;
  width: 300px;
  display: none;
  position: absolute;
}
</style>
<script language="JavaScript">
<?php
$default_search = $DB->Row('SELECT * FROM `tx_saved_searches` WHERE `identifier`=?', array('Default'));
if( $default_search && !$_REQUEST['s'] && !$_REQUEST['sponsor_id'] && !$_REQUEST['category_tag'] && !$_REQUEST['sf'] ):
?>
var default_search = <?php echo $default_search['fields']; ?>;
<?php else: ?>
var default_search = null;
<?php endif; ?>
</script>

<div id="main-content">
  <div id="centered-content" style="width: 985px;">
    <div class="heading">
      <div class="heading-icon">
        <img src="images/restore.png" border="0" alt="Load Search" title="Load Search" class="click" onclick="showRestoreForm(this)" id="rs-icon"<?php if( count($searches) < 1 ) echo ' style="display: none;"'; ?>>
        &nbsp;
        <img src="images/save-big.png" border="0" alt="Save Search" title="Save Search" class="click" onclick="showSaveForm(this)">
        &nbsp;
        <a href="index.php?r=txShGalleryTasks" class="window {title: 'Quick Tasks'}">
        <img src="images/tasks.png" border="0" alt="Quick Tasks" title="Quick Tasks"></a>
        &nbsp;
        <a href="index.php?r=txShGalleryAdd" class="window {title: 'Add Gallery'}">
        <img src="images/add.png" border="0" alt="Add Gallery" title="Add Gallery"></a>
        &nbsp;
        <a href="docs/galleries.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Search Galleries
    </div>

    <div id="infobar" class="noticebar"><div id="info"></div></div>

    <form action="ajax.php" name="search" id="search" method="POST">

    <table align="center" cellpadding="3" cellspacing="0" class="margin-top" border="0">
      <tr>
      <td align="right">
      <b>Status:</b>
      </td>
      <td colspan="2">
      <input type="checkbox" class="checkbox" name="status[]" value="unconfirmed" id="s_unconfirmed"> <label for="s_unconfirmed" class="plain-label lite">Unconfirmed</label>
      <input type="checkbox" class="checkbox" name="status[]" value="pending" id="s_pending" style="margin-left: 12px;"> <label for="s_pending" class="plain-label lite">Pending</label>
      <input type="checkbox" class="checkbox" name="status[]" value="approved" id="s_approved" style="margin-left: 12px;"> <label for="s_approved" class="plain-label lite">Approved</label>
      <input type="checkbox" class="checkbox" name="status[]" value="used" id="s_used" style="margin-left: 12px;"> <label for="s_used" class="plain-label lite">Used</label>
      <input type="checkbox" class="checkbox" name="status[]" value="holding" id="s_holding" style="margin-left: 12px;"> <label for="s_holding" class="plain-label lite">Holding</label>
      <input type="checkbox" class="checkbox" name="status[]" value="disabled" id="s_disabled" style="margin-left: 12px;"> <label for="s_disabled" class="plain-label lite">Disabled</label>
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Type:</b>
      </td>
      <td colspan="2">
      <input type="checkbox" class="checkbox" name="type[]" value="submitted" id="t_submitted"> <label for="t_submitted" class="plain-label lite">Submitted</label>
      <input type="checkbox" class="checkbox" name="type[]" value="permanent" id="t_permanent" style="margin-left: 24px;"> <label for="t_permanent" class="plain-label lite">Permanent</label>

      <span style="padding-left: 83px;">
      <b>Format:</b>
      <input type="checkbox" class="checkbox" name="format[]" id="f_pictures" value="pictures" style="margin-left: 3px;"> <label for="f_pictures" class="plain-label lite">Pictures</label>
      <input type="checkbox" class="checkbox" name="format[]" id="f_movies" value="movies" style="margin-left: 9px;"> <label for="f_movies" class="plain-label lite">Movies</label>
      </span>
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Thumb:</b>
      </td>
      <td colspan="2">
      <input type="checkbox" class="checkbox" name="preview[]" id="p_has" value="1"> <label for="p_has" class="plain-label lite">Has Preview</label>
      <input type="checkbox" class="checkbox" name="preview[]" id="p_hasnt" value="0" style="margin-left: 13px;"> <label for="p_hasnt" class="plain-label lite">No Preview</label>

      <span style="padding-left: 103px;">
      <input type="checkbox" class="checkbox" name="partners" id="partners" value="1"> <label for="partners" class="plain-label lite">Only partner galleries</label>
      <span>
      </td>
      </tr>
      <?php
      $sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors` ORDER BY `name`');

      if( count($sponsors) > 0 ):
      ?>
      <tr>
      <td align="right">
      <b>Sponsor:</b>
      </td>
      <td colspan="2">
        <select name="sponsor_id" id="sponsor_id">
          <option value="">ALL</option>
        <?php
        echo OptionTagsAdv($sponsors, $_REQUEST['sponsor_id'], 'sponsor_id', 'name', 50);
        ?>
        </select>
      </td>
      </tr>
      <?php endif; ?>
      <tr>
      <td align="right" valign="top">
      <div style="padding-top: 3px; font-weight: bold;">Categories:</div>
      </td>
      <td colspan="2">
      <?php $categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`'); ?>
        <div style="float: right;"><input type="checkbox" class="checkbox" name="cat_exclude" id="cat_exclude" value="1"> <label for="cat_exclude" class="plain-label lite">Exclude</label></div>
        <div id="category_selects">
            <div>
            <select name="categories[]">
              <option value="<?php echo MIXED_CATEGORY; ?>">MIXED</option>
            <?php
            echo OptionTagsAdv($categories, $_REQUEST['category_tag'], 'tag', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>
        </div>
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Search:</b>
      </td>
      <td colspan="2">
      <select name="field" id="s_field">
        <?php
        $search_fields = array('tx_galleries.gallery_id' => 'Gallery ID',
                               'gallery_url' => 'Gallery URL',
                               'description,keywords' => 'Description,Keywords',
                               'description' => 'Description',
                               'keywords' => 'Keywords',
                               'thumbnails' => 'Thumbnails',
                               'email' => 'E-mail Address',
                               'nickname' => 'Nickname',
                               'weight' => 'Weight',
                               'clicks' => 'Clicks',
                               'submit_ip' => 'Submit IP',
                               'gallery_ip' => 'Gallery IP',
                               'date_scanned' => 'Date Scanned',
                               'date_added' => 'Date Added',
                               'date_approved' => 'Date Approved',
                               'date_scheduled' => 'Date Scheduled',
                               'date_displayed' => 'Date Displayed',
                               'date_deletion' => 'Date of Deletion',
                               'partner' => 'Partner',
                               'administrator' => 'Administrator',
                               'has_recip' => 'Has Recip',
                               'allow_scan' => 'Allow Scan',
                               'allow_preview' => 'Allow Preview',
                               'times_selected' => 'Times Selected',
                               'used_counter' => 'Used Counter',
                               'build_counter' => 'Build Counter',
                               'tags' => 'Tags',
                               'dimensions' => 'Thumb Size',
                               'sponsor' => 'Sponsor Name',
                               'sponsor_id' => 'Sponsor ID');
        echo OptionTags($search_fields, $_REQUEST['sf']);
        $fields =& $DB->FetchAll('SELECT * FROM `tx_gallery_field_defs`');
        echo OptionTagsAdv($fields, '', 'name', 'label', 40);
        ?>
      </select>
      <select name="search_type" id="s_type">
        <option value="matches">Matches</option>
        <option value="contains">Contains</option>
        <option value="starts">Starts With</option>
        <option value="less">Less Than</option>
        <option value="greater">Greater Than</option>
        <option value="between">Between</option>
        <option value="empty">Empty</option>
      </select>
      <input type="text" name="search" id="s_search" value="<?php echo $_REQUEST['s']; ?>" onkeypress="return Search.onenter(event)" size="40" />
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Sort 1st:</b>
      </td>
      <td colspan="2">
      <select name="order" id="order">
        <option value="tx_galleries.gallery_id">Gallery ID</option>
        <option value="gallery_url">Gallery URL</option>
        <option value="description">Description</option>
        <option value="keywords">Keywords</option>
        <option value="thumbnails">Thumbnails</option>
        <option value="email">E-mail Address</option>
        <option value="nickname">Nickname</option>
        <option value="weight">Weight</option>
        <option value="clicks">Clicks</option>
        <option value="submit_ip">Submit IP</option>
        <option value="gallery_ip">Gallery IP</option>
        <option value="tx_galleries.sponsor_id">Sponsor</option>
        <option value="type">Type</option>
        <option value="format">Format</option>
        <option value="status">Status</option>
        <option value="date_scanned">Date Scanned</option>
        <option value="date_added">Date Added</option>
        <option value="date_approved">Date Approved</option>
        <option value="date_scheduled">Date Scheduled</option>
        <option value="date_displayed">Date Displayed</option>
        <option value="date_deletion">Date of Deletion</option>
        <option value="partner">Partner</option>
        <option value="administrator">Administrator</option>
        <option value="has_recip">Has Recip</option>
        <option value="allow_scan">Allow Scan</option>
        <option value="allow_preview">Allow Preview</option>
        <option value="times_selected">Times Selected</option>
        <option value="used_counter">Used Counter</option>
        <option value="build_counter">Build Counter</option>
        <?php
        echo OptionTagsAdv($fields, '', 'name', 'label', 40);
        ?>
        <option value="RAND()">Random</option>
      </select>
      <select name="direction" id="direction">
        <option value="ASC">Ascending</option>
        <option value="DESC">Descending</option>
      </select>
      </tr>
      <tr>
      <td align="right">
      <b>Sort 2nd:</b>
      </td>
      <td>
      <select name="order_next" id="order_next">
        <option value=""></option>
        <option value="tx_galleries.gallery_id">Gallery ID</option>
        <option value="gallery_url">Gallery URL</option>
        <option value="description">Description</option>
        <option value="keywords">Keywords</option>
        <option value="thumbnails">Thumbnails</option>
        <option value="email">E-mail Address</option>
        <option value="nickname">Nickname</option>
        <option value="weight">Weight</option>
        <option value="clicks">Clicks</option>
        <option value="submit_ip">Submit IP</option>
        <option value="gallery_ip">Gallery IP</option>
        <option value="tx_galleries.sponsor_id">Sponsor</option>
        <option value="type">Type</option>
        <option value="format">Format</option>
        <option value="status">Status</option>
        <option value="date_scanned">Date Scanned</option>
        <option value="date_added">Date Added</option>
        <option value="date_approved">Date Approved</option>
        <option value="date_scheduled">Date Scheduled</option>
        <option value="date_displayed">Date Displayed</option>
        <option value="date_deletion">Date of Deletion</option>
        <option value="partner">Partner</option>
        <option value="administrator">Administrator</option>
        <option value="has_recip">Has Recip</option>
        <option value="allow_scan">Allow Scan</option>
        <option value="allow_preview">Allow Preview</option>
        <option value="times_selected">Times Selected</option>
        <option value="used_counter">Used Counter</option>
        <option value="build_counter">Build Counter</option>
        <?php
        echo OptionTagsAdv($fields, '', 'name', 'label', 40);
        ?>
        <option value="RAND()">Random</option>
      </select>
      <select name="direction_next" id="direction_next">
        <option value="ASC">Ascending</option>
        <option value="DESC">Descending</option>
      </select>

      <b style="padding-left: 30px;">Per Page:</b>
      <input type="text" name="per_page" id="per_page" value="20" size="3">
      </td>
      <td align="right">
      <button type="button" onclick="$('#rand').val(Math.floor(Math.random()*999999999)); Search.search(true)">Search</button>
      </td>
      </tr>
    </table>

    <input type="hidden" name="r" value="txGallerySearch">
    <input type="hidden" name="page" id="page" value="1">
    <input type="hidden" name="rand" id="rand" value="">
    </form>

    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Galleries <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
      <div id="_pagelinks_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">
      </div>
      <div class="clear"></div>
    </div>

    <form id="results">

    <table class="tall-list" cellspacing="0">
      <thead>
        <tr>
          <td style="width: 15px;">
            <input type="checkbox" id="_autocb_" class="checkbox">
          </td>
          <td class="last">
            Gallery Data
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="2" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="2" class="last warn">
            No galleries matched your search criteria
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="2" class="last alert">
          </td>
        </tr>
      <tbody id="_tbody_">
      </tbody>
    </table>

    <div style="padding: 5px 2px 0px 2px;">
      <div id="_pagelinks_btm_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">
      </div>
      <div class="clear"></div>
    </div>

    <br />

    <div class="centered">
      <select name="function" id="function" onchange="functionChange()">
        <option value="email" class="{w: '#mail_galleries'}">E-mail</option>
        <option value="approve" class="{r: 'txGalleryApprove'}">Approve</option>
        <option value="reject" class="{r: 'txGalleryReject'}">Reject</option>
        <option value="delete" class="{r: 'txGalleryDelete'}">Delete</option>
        <option value="blacklist" class="{r: 'txGalleryBlacklist'}">Blacklist</option>
        <option value="disable" class="{r: 'txGalleryDisable'}">Disable</option>
        <option value="enable" class="{r: 'txGalleryEnable'}">Enable</option>
        <option value="export" class="{w: '#export_galleries'}">Export</option>
      </select>
      <select name="which" id="which">
        <option value="selected">Selected Galleries</option>
        <option value="matching">All Matching Galleries</option>
        <option value="all">All Galleries</option>
      </select>
      &nbsp;
      <button type="button" onclick="executeFunction()">Execute</button>

      <div id="multi_email_selector" style="display: none; padding-top: 5px;">
      <b>E-mail:</b>
      <select name="multi_email" id="multi_email">
        <option value="">NONE</option>
          <?php
          $options = '';
          $result = $DB->Query('SELECT `email_id`,`identifier` FROM `tx_rejections` ORDER BY `identifier`');
          while( $rejection = $DB->NextRow($result) )
          {
              $options .= "<option value=\"{$rejection['email_id']}\">" . htmlspecialchars(StringChop($rejection['identifier'], 30)) . "</option>\n";
          }
          $DB->Free($result);

          echo $options;
          ?>
      </select>
      </div>

      <div id="ban_reason_div" style="display: none; padding-top: 5px;">
        <b>Reason:</b> <input type="text" name="ban_reason" value="" size="40" />
      </div>
    </div>


    <div style="display: none;">
      <button class="window {title: 'E-mail Submitters', callback: bulkMail}" id="mail_galleries"></button>
      <button class="window {title: 'Bulk Edit Galleries', callback: bulkEdit}" id="edit_galleries"></button>
      <button class="window {title: 'Export Galleries', callback: bulkExport, height: 500}" id="export_galleries"></button>
    </div>

    <input type="hidden" name="search_form" id="search_form" value="">
    </form>

    <br />

    <table align="center" border="0" cellspacing="3">
      <tr>
        <td align="center" class="unconfirmed" width="75" style="border: 1px solid #AAA">
        Unconfirmed
        </td>
        <td align="center" class="pending" width="75" style="border: 1px solid #AAA">
        Pending
        </td>
        <td align="center" class="approved" width="75" style="border: 1px solid #AAA">
        Approved
        </td>
        <td align="center" class="used" width="75" style="border: 1px solid #AAA">
        Used
        </td>
        <td align="center" class="holding" width="75" style="border: 1px solid #AAA">
        Holding
        </td>
        <td align="center" class="disabled" width="75" style="border: 1px solid #AAA">
        Disabled
        </td>
      </tr>
    </table>

    <div class="page-end"></div>
  </div>
</div>

  <!-- IN PLACE EDITING FIELDS -->
  <div id="ip_fields" style="width: 100%">
    <div id="ip_text_div">
    <input type="text" id="ip_text"> <span id="charcount">0</span>
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </div>

    <div id="ip_cal_div">
    <input type="text" id="ip_cal" class="calendarSelectDate">
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </div>

    <div id="ip_st_div">
    <select id="ip_st">
      <option value="unconfirmed">Unconfirmed</option>
      <option value="pending">Pending</option>
      <option value="approved">Approved</option>
      <option value="used">Used</option>
      <option value="holding">Holding</option>
    </select>
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </div>

    <div id="ip_ty_div">
    <select id="ip_ty">
      <option value="submitted">Submitted</option>
      <option value="permanent">Permanent</option>
    </select>
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </div>

    <div id="ip_fo_div">
    <select id="ip_fo">
      <option value="pictures">Pictures</option>
      <option value="movies">Movies</option>
    </select>
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </div>

    <div id="ip_sp_div">
    <select id="ip_sp">
      <option value="">NONE</option>
      <?php
      echo OptionTagsAdv($sponsors, '', 'sponsor_id', 'name', 70);
      ?>
    </select>
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </div>

    <div id="ip_ic_div">
    <div style="text-align: right">
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </div>
    <div id="ip_ic_icons" style="height: 100px; width: 210px; overflow: auto; margin-top: 3px">
    <?php
    $icons =& $DB->FetchAll('SELECT * FROM `tx_icons`');
    foreach( $icons as $icon ):
    ?>
    <div style="padding-bottom: 3px;">
    <?php echo Checkbox('icons[]', 'checkbox', $icon['icon_id'], '');  echo " " . $icon['icon_html']; ?>
    </div>
    <?php endforeach; ?>
    </div>
    </div>

    <div id="ip_ct_div" style="padding-right: 84px;">
    <span style="position: absolute; top: 4px; right: 3px; display: block;">
    <img src="images/save.png" class="function click save">
    <img src="images/save-selected.png" class="function click save-selected">
    <img src="images/save-all.png" class="function click save-all">
    <img src="images/x.png" class="function click close">
    </span>
    </div>
  </div>

  <div style="display: none;">
  <div id="ip_category_selects">
    <div>
    <select name="categories">
      <?php
      echo OptionTagsAdv($categories, null, 'category_id', 'name', 50);
      ?>
    </select>
    <img src="images/add-small.png" onclick="addCategorySelect(this, '#ip_ct_div')" class="click-image" alt="Add Category">
    <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#ip_ct_div')" class="click-image" alt="Remove Category">
    </div>
  </div>
  </div>

  <div id="filters">
  <?php if( $C['imager'] ): ?>
    <form id="filter-form">
      <?php if( $C['imager'] == 'magick' ): ?>
      <div style="margin-bottom: 8px; position: relative; left: 50px;">
      <b>Sharpen</b> <input type="text" id="filter-sharpen" value="1x1" size="8"> &nbsp; <img src="images/check.png" border="0" class="click" id="apply-sharpen">
      </div>
      <div style="margin-bottom: 8px; position: relative; left: 44px;">
      <b>Modulate</b> <input type="text" id="filter-modulate" value="110,102,100" size="12"> &nbsp; <img src="images/check.png" border="0" class="click" id="apply-modulate">
      </div>
      <div style="margin-bottom: 8px; position: relative; left: 49px;">
      <b>Contrast</b> &nbsp;<img src="images/increase.png" border="0" class="click" id="apply-contrast"> &nbsp; <img src="images/decrease.png" border="0" class="click" id="apply-contrast-">
      </div>
      <div style="margin-bottom: 8px; position: relative; left: 41px;">
      <b>Normalize</b> &nbsp;<img src="images/check.png" border="0" class="click" id="apply-normalize">
      </div>
      <div style="margin-bottom: 8px; position: relative; left: 50px;">
      <b>Enhance</b> &nbsp;<img src="images/check.png" border="0" class="click" id="apply-enhance">
      </div>
      <?php endif; ?>
      <div style="margin-bottom: 8px; position: relative; left: 34px;">
      <b>Annotation</b>
      <select id="filter-annotation">
      <?php
      $annotations =& $DB->FetchAll('SELECT * FROM `tx_annotations`');
      echo OptionTagsAdv($annotations, null, 'annotation_id', 'identifier');
      ?>
      </select> &nbsp; <img src="images/check.png" border="0" class="click" id="apply-annotation">
      </div>

      <div style="text-align: center">
      <button type="button" id="apply-undo" style="visibility: hidden">Undo</button>
      &nbsp;
      <button type="button" id="apply-reset" style="visibility: hidden">Reset</button>
      &nbsp;
      <button type="button" id="apply-save">Save</button>
      &nbsp;
      <button type="button" id="apply-cancel">Cancel</button>
      </div>

      <input type="hidden" id="filter-gallery_id" value="">
    </form>
  <?php else: ?>
    Image editing is not available on this server
  <?php endif; ?>
  </div>

  <div id="save-search-div">
    <form id="save-search-form">
      <b>Identifier:</b> <input type="text" size="30" id="save-search-identifier">
      <img src="images/save.png" class="function click" onclick="saveSearch()">
      <img src="images/x.png" class="function click" onclick="$('#save-search-div').hide();">
    </form>
  </div>

  <div id="load-search-div">
    <form id="load-search-form">
      <select id="load-search-select">
      <?php
      echo OptionTagsAdv($searches, '', 'search_id', 'identifier', 40);
      ?>
      </select>
      <img src="images/restore-small.png" class="function click" onclick="loadSearch()">
      <img src="images/trash.png" class="function click" onclick="deleteSearch()">
      <img src="images/x.png" class="function click" onclick="$('#load-search-div').hide();">
    </form>
  </div>
</body>
</html>
