<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function() { Search.search(true); });

function clearHistory()
{    
    if( confirm('Are you sure you want to clear the page build history?') )
    {
        infoBarAjax({data: 'r=txPageBuildHistoryClear&config_id='+$('#config_id').val()});
    }
    
    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/galleries-scanner-history.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Page Build History
    </div>
    
    <div id="infobar" class="noticebar"><div id="info"></div></div>
    
    <form action="ajax.php" name="search" id="search" method="POST">
    <input type="hidden" name="config_id" id="config_id" value="<?php echo htmlspecialchars($_REQUEST['config_id']); ?>">
    <input type="hidden" name="r" value="txPageBuildHistorySearch">
    <input type="hidden" name="per_page" id="per_page" value="20">
    <input type="hidden" name="page" id="page" value="1">
    </form>
    
    <div style="padding: 20px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Results <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
      <div id="_pagelinks_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">              
      </div>
      <div class="clear"></div>
    </div>
    
    <form id="results">
    
    <table class="tall-list" cellspacing="0">
      <thead>
        <tr>
          <td class="centered" style="width: 120px;">
            Started
          </td>
          <td class="centered" style="width: 120px;">
            Finished
          </td>
          <td class="centered" style="width: 100px;">
            Pages Built
          </td>
          <td class="last">
            Error Message
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="4" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="4" class="last warn">
            There are no page build history reports
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="4" class="last alert">            
          </td>
        </tr>
      <tbody id="_tbody_">        
      </tbody>
    </table>
        
    </form>
    
    
    <div style="padding: 0px 2px 0px 2px;">
      <div id="_pagelinks_btm_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">              
      </div>
      <div class="clear"></div>
    </div>
       
    <div class="centered">
      <button type="button" onclick="clearHistory()">Clear History</button>
    </div>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>