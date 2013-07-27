<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function()
  {
      Search.search(true);
  });

function bulkMail()
{
    return {href: 'index.php?&r=txShSubmitterMail&which=' + $('#which').val()};
}

function functionChange()
{
    switch($('#function').val())
    {
    case 'blacklist':
        $('#ban_reason_div:hidden').slideDown(350);
        break;
    default:
        $('#ban_reason_div:visible').slideUp(350);
        break;
    }
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/galleries-scanner-results.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Gallery Scanner Results
    </div>

    <div id="infobar" class="noticebar"><div id="info"></div></div>

    <form action="ajax.php" name="search" id="search" method="POST">

    <table align="center" cellpadding="3" cellspacing="0" class="margin-top" border="0">
      <tr>
      <td align="right">
      <b>Search:</b>
      </td>
      <td colspan="2">
      <select name="field">
        <option value="gallery_id">Gallery ID</option>
        <option value="gallery_url">Gallery URL</option>
        <option value="http_status">HTTP Status</option>
        <option value="date_scanned">Date Scanned</option>
        <option value="action">Action Taken</option>
      </select>
      <select name="search_type">
        <option value="matches">Matches</option>
        <option value="contains">Contains</option>
        <option value="starts">Starts With</option>
        <option value="less">Less Than</option>
        <option value="greater">Greater Than</option>
        <option value="between">Between</option>
        <option value="empty">Empty</option>
      </select>
      <input type="text" name="search" size="40" value="" onkeypress="return Search.onenter(event)" />
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Sort:</b>
      </td>
      <td>
      <select name="order" id="order">
        <option value="date_scanned">Date Scanned</option>
        <option value="gallery_id">Gallery ID</option>
        <option value="http_status">HTTP Status</option>
      </select>
      <select name="direction" id="direction">
        <option value="ASC">Ascending</option>
        <option value="DESC">Descending</option>
      </select>

      <b style="padding-left: 30px;">Per Page:</b>
      <input type="text" name="per_page" id="per_page" value="20" size="3">
      </td>
      <td align="right">
      <button type="button" onclick="Search.search(true)">Search</button>
      </td>
      </tr>
    </table>

    <input type="hidden" name="config_id" value="<?php echo htmlspecialchars($_REQUEST['config_id']); ?>">
    <input type="hidden" name="r" value="txScannerResultsSearch">
    <input type="hidden" name="page" id="page" value="1">
    </form>

    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Results <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
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
          <td>
            Gallery URL/Scan Result
          </td>
          <td style="width: 90px;">
            Action
          </td>
          <td style="width: 110px;">
            Date Scanned
          </td>
          <td class="last" style="width: 100px; text-align: right">
            Functions
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="7" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="7" class="last warn">
            No scanner results matched your search or no search term entered
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="7" class="last alert">
          </td>
        </tr>
      <tbody id="_tbody_">
      </tbody>
    </table>

    <div style="padding: 0px 2px 0px 2px;">
      <div id="_pagelinks_btm_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">
      </div>
      <div class="clear"></div>
    </div>

    <br />

    <div class="centered">
      <select name="function" id="function" onchange="functionChange()">
        <option value="delete" class="{r: 'txGalleryDelete'}">Delete</option>
        <option value="email" class="{w: '#mail_galleries'}">E-mail</option>
        <option value="blacklist" class="{r: 'txGalleryBlacklist'}">Blacklist</option>
        <option value="disable" class="{r: 'txGalleryDisable'}">Disable</option>
        <option value="enable" class="{r: 'txGalleryEnable'}">Enable</option>
      </select>
      <select name="which" id="which">
        <option value="selected">Selected Galleries</option>
      </select>
      &nbsp;
      <button type="button" onclick="executeFunction()">Execute</button>

      <div id="ban_reason_div" style="display: none; padding-top: 5px;">
        <b>Reason:</b> <input type="text" name="ban_reason" value="" size="40" />
      </div>
    </div>

    <div style="display: none;">
      <button class="window {title: 'E-mail Submitters', callback: bulkMail}" id="mail_galleries"></button>
    </div>

    <input type="hidden" name="search_form" id="search_form" value="">
    </form>

    <br />

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>