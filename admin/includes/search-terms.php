<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function() { Search.search(true); });

function deleteAll()
{
    if( confirm('Are you sure you want to delete all of the search terms?') )
    {
        infoBarAjax({data: 'r=txSearchTermDelete&term_id=ALL'});
    }
    
    return false;
}

function deleteSelected(id)
{
    var selected = getSelected(id);
    
    if( selected.length < 1 )
    {
        alert('Please select at least one term to delete');
        return false;
    }
    
    if( confirm('Are you sure you want to delete ' + (selected.length > 1 ? 'the selected terms?' : 'this term?')) )
    {
        infoBarAjax({data: 'r=txSearchTermDelete&' + selected.serialize()});
    }
    
    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      User Search Terms
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
        <option value="term">Search Term</option>
        <option value="searches"># of Searches</option>
        <option value="date_last_search">Last Search</option>
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
        <option value="searches"># of Searches</option>
        <option value="term">Search Term</option>
        <option value="date_last_search">Last Search</option>
      </select>      
      <select name="direction" id="direction">        
        <option value="DESC">Descending</option>
        <option value="ASC">Ascending</option>
      </select>
      
      <b style="padding-left: 30px;">Per Page:</b>
      <input type="text" name="per_page" id="per_page" value="20" size="3">
      </td>
      <td align="right">
      <button type="button" onclick="Search.search(true)">Search</button>
      </td>
      </tr>
    </table>
    
    <input type="hidden" name="r" value="txSearchTermSearch">
    <input type="hidden" name="page" id="page" value="1">
    </form>
    
    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Terms <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
      <div id="_pagelinks_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">              
      </div>
      <div class="clear"></div>
    </div>
    
    <form id="results">
    
    <table class="list" cellspacing="0">
      <thead>
        <tr>
          <td style="width: 15px;">
            <input type="checkbox" id="_autocb_" class="checkbox">
          </td>
          <td>
            Term
          </td>
          <td style="width: 110px;">
            Searches
          </td>
          <td style="width: 110px;">
            Last Search
          </td>
          <td class="last" style="width: 80px; text-align: right">
            Functions
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="5" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="5" class="last warn">
            No search terms matched your search criteria
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="5" class="last alert">            
          </td>
        </tr>
      <tbody id="_tbody_">        
      </tbody>
    </table>
    
    </form>
    
    <hr>
    
    <div style="padding: 0px 2px 0px 2px;">
      <div id="_pagelinks_btm_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">              
      </div>
      <div class="clear"></div>
    </div>
       
    <div class="centered">
      <button type="button" onclick="deleteSelected()">Delete Selected</button>
      &nbsp;
      <button type="button" onclick="deleteAll()">Delete All</button>
    </div>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
