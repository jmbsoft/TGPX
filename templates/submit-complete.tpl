{assign var=$page_title value="Submit a Gallery"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Gallery Submission {if $confirmed}Confirmed{else}Recorded{/if}
</div>


<div class="content-section">

<table align="center" width="100%" cellpadding="3" cellspacing="2">


<tr>
<td colspan="2" style="padding-bottom: 5px;">
{* Display message that confirmation has been completed successfully *}
{if $confirmed}
Your gallery submission has been successfully confirmed!
<br />
<br />
{/if}

Thank you for your submission!

{* This text will be shown if the gallery was auto-approved *}
{if $gallery.status == 'approved'}
Your gallery has been added to our database and will be displayed on our TGP page shortly.  


{* This text will be shown if the gallery needs to be confirmed through e-mail *}
{elseif $gallery.status == 'unconfirmed'}
Your gallery has been added to our database.  You will receive an e-mail shortly
at {$gallery.email|htmlspecialchars}.  In that e-mail you will find a link that you need to visit in order to confirm
your gallery submission.  Your gallery will not become eligible for display at our TGP until you have confirmed
your submission.



{* This text will be shown if the gallery needs to be approved *}
{elseif $gallery.status == 'pending'}
Your gallery has been added to our database, and we will examine your gallery shortly
and determine if it is acceptable for our TGP.  If your gallery is accepted, it will be displayed
on our TGP in the next few days.

{/if}

Your gallery has been assigned the ID number {$gallery.gallery_id|htmlspecialchars}.  You can
reference this ID number if you need to contact us for any reason regarding this gallery submission.
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Name/Nickname</b>
</td>
<td>
{$gallery.nickname|htmlspecialchars}
</td>
</tr>

<tr>
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
{$gallery.email|htmlspecialchars}
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Gallery URL</b>
</td>
<td>
<a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank">{$gallery.gallery_url|htmlspecialchars}</a>
</td>
</tr>

<tr>
<td width="150" align="right" valign="top">
<b>Description</b>
</td>
<td>
{$gallery.description|htmlspecialchars}
</td>
</tr>

{* Only display if user is allowed to submit keywords *}
{if $config.allow_keywords}
<tr>
<td width="150" align="right" valign="top">
<b>Keywords</b>
</td>
<td>
{$gallery.keywords|htmlspecialchars}
</td>
</tr>
{/if}

<tr>
<td width="150" align="right" valign="top">
<b>Category</b>
</td>
<td>
{$gallery.category|htmlspecialchars}
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Thumbnails</b>
</td>
<td>
{$gallery.thumbnails|htmlspecialchars}
</td>
</tr>

{if $gallery.preview_url}
<tr>
<td width="150" align="right" valign="top">
<b>Preview Thumb</b>
</td>
<td>
<img src="{$gallery.preview_url|htmlspecialchars}">
</td>
</tr>
{/if}

{* Show the user defined fields *}
{foreach var=$field from=$user_fields}
  {if $field.on_submit}
    {if $field.type == FT_CHECKBOX}
<tr>
<td width="150" align="right">
&nbsp;
</td>
<td>
{if $field.value}
<img src="{$config.install_url}/images/check.png" border="0">
{else}
<img src="{$config.install_url}/images/uncheck.png" border="0">
{/if}
<b>{$field.label|htmlspecialchars}</b> 
</td>
</tr>    
    {else}
<tr>
<td width="150" align="right">
<b>{$field.label|htmlspecialchars}</b> 
</td>
<td>
{$field.value|htmlspecialchars}
</td>
</tr>
    {/if}
  {/if}
{/foreach}
</table>

</div>

{include filename="global-footer.tpl"}