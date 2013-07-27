<?php
if( !defined('TGPX') ) die("Access denied");

$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');
include_once('includes/header.php');
include_once('includes/menu.php');
?>

<style>
div .fieldgroup label {

}
</style>

<script language="JavaScript">
$(function() 
  { 
      Search.onresults = function()
                         {
                             popUpCal.init();
                         };
      
      Search.search(true); });

function bulkMail()
{
    return {href: 'index.php?&r=txShPartnerMail&which=' + $('#which').val()};
}

function bulkEdit()
{
    return {href: 'index.php?&r=txShPartnerEditBulk&which=' + $('#which').val()};
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=txShPartnerAdd" class="window {title: 'Add Partner Account'}">
        <img src="images/add.png" border="0" alt="Add Partner Account" title="Add Partner Account"></a>
        &nbsp;
        <a href="docs/partners.html#processnew" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Review Partner Account Requests
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
        <option value="tx_partners.username">Username</option>
        <option value="name">Name</option>
        <option value="email">E-mail Address</option>
        <option value="date_added">Date Added</option>
        <?php
        $fields =& $DB->FetchAll('SELECT * FROM `tx_partner_field_defs`');        
        echo OptionTagsAdv($fields, '', 'name', 'label', 40);
        ?>    
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
        <option value="tx_partners.username">Username</option>
        <option value="name">Name</option>
        <option value="email">E-mail Address</option>
        <option value="date_added">Date Added</option>
        <?php    
        echo OptionTagsAdv($fields, '', 'name', 'label', 40);
        ?>   
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
    
    <input type="hidden" name="r" value="txPartnerReviewSearch">
    <input type="hidden" name="status" value="pending">
    <input type="hidden" name="page" id="page" value="1">
    </form>
    
    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Accounts <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
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
            Account Details
          </td>
          <td class="last">
            &nbsp;
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="3" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="3" class="last warn">
            No accounts matched your search criteria
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="3" class="last alert">            
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
      <select id="function">
        <option value="approve" class="{r: 'txPartnerApprove'}">Approve</option>
        <option value="reject" class="{r: 'txPartnerReject'}">Reject</option>
      </select>
      <select id="which" name="which">
        <option value="selected">Selected Partners</option>
        <option value="matching">All Matching Partners</option>
      </select>
      &nbsp;
      <button type="button" onclick="executeFunction()">Execute</button>
    </div>
        
    <input type="hidden" name="search_form" id="search_form" value="">
    </form>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
