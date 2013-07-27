<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function() 
  {
      Search.search(true);
      setTimeout('updateStatus()', 5000);
  });

function deleteSelected(id)
{
    var selected = getSelected(id);
    
    if( selected.length < 1 )
    {
        alert('Please select at least one configuration to delete');
        return false;
    }
    
    if( confirm('Are you sure you want to delete ' + (selected.length > 1 ? 'the selected configurations?' : 'this configuration?')) )
    {
        infoBarAjax({data: 'r=txScannerConfigDelete&' + selected.serialize()});
    }
    
    return false;
}

function scannerAction(action, id)
{
    if( confirm('Are you sure you want to do this?') )
    {
        var func = (action == 'start' ? 'txScannerStart' : 'txScannerStop');        
        infoBarAjax({data: 'r='+func+'&config_id=' + id}, false);
    }
    
    return false;
}

function updateStatus()
{
    $('#status_activity').show();

    $.ajax({type: 'POST',
            url: 'ajax.php',
            dataType: 'json',
            data: 'r=txScannerStatus',
            timeout: 10000,
            error: function(request, status, error) { setTimeout('updateStatus()', 5000); },
            success: function(json)
                     {
                        $('#status_activity').hide();
                        
                        if( json.status == JSON_SUCCESS )
                        {
                            $.each(json.configs, function(i, config)
                                                 {
                                                     $('#status_'+config.config_id).html(config.current_status);
                                                     $('#run_'+config.config_id).html(config.date_last_run);
                                                 });
                        }
                        
                        setTimeout('updateStatus()', 5000);
                     }
            });
}

</script>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=txShScannerConfigAdd" class="window {title: 'Add Scanner Configuration'}">
        <img src="images/add.png" border="0" alt="Add Scanner Configuration" title="Add Scanner Configuration"></a>
        &nbsp;
        <a href="docs/galleries-scanner.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Gallery Scanner Configurations
    </div>
    
    <div id="infobar" class="noticebar"><div id="info"></div></div>
    
    <?php if( !$C['shell_exec'] || empty($C['php_cli']) ): ?>
    <div class="warn margin-top margin-bottom">
      <a href="docs/galleries-scanner.html" target="_blank"><img src="images/help-small.png" border="0" width="12" height="12"></a> The gallery scanner cannot be
      started through the control panel because your server either has the shell_exec() PHP function disabled or it is missing the CLI version of PHP.  Please see 
      the documentation for possible alternatives.  
    </div>
    <?php endif; ?>
    
    <form action="ajax.php" name="search" id="search" method="POST">  
    <input type="hidden" name="r" value="txScannerConfigSearch">
    <input type="hidden" name="per_page" id="per_page" value="20">
    <input type="hidden" name="page" id="page" value="1">
    </form>
    
    <div style="padding: 20px 2px 5px 2px;">
      <div style="float: left; display: none;" id="_matches_"><b id="_start_">?</b> - <b id="_end_">?</b> of <b id="_total_">?</b></div>
      <div id="_pagelinks_" style="float: right; line-height: 0px; padding: 2px 0px 0px 0px;">              
      </div>
      <div class="clear"></div>
    </div>
    
    <form id="results">
    
    <table class="list" cellspacing="0">
      <thead>
        <tr>
          <td style="width: 15px;">
            <input type="checkbox" id="_autocb_" class="checkbox">
          </td>
          <td>
            Identifier
          </td>
          <td style="width: 300px;">
            Status
            <span id="status_activity" style="display: none; padding-left: 5px;">
            <img src="images/activity-small.gif">
            </span>
          </td>
          <td style="width: 110px;">
            Last Run
          </td>
          <td class="last" style="width: 110px; text-align: right">
            Functions
          </td>
        </tr>
      </thead>
        <tr id="_activity_">
          <td colspan="7" class="last centered">
            <img src="images/activity.gif" border="0" width="16" height="16" alt="Working...">
          </td>
        </tr>
        <tr id="_none_" style="display: none;">
          <td colspan="7" class="last warn">
            No gallery scanner configurations have been created
          </td>
        </tr>
        <tr id="_error_" style="display: none;">
          <td colspan="7" class="last alert">            
          </td>
        </tr>
      <tbody id="_tbody_">        
      </tbody>
    </table>
    
    </form>
    
    <hr>
       
    <div class="centered">
      <button type="button" onclick="deleteSelected()">Delete</button>
    </div>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>