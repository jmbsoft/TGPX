<?php
if( !defined('TGPX') ) die("Access denied");

$total = $DB->Count('SELECT COUNT(*) FROM `tx_galleries`');
$submitted = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `type`=?', array('submitted'));
$permanent = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `type`=?', array('permanent'));

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#submit').bind('click', function()
                                 {
                                     infoBarShow('Loading data...');

                                     var type = $('#type option:selected').text();

                                     $.ajax({type: 'POST',
                                                   url: 'ajax.php',
                                                   dataType: 'json',
                                                   data: 'r=txGalleryStats&type=' + $('#type').val(),
                                                   error: function(request, status, error)
                                                          {
                                                              infoBarUpdate('The XMLHttpRequest failed; check your internet connection and make sure your server is online', JSON_FAILURE);
                                                              $('#stattable').hide();
                                                          },
                                                   success: function(json)
                                                            {
                                                                if( json.status == JSON_SUCCESS )
                                                                {
                                                                    infoBarUpdate('Data loaded successfully', JSON_SUCCESS, 1);

                                                                    $('#results').html(json.html);
                                                                    $('#table-head').html(type);
                                                                    $('#stattable').show();
                                                                    $('#results span.tt').Tooltip();
                                                                }
                                                                else
                                                                {
                                                                    infoBarUpdate(json.message, JSON_FAILURE);
                                                                    $('#stattable').hide();
                                                                }
                                                            }});
                                 });
  });
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/galleries-statistics.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Gallery Statistics
    </div>

    <div id="infobar" class="noticebar"><div id="info"></div></div>

    <br />

    <form action="ajax.php" id="form" method="POST">

    <table align="center" cellpadding="3" cellspacing="0" border="0">
      <tr>
      <td align="right">
        <select id="type">
          <option value="click-category">Most Clicked Categories</option>
          <option value="click-sponsor">Most Clicked Sponsors</option>
          <option value="click-gallery">Most Clicked Galleries</option>
          <option value="prod-used-category">Most Productive Categories (Used counter)</option>
          <option value="prod-used-sponsor">Most Productive Sponsors (Used counter)</option>
          <option value="prod-used-gallery">Most Productive Galleries (Used counter)</option>
          <option value="prod-build-category">Most Productive Categories (Build counter)</option>
          <option value="prod-build-sponsor">Most Productive Sponsors (Build counter)</option>
          <option value="prod-build-gallery">Most Productive Galleries (Build counter)</option>
        </select>
        &nbsp;
        <button type="button" id="submit">View Stats</button>
      </td>
      </tr>
    </table>

    <input type="hidden" name="r" value="txGalleryStats">
    </form>

    <br />

    <table align="center" cellspacing="0" id="stattable" style="display: none;">
      <thead>
        <tr class="thead">
          <td colspan="2" id="table-head">
          </td>
        </tr>
      </thead>
      <tbody id="results">
      </tbody>
    </table>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
