<tr id="<?php echo $item['report_id']; ?>">
  <td valign="top">
    <input type="checkbox" class="checkbox autocb" name="report_id[]" value="<?php echo $item['report_id']; ?>">
  </td>
  <td valign="top">
    <?php echo $item['report_id']; ?>
  </td>
  <td valign="top">
    <a href="<?php echo $item['gallery']['gallery_url']; ?>" target="_blank"><?php echo $item['gallery']['gallery_url']; ?></a><br />
    <?php echo $item['reason']; ?><br />
    <span style="color: #777">
    <?php echo date(DF_SHORT, strtotime($item['date_reported'])); ?><br />
    <?php echo $item['report_ip']; ?>
    </span>
  </td>
  <td style="text-align: right;" class="last" valign="top">
    <img src="images/search.png" width="12" height="12" alt="Scan" title="Scan" class="click-image function" onclick="openScan('<?php echo $item['gallery_id']; ?>')">
    <img src="images/blacklist.png" width="12" height="12" alt="Blacklist" title="Blacklist" class="function click-image" onclick="executeSingle('blacklist', '<?php echo $item['report_id']; ?>')">
    <img src="images/x.png" width="12" height="12" alt="Delete" title="Delete" class="function click-image" onclick="executeSingle('delete', '<?php echo $item['report_id']; ?>')">
    <img src="images/trash.png" width="12" height="12" alt="Ignore" title="Ignore" class="function click-image" onclick="executeSingle('ignore', '<?php echo $item['report_id']; ?>')">
  </td>
</tr>