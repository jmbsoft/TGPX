<tr id="<?php echo $item['page_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="page_id[]" value="<?php echo $item['page_id']; ?>">
  </td>
  <td>
    <?php echo $item['page_id']; ?>
  </td>
  <td>
    <a href="<?php echo $item['page_url']; ?>" target="_blank">
    <?php echo StringChopTooltip($item['page_url'], 80); ?>
    </a>
  </td>
  <td class="centered">
    <img src="images/<?PHP echo $item['locked'] ? 'check' : 'x'; ?>.png">
  </td>
  <td>
    <?php echo $item['build_order']; ?>
  </td>
  <td>
    <?php if( empty($item['category_id']) ): ?>
      MIXED
    <?php 
    else:
    $category_name = htmlspecialchars($GLOBALS['categories'][$item['category_id']]['name']);
    echo StringChopTooltip($category_name, 25);
    endif; ?>
  </td>
  <td style="text-align: right;" class="last">    
    <a href="" onclick="return buildSelected('<?php echo $item['page_id']; ?>', false)" class="function">
    <img src="images/build.png" width="12" height="12" alt="Build Page" title="Build Page"></a>
    <a href="" onclick="return buildSelected('<?php echo $item['page_id']; ?>', true)" class="function">
    <img src="images/build-new.png" width="12" height="12" alt="Build Page With New" title="Build Page With New"></a>
    <a href="index.php?r=txPageTemplateLoad&page_id=<?php echo urlencode($item['page_id']); ?>" class="function">
    <img src="images/html-small.png" width="12" height="12" alt="Edit Template" title="Edit Template"></a>
    <a href="index.php?r=txShPageEdit&page_id=<?php echo urlencode($item['page_id']); ?>" class="window function {title: 'Edit TGP Page', height: 375}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['page_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>