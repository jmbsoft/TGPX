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
        alert('Please select at least one category to delete');
        return false;
    }

    var plural = (selected.length > 1 ? 'the selected categories' : 'this category');
    var confirm_msg = 'Are you sure you want to delete ' + plural + "?\r\n" +
                      'All galleries that are exclusively in ' + plural + ' will also be deleted!';

    if( confirm(confirm_msg) )
    {
        infoBarAjax({data: 'r=txCategoryDelete&' + selected.serialize()});
    }

    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=txShCategoryEditDefault" class="window {title: 'Edit Default Category Settings'}">
        <img src="images/edit-large.png" border="0" alt="Edit Default Category" title="Edit Default Category"></a>
        &nbsp;
        <a href="index.php?r=txShCategoryAdd" class="window {title: 'Add Category'}">
        <img src="images/add.png" border="0" alt="Add Category" title="Add Category"></a>
        &nbsp;
        <a href="docs/categories.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Manage Categories
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
        <option value="name">Category Name</option>
        <option value="pics_allowed">Picture Galleries Allowed</option>
        <option value="pics_extensions">Picture Extensions</option>
        <option value="pics_minimum">Picture Minimum Thumbs</option>
        <option value="pics_maximum">Picture Maximum Thumbs</option>
        <option value="pics_file_size">Picture File Size</option>
        <option value="pics_preview_size">Picture Thumb Size</option>
        <option value="pics_preview_allowed">Picture Thumb Allowed</option>
        <option value="movies_allowed">Movie Galleries Allowed</option>
        <option value="movies_extensions">Movie Extensions</option>
        <option value="movies_minimum">Movie Minimum Thumbs</option>
        <option value="movies_maximum">Movie Maximum Thumbs</option>
        <option value="movies_file_size">Movie File Size</option>
        <option value="movies_preview_size">Movie Thumb Size</option>
        <option value="movies_preview_allowed">Movie Thumb Allowed</option>
        <option value="per_day">Galleries Per Day</option>
        <option value="hidden">Hidden</option>
        <option value="date_last_submit">Date of Last Submission</option>
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
      <input type="text" name="search" value="" onkeypress="return Search.onenter(event)" size="40" />
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Sort:</b>
      </td>
      <td>
      <select name="order" id="order">
        <option value="name">Category Name</option>
        <option value="pics_allowed">Picture Galleries Allowed</option>
        <option value="pics_extensions">Picture Extensions</option>
        <option value="pics_minimum">Picture Minimum Thumbs</option>
        <option value="pics_maximum">Picture Maximum Thumbs</option>
        <option value="pics_file_size">Picture File Size</option>
        <option value="pics_preview_size">Picture Thumb Size</option>
        <option value="pics_preview_allowed">Picture Thumb Allowed</option>
        <option value="movies_allowed">Movie Galleries Allowed</option>
        <option value="movies_extensions">Movie Extensions</option>
        <option value="movies_minimum">Movie Minimum Thumbs</option>
        <option value="movies_maximum">Movie Maximum Thumbs</option>
        <option value="movies_file_size">Movie File Size</option>
        <option value="movies_preview_size">Movie Thumb Size</option>
        <option value="movies_preview_allowed">Movie Thumb Allowed</option>
        <option value="per_day">Galleries Per Day</option>
        <option value="hidden">Hidden</option>
        <option value="date_last_submit">Date of Last Submission</option>
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

    <input type="hidden" name="r" value="txCategorySearch">

    <input type="hidden" name="page" id="page" value="1">
    </form>

    <div style="padding: 0px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_">Categories <b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
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
            Name
          </td>
          <td style="width: 60px; text-align: center;">
            Pictures
          </td>
          <td style="width: 60px; text-align: center;">
            Movies
          </td>
          <td style="width: 60px; text-align: center;">
            Hidden
          </td>
          <td style="width: 60px;">
            Galleries
          </td>
          <td style="width: 120px;">
            Last Submit
          </td>
          <td style="width: 120px;">
            Sorter
          </td>
          <td class="last" style="width: 80px; text-align: right">
            Functions
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="9" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="9" class="last warn">
            No categories matched your search criteria
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="9" class="last alert">
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
