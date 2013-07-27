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
      $('img.function').bind('click', function()
                                       {
                                           var params = $(this).data();

                                           infoBarShow('Loading data...');

                                           $.ajax({type: 'POST',
                                                   url: 'ajax.php',
                                                   dataType: 'json',
                                                   data: 'r=txGalleryBreakdown&' + params.data,
                                                   error: function(request, status, error)
                                                          {
                                                              infoBarUpdate('The XMLHttpRequest failed; check your internet connection and make sure your server is online', JSON_FAILURE);
                                                              $('#breaktable').hide();
                                                          },
                                                   success: function(json)
                                                            {
                                                                if( json.status == JSON_SUCCESS )
                                                                {
                                                                    infoBarUpdate('Data loaded successfully', JSON_SUCCESS, 1);


                                                                    $('#results').html('');
                                                                    $('#type').html(json.type);
                                                                    $('#breaktable').show();

                                                                    $.each(json.breakdown, function(key, value)
                                                                                           {
                                                                                               $('#results').append('<tr class="tbody"><td>'+ value.grouper + '</td><td>' + value.amount + '</td></tr>');
                                                                                           });
                                                                }
                                                                else
                                                                {
                                                                    infoBarUpdate(json.message, JSON_FAILURE);
                                                                    $('#breaktable').hide();
                                                                }
                                                            }});
                                       });
  });
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/galleries-breakdown.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Gallery Breakdown
    </div>

    <div id="infobar" class="noticebar"><div id="info"></div></div>

    <br />

    <table width="100%">
      <tr>
        <td width="33%" align="center" valign="top">

          <table width="80%" cellspacing="0">
            <tr class="thead">
              <td>
                Overall
              </td>
              <td>
                <?php echo number_format($total, 0, $C['dec_point'], $C['thousands_sep']); ?>
              </td>
              <td align="right">
                <img src="images/add-small.png" width="12" height="12" alt="By Date Added" title="By Date Added" class="click-image function {data: 'group=added'}">
                <img src="images/by_date.png" width="12" height="12" alt="By Date Displayed" title="By Date Displayed" class="click-image function {data: 'group=displayed'}">
                <img src="images/by_format.png" width="12" height="12" alt="By Format" title="By Format" class="click-image function {data: 'group=format'}">
                <img src="images/by_category.png" width="12" height="12" alt="By Category" title="By Category" class="click-image function {data: 'group=category'}">
                <img src="images/sponsor.png" width="12" height="12" alt="By Sponsor" title="By Sponsor" class="click-image function {data: 'group=sponsor'}">
              </td>
            </tr>

            <?php
            $result = $DB->Query('SELECT `status`,COUNT(*) AS `amount` FROM `tx_galleries` GROUP BY `status`');

            while( $breakdown = $DB->NextRow($result) ):
            ?>

            <tr class="tbody">
              <td>
                <?php echo ucfirst($breakdown['status']); ?>
              </td>
              <td>
                <?php echo number_format($breakdown['amount'], 0, $C['dec_point'], $C['thousands_sep']); ?>
              </td>
              <td align="right">
                <img src="images/add-small.png" width="12" height="12" alt="By Date Added" title="By Date Added" class="click-image function {data: 'group=added&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_date.png" width="12" height="12" alt="By Date Displayed" title="By Date Displayed" class="click-image function {data: 'group=displayed&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_format.png" width="12" height="12" alt="By Format" title="By Format" class="click-image function {data: 'group=format&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_category.png" width="12" height="12" alt="By Category" title="By Category" class="click-image function {data: 'group=category&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/sponsor.png" width="12" height="12" alt="By Sponsor" title="By Sponsor" class="click-image function {data: 'group=sponsor&status=<?php echo urlencode($breakdown['status']); ?>'}">
              </td>
            </tr>

            <?php
            endwhile;

            $DB->Free($result);
            ?>

          </table>

        </td>
        <td width="33%" align="center" valign="top">

          <table width="80%" cellspacing="0">
            <tr class="thead">
              <td>
                Submitted
              </td>
              <td>
                <?php echo number_format($submitted, 0, $C['dec_point'], $C['thousands_sep']); ?>
              </td>
              <td align="right">
                <img src="images/add-small.png" width="12" height="12" alt="By Date Added" title="By Date Added" class="click-image function {data: 'group=added&type=submitted'}">
                <img src="images/by_date.png" width="12" height="12" alt="By Date Displayed" title="By Date Displayed" class="click-image function {data: 'group=displayed&type=submitted'}">
                <img src="images/by_format.png" width="12" height="12" alt="By Format" title="By Format" class="click-image function {data: 'group=format&type=submitted'}">
                <img src="images/by_category.png" width="12" height="12" alt="By Category" title="By Category" class="click-image function {data: 'group=category&type=submitted'}">
                <img src="images/sponsor.png" width="12" height="12" alt="By Sponsor" title="By Sponsor" class="click-image function {data: 'group=sponsor&type=submitted'}">
              </td>
            </tr>

            <?php
            $result = $DB->Query('SELECT `status`,COUNT(*) AS `amount` FROM `tx_galleries` WHERE `type`=? GROUP BY `status`', array('submitted'));

            while( $breakdown = $DB->NextRow($result) ):
            ?>

            <tr class="tbody">
              <td>
                <?php echo ucfirst($breakdown['status']); ?>
              </td>
              <td>
                <?php echo number_format($breakdown['amount'], 0, $C['dec_point'], $C['thousands_sep']); ?>
              </td>
              <td align="right">
                <img src="images/add-small.png" width="12" height="12" alt="By Date Added" title="By Date Adde" class="click-image function {data: 'group=added&type=submitted&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_date.png" width="12" height="12" alt="By Date Displaye" title="By Date Displayed" class="click-image function {data: 'group=displayed&type=submitted&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_format.png" width="12" height="12" alt="By Format" title="By Format" class="click-image function {data: 'group=format&type=submitted&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_category.png" width="12" height="12" alt="By Category" title="By Category" class="click-image function {data: 'group=category&type=submitted&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/sponsor.png" width="12" height="12" alt="By Sponsor" title="By Sponsor" class="click-image function {data: 'group=sponsor&type=submitted&status=<?php echo urlencode($breakdown['status']); ?>'}">
              </td>
            </tr>

            <?php
            endwhile;

            $DB->Free($result);
            ?>

          </table>

        </td>
        <td width="33%" align="center" valign="top">

          <table width="80%" cellspacing="0">
            <tr class="thead">
              <td>
                Permanent
              </td>
              <td>
                <?php echo number_format($permanent, 0, $C['dec_point'], $C['thousands_sep']); ?>
              </td>
              <td align="right">
                <img src="images/add-small.png" width="12" height="12" alt="By Date Added" title="By Date Added" class="click-image function {data: 'group=added&type=permanent'}">
                <img src="images/by_date.png" width="12" height="12" alt="By Date Displayed" title="By Date Displayed" class="click-image function {data: 'group=displayed&type=permanent'}">
                <img src="images/by_format.png" width="12" height="12" alt="By Format" title="By Format" class="click-image function {data: 'group=format&type=permanent'}">
                <img src="images/by_category.png" width="12" height="12" alt="By Category" title="By Category" class="click-image function {data: 'group=category&type=permanent'}">
                <img src="images/sponsor.png" width="12" height="12" alt="By Sponsor" title="By Sponsor" class="click-image function {data: 'group=sponsor&type=permanent'}">
              </td>
            </tr>

            <?php
            $result = $DB->Query('SELECT `status`,COUNT(*) AS `amount` FROM `tx_galleries` WHERE `type`=? GROUP BY `status`', array('permanent'));

            while( $breakdown = $DB->NextRow($result) ):
            ?>

            <tr class="tbody">
              <td>
                <?php echo ucfirst($breakdown['status']); ?>
              </td>
              <td>
                <?php echo number_format($breakdown['amount'], 0, $C['dec_point'], $C['thousands_sep']); ?>
              </td>
              <td align="right">
                <img src="images/add-small.png" width="12" height="12" alt="By Date Added" title="By Date Added" class="click-image function {data: 'group=added&type=permanent&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_date.png" width="12" height="12" alt="By Date Displayed" title="By Date Displayed" class="click-image function {data: 'group=displayed&type=permanent&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_format.png" width="12" height="12" alt="By Format" title="By Format" class="click-image function {data: 'group=format&type=permanent&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/by_category.png" width="12" height="12" alt="By Category" title="By Category" class="click-image function {data: 'group=category&type=permanent&status=<?php echo urlencode($breakdown['status']); ?>'}">
                <img src="images/sponsor.png" width="12" height="12" alt="By Sponsor" title="By Sponsor" class="click-image function {data: 'group=sponsor&type=permanent&status=<?php echo urlencode($breakdown['status']); ?>'}">
              </td>
            </tr>

            <?php
            endwhile;

            $DB->Free($result);
            ?>
          </table>

        </td>
      </tr>
    </table>

    <br />

    <table align="center" cellspacing="0" id="breaktable" style="display: none;">
      <thead>
        <tr class="thead">
          <td colspan="2" id="type">
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
