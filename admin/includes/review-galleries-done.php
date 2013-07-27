
<span style="font-size: 9pt; font-weight: bold;">
<?php if( $remain || $pending ): ?>
  You have completed reviewing the galleries that matched the options you specified
  <br />
  <br />

  <?php if( $remain ): ?>
  You can go back and <a href="" onclick="return restartFromBegin()">review the <?php echo number_format($remain, 0, $C['dec_point'], $C['thousands_sep']); ?> galler<?php echo $remain == 1 ? 'y' : 'ies'; ?> that you skipped</a>
  <br /><br />
  <?php endif; ?>

  <?php if( $pending ): ?>
  You can start <a href="review.php">reviewing the <?php echo number_format($pending, 0, $C['dec_point'], $C['thousands_sep']); ?> remaining pending galler<?php echo $pending == 1 ? 'y' : 'ies'; ?></a>
  <br /><br />
  <?php endif; ?>

<?php else: ?>
All galleries have been reviewed!
<?php endif; ?>
</span>