{assign var=$page_title value="Your Galleries"}
{include filename="global-header.tpl"}

<script language="JavaScript">
// Called when the page loads
$(function()
  {
      $('#direction option[@value={$direction|htmlspecialchars}]').attr({selected: 'selected'});
      $('#sort option[@value={$sort|htmlspecialchars}]').attr({selected: 'selected'});
      
      if( $.browser.msie6 )
          $.iUtil.fixPNG(document, 'images/empty.gif');
  });
  
function jumpPage(direction)
{
    var page = parseInt($('#p').val());
    
    $('#p').val(page + direction);
    $('#s').val($('#sort').val());
    $('#d').val($('#direction').val());
    $('#jumper').submit();
}

function reSort()
{
    $('#p').val(1);
    $('#s').val($('#sort').val());
    $('#d').val($('#direction').val());
    $('#jumper').submit();
}

function showDisable(gallery_id)
{
    var offset = $.iUtil.getPosition($$('gallery_'+gallery_id));
    $('#disabler').css({top: offset.y, left: offset.x}).show();
    $('#disable_id').val(gallery_id);
    $('#jumper').bind('submit', prepareDisable);
}

function hideDisable()
{
    $('#disabler').hide();
    $('#disable_id').val('');
    $('#jumper').unbind('submit');
}

function prepareDisable()
{
    if( !$('#reason').val() )
    {
        alert('Please enter a reason for disabling this gallery');
        return false;
    }
        
    if( confirm('Are you sure you want to disable this gallery?') )
    {
        $('#r').val('disable');
    }
    else
    {
        return false;
    }
}
</script>

<div class="header" style="text-align: center;">
Your Galleries
</div>


<div class="content-section">

<div style="font-weight: bold; text-align: center;">
<a href="partner.php?r=overview">Account Overview</a> : 
<a href="partner.php?r=edit">Edit Account</a> : 
<a href="{$config.install_url}/submit.php?username={$partner.username|urlencode}">Submit Galleries</a> : 
<a href="mailto:{$config.from_email}">E-mail Administrator</a> :
<a href="partner.php?r=logout">Log Out</a>
</div>

<br />
<br />

{if $disabled}
<div class="notice">
Gallery #{$disabled_id|htmlspecialchars} has been successfully disabled as requested
</div>

<br />
{/if}

{* Only show if this partner has at least one gallery in the database *}
{if count($galleries)}

{* Display pagination information *}
<div id="pagination" style="height: 18px; text-align: center;">  
  <div class="small" style="float: left;">
    Galleries <b>{$pagination.start|htmlspecialchars}</b> - <b>{$pagination.end|htmlspecialchars}</b> of <b>{$pagination.total|tnumber_format}</b>
  </div>
  
  <div style="float: right">
    {if $pagination.prev}
    <img src="{$config.install_url}/images/previous.png" border="0" alt="Previous" class="click" onclick="jumpPage(-1)">
    {else}
    <img src="{$config.install_url}/images/previous_off.png" border="0" alt="Previous">
    {/if}
    {if $pagination.next}
    <img src="{$config.install_url}/images/next.png" border="0" alt="Next" class="click" onclick="jumpPage(1)">
    {else}
    <img src="{$config.install_url}/images/next_off.png" border="0" alt="Next">
    {/if}    
  </div>
  
  <div style="position: relative; left: -30px;">
  <span class="small" style="font-weight: bold;">
  Sort By
  </span>
  <select class="small" id="sort">
    <option value="added">Date Added</option>
    <option value="approved">Date Approved</option>
    <option value="clicks">Clicks</option>
    <option value="status">Status</option>
  </select>
  <select class="small" id="direction">
    <option value="asc">Ascending</option>
    <option value="desc">Descending</option>
  </select>
  <button type="button" class="small" onclick="reSort()">Go</button>
  </div>
</div>

<div id="galleries" style="clear: both; padding-top: 5px; margin-top: 5px; border-top: 1px solid #dcdcdc;">
{* Loop through and display each of this partners galleries *}
{foreach from=$galleries var=$gallery}
<div class="gallery-div" id="gallery_{$gallery.gallery_id|htmlspecialchars}"{if $gallery.preview_height} style="height: {$gallery.preview_height|htmlspecialchars}px;"{/if}>

{if $gallery.preview_url}
<div style="float: right; padding-right: 10px;">
<img src="{$gallery.preview_url|htmlspecialchars}">
</div>
{/if}

<span class="small" style="color: #555">#{$gallery.gallery_id|htmlspecialchars}</span>
<a href="{$gallery.gallery_url|htmlspecialchars}">{$gallery.gallery_url|htmlspecialchars}</a> 
{if $gallery.status != 'disabled'}
&nbsp;
<img src="{$config.install_url}/images/uncheck.png" class="click" onclick="showDisable('{$gallery.gallery_id|htmlspecialchars}')" alt="Disable" title="Disable">
{/if}
<br />
{if $gallery.description}{$gallery.description|htmlspecialchars}<br />{/if}
<span class="small" style="color: #999">
<span style="color: #555">Added:</span> {$gallery.date_added|tdatetime}, 
<span style="color: #555">Approved:</span> {if $gallery.date_approved}{$gallery.date_approved|tdatetime}{else}-{/if}<br />
<span style="color: #555">Status:</span> {$gallery.status|ucfirst}, 
<span style="color: #555">Clicks:</span> {$gallery.clicks|tnumber_format}
</span>

</div>
{/foreach}
</div>

<form action="partner.php" method="GET" id="jumper">
<input type="hidden" name="r" id="r" value="galleries">
<input type="hidden" name="p" id="p" value="{$pagination.page|htmlspecialchars}">
<input type="hidden" name="s" id="s" value="{$sort|htmlspecialchars}">
<input type="hidden" name="d" id="d" value="{$direction|htmlspecialchars}">
<input type="hidden" name="disable_id" id="disable_id" value="">

<div id="disabler">
<span class="bold small">Reason for disabling this gallery:</span><br />
<input type="text" name="reason" id="reason" size="60">
<button type="submit">Disable</button>
<button type="button" onclick="hideDisable()">Cancel</button>
</div>

</form>

{* Show a message indicating that the user doesn't have any galleries submitted *}
{else}
<div class="notice">
You do not currently have any galleries in the database
</div>
{/if}

</div>

{include filename="global-footer.tpl"}