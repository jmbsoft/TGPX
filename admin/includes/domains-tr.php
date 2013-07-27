<tr id="<?php echo $item['domain_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="domain_id[]" value="<?php echo $item['domain_id']; ?>">
  </td>
  <td>
    <?php echo $item['domain']; ?>
  </td>
  <td>
    <a href="<?php echo $item['base_url']; ?>" target="_blank"><?php echo StringChopTooltip($item['base_url'], 60); ?></a>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShDomainEdit&domain_id=<?php echo urlencode($item['domain_id']); ?>" class="window function {title: 'Edit Domain'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['domain_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>