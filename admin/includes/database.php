<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
function executeQuery()
{
    if( confirm('Are you sure you want to execute this MySQL query?') )
    {
        infoBarAjax({data: 'r=txDatabaseRawQuery&' + $('#query').serialize()}, false);
    }
    
    return false;
}
</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=txDatabaseOptimize" class="window {title: 'Database Repair and Optimize', height: 300}">
        <img src="images/repair.png" border="0" alt="Repair and Optimize" title="Repair and Optimize"></a>
        &nbsp;
        <a href="docs/database-tools.html#backup" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Database Backup and Restore
    </div>
    
    <div id="infobar" class="noticebar"><div id="info"></div></div>
    
    <?php if( !$C['shell_exec'] || empty($C['php_cli']) ): ?>
    <div class="warn margin-top">
      <a href="docs/database-tools.html#backup" target="_blank"><img src="images/help-small.png" border="0" width="12" height="12"></a> The database backup and
      restore functions will have to be run through your browser because your server either has the shell_exec() PHP function disabled or it is missing the
      CLI version of PHP.  Depending on the size of your database and the PHP restrictions set on your server, it may not be possible to complete the backup or restore
      procedure in the amount of time that the script is given to run.  Please see the documentation for possible alternatives.  
    </div>
    <?php endif; ?>
    
    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-top">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>
    
    <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to do this?')">
    
    <div class="centered margin-top" style="font-weight: bold">
      <table align="center">
      <tr>
      <td align="right">
      <b>SQL Filename</b>
      </td>
      <td align="left">
      <input type="text" name="sql-file" id="sql-file" size="30" value="sql-backup.txt" />
      </td>
      </tr>
      <tr>
      <td align="right">
      <b>Thumbs Filename</b>
      </td>
      <td align="left">
      <input type="text" name="thumbs-file" id="thumbs-file" size="30" value="thumbs-backup.txt" />
      <img src="images/x.png" border="0" class="click function" onclick="$('#thumbs-file').val('');">
      </td>
      </tr>
      <?php if( $C['shell_exec'] && !empty($C['php_cli']) && !empty($C['tar']) && !empty($C['gzip']) ): ?>
      <tr>
      <td align="right">
      <b>Archive Filename</b>
      </td>
      <td align="left">
      <input type="text" name="archive-file" id="archive-file" size="30" value="backup-<?php echo date(DF_DATE, TIME_NOW); ?>.tar.gz" />
      <img src="images/x.png" border="0" class="click function" onclick="$('#archive-file').val('');">
      </td>
      </tr>
      <?php endif; ?>
      </table>
      
      <div style="margin-top: 8px;">
      <button type="submit" onclick="$('#r').val('txDatabaseBackup')">Backup</button>
      &nbsp;&nbsp;&nbsp;
      <button type="submit" onclick="$('#r').val('txDatabaseRestore')">Restore</button>
      </div>
    </div>
    
    <input type="hidden" name="r" id="r" value="">
    </form>
    
    <br />    
    
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/database-tools.html#raw" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Raw Database Query
    </div>
       
    <form id="query_form">
    
    <div class="centered margin-top" style="font-weight: bold">
      <b>Query</b> <input type="text" name="query" id="query" size="100" value="" onkeypress="return event.keyCode!=13" /> &nbsp; 
      <button type="button" id="execute_button" onclick="executeQuery()">Execute</button>
    </div>

    </form>
    
    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
