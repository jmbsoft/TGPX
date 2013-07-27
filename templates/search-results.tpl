{assign var=$page_title value="Search Results"}
{include filename="global-header.tpl"}

<script language="JavaScript" type="text/javascript">
var new_search = false;
function searchSubmit()
{
    if( new_search )
    {
        $('#p').val('1');
    }
}

function jumpPage(offset)
{
    $('#p').val(parseInt($('#p').val()) + offset);
    $('#search_form').submit();
    return false;
}
</script>

<div class="content-section">

<table align="center" width="100%" cellpadding="5" cellspacing="0">
<tr>
<td align="center" colspan="3">
<div style="padding-bottom: 10px;">
<form action="search.php" method="get" id="search_form" onsubmit="searchSubmit()">
<input type="text" name="s" size="30" value="{$search_term|htmlspecialchars}" onchange="new_search = true;" />
<select name="c" onchange="new_search = true;">
  <option value="">All Categories</option>
  {options from=$search_categories key=tag value=name selected=$search_category}
</select>
<select name="f" onchange="new_search = true;">
  <option value="">All Formats</option>
  {options from=$search_formats key=format value=name selected=$search_format}
</select>
<input type="hidden" name="p" id="p" value="{$page|htmlspecialchars}">
<input type="hidden" name="pp" value="{$per_page|htmlspecialchars}">
<input type="hidden" name="pt" value="{$picture_thumb|htmlspecialchars}">
<input type="hidden" name="mt" value="{$movie_thumb|htmlspecialchars}">
<input type="submit" value="Search">
</form>
</div>
</td>
</tr>
{if $search_too_short}
<tr>
<td>
<div class="error">
The search term you entered is too short, it must be at least 4 characters
</div>
</td>
</tr>
{else}

<tr>
<td width="100">
{if $pagination.prev}
<a href="" onclick="return jumpPage(-1)" class="link" style="text-decoration: none;">
<img src="{$config.install_url}/images/previous.png" border="0" alt="" style="position: relative; top: 3px;"> <b>Previous</b></a>
&nbsp;
{/if}
</td>
<td align="center">
<b style="font-size: 14pt;">Search results {$pagination.start|htmlspecialchars} - {$pagination.end|htmlspecialchars} of {$pagination.total|htmlspecialchars}</b>
</td>
<td align="right" width="100">
{if $pagination.next}
&nbsp;
<a href="" onclick="return jumpPage(1)" class="link" style="text-decoration: none;">
<b>Next</b> <img src="{$config.install_url}/images/next.png" border="0" alt="" style="position: relative; top: 3px;"></a>
{/if}
</td>
</tr>

<tr>
<td colspan="3">
<div style="padding-top: 10px; border-top: 2px solid #333; border-bottom: 2px solid #333;">
{foreach var=$gallery from=$results}
{if $gallery.preview_url}
<a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank"><img src="{$gallery.preview_url|htmlspecialchars}" border="0"></a><br />
{/if}
<a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank">{$gallery.description|htmlspecialchars}</a> - {$gallery.thumbnails|htmlspecialchars} {$gallery.category|htmlspecialchars} {$gallery.format|htmlspecialchars}<br /><br />
{/foreach}
</div>
</td>
</tr>

<tr>
<td width="100">
{if $pagination.prev}
<a href="" onclick="return jumpPage(-1)" class="link" style="text-decoration: none;">
<img src="{$config.install_url}/images/previous.png" border="0" alt="" style="position: relative; top: 3px;"> <b>Previous</b></a>
&nbsp;
{/if}
</td>
<td align="center">

</td>
<td align="right" width="100">
{if $pagination.next}
&nbsp;
<a href="" onclick="return jumpPage(1)" class="link" style="text-decoration: none;">
<b>Next</b> <img src="{$config.install_url}/images/next.png" border="0" alt="" style="position: relative; top: 3px;"></a>
{/if}
</td>
</tr>

{/if}
</table>

</div>

{include filename="global-footer.tpl"}