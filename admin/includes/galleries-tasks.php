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
        <a href="docs/galleries.html#tasks" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
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
                    <option value="gallery_url">Gallery URL</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="thumbnails">Thumbnails</option>
                    <option value="email">E-mail Address</option>
                    <option value="nickname">Nickname</option>
                    <option value="weight">Weight</option>
                    <option value="clicks">Clicks</option>
                    <option value="submit_ip">Submit IP</option>
                    <option value="gallery_ip">Gallery IP</option>
                    <option value="type">Type</option>
                    <option value="format">Format</option>
                    <option value="status">Status</option>
                    <option value="date_scanned">Date Scanned</option>
                    <option value="date_added">Date Added</option>
                    <option value="date_approved">Date Approved</option>
                    <option value="date_scheduled">Date Scheduled</option>
                    <option value="date_displayed">Date Displayed</option>
                    <option value="date_deletion">Date of Deletion</option>
                    <option value="partner">Partner</option>
                    <option value="administrator">Administrator</option>        
                    <option value="has_recip">Has Recip</option>
                    <option value="allow_scan">Allow Scan</option>
                    <option value="allow_preview">Allow Preview</option>
                    <option value="times_selected">Times Selected</option>
                    <option value="used_counter">Used Counter</option>
                    <option value="build_counter">Build Counter</option>
                    <option value="tags">Tags</option>
                    <option value="dimensions">Thumb Size</option>
                    <option value="preview_url">Thumbnail URL</option>
                    <?php
                    $fields =& $DB->FetchAll('SELECT * FROM `tx_gallery_field_defs`');        
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
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
        <input type="hidden" name="r" value="txGallerySearchAndReplace">
        </form>
        
        
        <form action="index.php" method="POST" id="sasform">        
        <fieldset>
          <legend>Search and Set</legend>
          
            <div class="fieldgroup">
                <label>Search For:</label>
                <input type="text" name="search" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label>Search Field:</label>
                <select name="field">
                    <option value="gallery_url">Gallery URL</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="thumbnails">Thumbnails</option>
                    <option value="email">E-mail Address</option>
                    <option value="nickname">Nickname</option>
                    <option value="weight">Weight</option>
                    <option value="clicks">Clicks</option>
                    <option value="submit_ip">Submit IP</option>
                    <option value="gallery_ip">Gallery IP</option>
                    <option value="type">Type</option>
                    <option value="format">Format</option>
                    <option value="status">Status</option>
                    <option value="date_scanned">Date Scanned</option>
                    <option value="date_added">Date Added</option>
                    <option value="date_approved">Date Approved</option>
                    <option value="date_scheduled">Date Scheduled</option>
                    <option value="date_displayed">Date Displayed</option>
                    <option value="date_deletion">Date of Deletion</option>
                    <option value="partner">Partner</option>
                    <option value="administrator">Administrator</option>        
                    <option value="has_recip">Has Recip</option>
                    <option value="allow_scan">Allow Scan</option>
                    <option value="allow_preview">Allow Preview</option>
                    <option value="times_selected">Times Selected</option>
                    <option value="used_counter">Used Counter</option>
                    <option value="build_counter">Build Counter</option>
                    <option value="tags">Tags</option>
                    <option value="dimensions">Thumb Size</option>
                    <option value="sponsor_id">Sponsor</option>
                    <option value="preview_url">Thumbnail URL</option>
                    <?php   
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
                </select>
            </div>
            
            <div class="fieldgroup">
                <label>Set Field:</label>
                <select name="set_field">
                    <option value="gallery_url">Gallery URL</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="thumbnails">Thumbnails</option>
                    <option value="email">E-mail Address</option>
                    <option value="nickname">Nickname</option>
                    <option value="weight">Weight</option>
                    <option value="clicks">Clicks</option>
                    <option value="submit_ip">Submit IP</option>
                    <option value="gallery_ip">Gallery IP</option>
                    <option value="type">Type</option>
                    <option value="format">Format</option>
                    <option value="status">Status</option>
                    <option value="date_scanned">Date Scanned</option>
                    <option value="date_added">Date Added</option>
                    <option value="date_approved">Date Approved</option>
                    <option value="date_scheduled">Date Scheduled</option>
                    <option value="date_displayed">Date Displayed</option>
                    <option value="date_deletion">Date of Deletion</option>
                    <option value="partner">Partner</option>
                    <option value="administrator">Administrator</option>        
                    <option value="has_recip">Has Recip</option>
                    <option value="allow_scan">Allow Scan</option>
                    <option value="allow_preview">Allow Preview</option>
                    <option value="times_selected">Times Selected</option>
                    <option value="used_counter">Used Counter</option>
                    <option value="build_counter">Build Counter</option>
                    <option value="tags">Tags</option>
                    <option value="dimensions">Thumb Size</option>
                    <option value="sponsor_id">Sponsor</option>
                    <option value="preview_url">Thumbnail URL</option>
                    <?php   
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
                </select>       
            </div>
            
            <div class="fieldgroup">
                <label>Set Value:</label>
                <input type="text" name="replace" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <button type="button" onclick="submitForm('#sasform')">Search and Set</button>
            </div>
            
        </fieldset>
        <input type="hidden" name="r" value="txGallerySearchAndSet">
        </form>
        
        <form action="index.php" method="POST" id="saaform">        
        <fieldset>
          <legend>Search and Append</legend>
          
            <div class="fieldgroup">
                <label>Search For:</label>
                <input type="text" name="search" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label>Search Field:</label>
                <select name="field">
                    <option value="gallery_url">Gallery URL</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="thumbnails">Thumbnails</option>
                    <option value="email">E-mail Address</option>
                    <option value="nickname">Nickname</option>
                    <option value="weight">Weight</option>
                    <option value="clicks">Clicks</option>
                    <option value="submit_ip">Submit IP</option>
                    <option value="gallery_ip">Gallery IP</option>
                    <option value="type">Type</option>
                    <option value="format">Format</option>
                    <option value="status">Status</option>
                    <option value="date_scanned">Date Scanned</option>
                    <option value="date_added">Date Added</option>
                    <option value="date_approved">Date Approved</option>
                    <option value="date_scheduled">Date Scheduled</option>
                    <option value="date_displayed">Date Displayed</option>
                    <option value="date_deletion">Date of Deletion</option>
                    <option value="partner">Partner</option>
                    <option value="administrator">Administrator</option>        
                    <option value="has_recip">Has Recip</option>
                    <option value="allow_scan">Allow Scan</option>
                    <option value="allow_preview">Allow Preview</option>
                    <option value="times_selected">Times Selected</option>
                    <option value="used_counter">Used Counter</option>
                    <option value="build_counter">Build Counter</option>
                    <option value="tags">Tags</option>
                    <option value="dimensions">Thumb Size</option>
                    <option value="sponsor_id">Sponsor</option>
                    <option value="preview_url">Thumbnail URL</option>
                    <?php   
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
                </select>
            </div>
            
            <div class="fieldgroup">
                <label>Append Field:</label>
                <select name="append_field">
                    <option value="gallery_url">Gallery URL</option>
                    <option value="description">Description</option>
                    <option value="keywords">Keywords</option>
                    <option value="email">E-mail Address</option>
                    <option value="nickname">Nickname</option>
                    <option value="weight">Weight</option>
                    <option value="clicks">Clicks</option>
                    <option value="submit_ip">Submit IP</option>
                    <option value="gallery_ip">Gallery IP</option>                    
                    <option value="tags">Tags</option>
                    <option value="preview_url">Thumbnail URL</option>
                    <?php   
                    echo OptionTagsAdv($fields, '', 'name', 'label', 40);
                    ?>
                </select>       
            </div>
            
            <div class="fieldgroup">
                <label>Append Value:</label>
                <input type="text" name="append" size="60" />            
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <button type="button" onclick="submitForm('#saaform')">Search and Append</button>
            </div>
            
        </fieldset>
        <input type="hidden" name="r" value="txGallerySearchAndAppend">
        </form>
        
        
        <form>
        <fieldset>
          <legend>Other Functions</legend>
            
            <div class="fieldgroup">
                <label></label>
                <img src="images/run.png" style="position: relative; top: 4px;" class="click" onclick="run('r=txGalleryResetClicks&type=submitted')"> &nbsp;Reset click counts to zero for submitted galleries 
                
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <img src="images/run.png" style="position: relative; top: 4px;" class="click" onclick="run('r=txGalleryResetClicks&type=permanent')"> &nbsp;Reset click counts to zero for permanent galleries
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <img src="images/run.png" style="position: relative; top: 4px;" class="click" onclick="run('r=txGalleryDecrementCounters')"> &nbsp;Decrement the used and build counters by one
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <img src="images/run.png" style="position: relative; top: 4px;" class="click" onclick="run('r=txGalleryRemoveUnconfirmed')"> &nbsp;Remove unconfirmed galleries that are more than 48 hours old
            </div>
            
            <div class="fieldgroup">
                <label></label>
                <img src="images/run.png" style="position: relative; top: 4px;" class="click" onclick="run('r=txPreviewCleanup')"> &nbsp;Cleanup broken thumbnail previews
            </div>         
        </fieldset>
        </form>
</div>

    

</body>
</html>
