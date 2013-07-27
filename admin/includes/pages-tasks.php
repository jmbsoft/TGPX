<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function() { });

function submitForm(form)
{
    if( confirm('Are you sure you want to do this?') )
    {
        $('#msg').html('Processing...');
        $('#message').show();
        $('#activity').show();
        
        $.ajax({type: 'POST',
                url: 'ajax.php',
                dataType: 'json',
                data: $(form).formSerialize(),
                error: function(request, status, error)
                       {
                           $('#activity').hide();
                           $('#msg').html(error);
                       },
                success: function(json)
                         {
                             $('#activity').hide();                        
                             $('#msg').html(json.message);
                         }
            });
    }
}


function run(data)
{
    if( confirm('Are you sure you want to do this?') )
    {
        $('#msg').html('Processing...');
        $('#message').show();
        $('#activity').show();
        
        $.ajax({type: 'POST',
                url: 'ajax.php',
                dataType: 'json',
                data: data,
                error: function(request, status, error)
                       {
                           $('#activity').hide();
                           $('#msg').html(error);
                       },
                success: function(json)
                         {
                             $('#activity').hide();                        
                             $('#msg').html(json.message);
                         }
            });
    }
}
</script>

<div style="padding: 10px;">
    
    <div>
      <div style="float: right;">
        <a href="docs/pages-manage.html#tasks" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
    </div>
       
        <div class="notice margin-bottom" id="message" style="display: none;">
          <img src="images/activity-small.gif" id="activity"> <span id="msg"></span>
        </div>       

        <form action="index.php" method="POST" id="sarform">        
        <fieldset>
          <legend>Search and Replace</legend>
          
            <div class="fieldgroup">
                <label>Search For:</label>
                <input type="text" name="search" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label>Search Field:</label>
                <select name="field">
                    <option value="page_url">Page URL</option>
                    <option value="filename">Path &amp; Filename</option>
                    <option value="tags">Tags</option>
                </select>
            </div>
            
            <div class="fieldgroup">
                <label>Replace With:</label>
                <input type="text" name="replace" size="60" />            
            </div>            
            
            <div class="fieldgroup">
                <label></label>
                <button type="button" onclick="submitForm('#sarform')">Search and Replace</button>
            </div>
            
        </fieldset>
        <input type="hidden" name="r" value="txPageSearchAndReplace">
        </form>
</div>

    

</body>
</html>
