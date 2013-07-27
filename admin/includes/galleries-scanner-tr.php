<tr id="<?php echo $item['config_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="config_id[]" value="<?php echo $item['config_id']; ?>">
  </td>
  <td>
    [<?php echo $item['config_id']; ?>]    
    <?php echo StringChopTooltip($item['identifier'], 60); ?>
  </td>
  <td id="status_<?php echo $item['config_id']; ?>">
    <?php echo $item['current_status']; ?>
  </td>
  <td id="run_<?php echo $item['config_id']; ?>">
    <?php echo $item['date_last_run'] ? date(DF_SHORT, strtotime($item['date_last_run'])) : '-'; ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShScannerHistory&config_id=<?php echo urlencode($item['config_id']); ?>">
    <img src="images/history.png" alt="History" title="History" border="0" class="function"></a>
    <a href="index.php?r=txShScannerResults&config_id=<?php echo urlencode($item['config_id']); ?>">
    <img src="images/report.png" alt="View Results" title="View Results" border="0" class="function"></a>
    <?php if( $C['shell_exec'] && !empty($C['php_cli']) ): ?>    
    <a href="javascript:void(0)" onclick="return scannerAction('start', <?php echo $item['config_id']; ?>)">
    <img src="images/start.png" alt="Start Scanner" title="Start Scanner" border="0" class="function"></a>
    <?php endif; ?>
    <a href="javascript:void(0)" onclick="return scannerAction('stop', <?php echo $item['config_id']; ?>)">
    <img src="images/stop.png" alt="Stop Scanner" title="Stop Scanner" border="0" class="function"></a>
    <a href="index.php?r=txShScannerConfigEdit&config_id=<?php echo urlencode($item['config_id']); ?>" class="window function {title: 'Edit Scanner Configuration'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['config_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>