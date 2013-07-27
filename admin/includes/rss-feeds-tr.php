<tr id="<?php echo $item['feed_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="feed_id[]" value="<?php echo $item['feed_id']; ?>">
  </td>
  <td>
    <a href="<?php echo $item['feed_url']; ?>" target="_blank"><?php echo StringChopTooltip($item['feed_url'], 75); ?></a>
  </td>
  <td>
    <?php echo $item['sponsor_id'] ? StringChopTooltip($GLOBALS['sponsors'][$item['sponsor_id']]['name'], 40) : '-'; ?>
  </td>
  <td style="text-align: center">
    <?php echo $item['date_last_import'] ? date(DF_SHORT, strtotime($item['date_last_import'])) : '-'; ?>
  </td>
  <td style="text-align: right;" class="last">
    <img src="images/expand.png" width="12" height="12" alt="Import" title="Import" class="click function" onClick="importRss('<?php echo $item['feed_id']; ?>')">
    <a href="index.php?r=txShRssFeedEdit&feed_id=<?php echo urlencode($item['feed_id']); ?>" class="window function {title: 'Edit RSS Feed'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['feed_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>