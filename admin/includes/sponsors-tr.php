<tr id="<?php echo $item['sponsor_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="sponsor_id[]" value="<?php echo $item['sponsor_id']; ?>">
  </td>
  <td>
    <?php echo StringChopTooltip($item['name'], 40); ?>
  </td>
  <td>
    <a href="<?php echo $item['url']; ?>" target="_blank">
    <?php echo StringChopTooltip($item['url'], 70); ?>
    </a>
  </td>
  <td>
    <?php
    $galleries = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `sponsor_id`=?', array($item['sponsor_id']));
    echo number_format($galleries, 0, $C['dec_point'], $C['thousands_sep']);
    ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShGallerySearch&sponsor_id=<?php echo urlencode($item['sponsor_id']); ?>" class="function">
    <img src="images/go.png" alt="View Galleries" title="View Galleries"></a>
    <a href="index.php?r=txShSponsorEdit&sponsor_id=<?php echo urlencode($item['sponsor_id']); ?>" class="window function {title: 'Edit Sponsor'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['sponsor_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>