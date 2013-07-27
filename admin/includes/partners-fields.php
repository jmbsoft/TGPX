<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>
<script language="JavaScript">
$(function() { Search.search(true); });

function deleteSelected(id)
{
    var selected = getSelected(id);

    if( selected.length < 1 )
    {
        alert('Please select at least one user defined field to delete');
        return false;
    }

    if( confirm('Are you sure you want to delete ' + (selected.length > 1 ? 'the selected user defined fields?' : 'this user defined field?')) )
    {
        infoBarAjax({data: 'r=txPartnerFieldDelete&' + selected.serialize()});
    }

    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">

    <div class="heading margin-bottom">
      <div class="heading-icon">
        <a href="index.php?r=txShPartnerFieldAdd" class="window {title: 'Add Partner Field'}"><img src="images/add.png" border="0" alt="Add Partner Field" title="Add Partner Field"></a>
        &nbsp;
        <a href="docs/partner-fields.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      User Defined Partner Fields
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
        <option value="name">Field Name</option>
        <option value="label">Label</option>
        <option value="type">Field Type</option>
        <option value="tag_attributes">Tag Attributes</option>
        <option value="options">Options</option>
        <option value="validation_message">Validation Message</option>
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
        <option value="name">Field Name</option>
        <option value="label">Label</option>
        <option value="type">Field Type</option>
        <option value="tag_attributes">Tag Attributes</option>
        <option value="options">Options</option>
        <option value="validation_message">Validation Message</option>
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

    <input type="hidden" name="r" value="txPartnerFieldSearch">
    <input type="hidden" name="page" id="page" value="1">
    </form>

    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Fields <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
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
          <td style="width: 130px;">
            Field
          </td>
          <td>
            Label
          </td>
          <td style="width: 65px;">
            Type
          </td>
          <td style="width: 80px; text-align: center;">
            On Request
          </td>
          <td style="width: 70px; text-align: center;">
            Req Only
          </td>
          <td style="width: 50px; text-align: center;">
            On Edit
          </td>
          <td class="last" style="width: 50px; text-align: right">
            Functions
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="10" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="10" class="last warn">
            No partner fields match your search criteria
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="10" class="last alert">
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
      <button type="button" onclick="deleteSelected()">Delete</button>
    </div>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
