<tr id="<?php echo $item['category_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="category_id[]" value="<?php echo $item['category_id']; ?>">
  </td>
  <td>
    <?php echo StringChopTooltip($item['name'], 40); ?>
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['pics_allowed'] ? 'check' : 'x'; ?>.png">
    <?php if( $item['pics_allowed'] && $item['pics_preview_allowed'] ): ?>
    &nbsp;
    <img src="images/preview.png" alt="Prevew Allowed" title="Preview Allowed">
    <?php endif; ?>
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['movies_allowed'] ? 'check' : 'x'; ?>.png">
    <?php if( $item['movies_allowed'] && $item['movies_preview_allowed'] ): ?>
    &nbsp;
    <img src="images/preview.png" alt="Prevew Allowed" title="Preview Allowed">
    <?php endif; ?>
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['hidden'] ? 'check' : 'x'; ?>.png">
  </td>
  <td>
    <?php
    $galleries = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE MATCH(`categories`) AGAINST(? IN BOOLEAN MODE)', array($item['tag']));
    echo number_format($galleries, 0, $C['dec_point'], $C['thousands_sep']);
    ?>
  </td>
  <td>
    <?php echo $item['date_last_submit']; ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item[$_REQUEST['order']], 20); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShGallerySearch&category_tag=<?php echo urlencode($item['tag']); ?>" class="function">
    <img src="images/go.png" alt="View Galleries" title="View Galleries"></a>
    <a href="index.php?r=txShCategoryEdit&category_id=<?php echo urlencode($item['category_id']); ?>" class="window function {title: 'Edit Category'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['category_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>