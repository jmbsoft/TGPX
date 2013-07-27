{assign var=$page_title value="Partner Account Request"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Partner Account Request Submitted
</div>


<div class="content-section">

<table align="center" width="100%" cellpadding="3" cellspacing="2">

<tr>
<td colspan="2">
Your partner account request has been submitted and will be reviewed shortly. 
You will be contacted at the e-mail address listed below when your request has been reviewed.
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Username</b>
</td>
<td>
{$request.username|htmlspecialchars}
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Your Name</b>
</td>
<td>
{$request.name|htmlspecialchars}
</td>
</tr>

<tr>
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
{$request.email|htmlspecialchars}
</td>
</tr>


{* Show the user defined fields *}
{foreach var=$field from=$user_fields}
  {if $field.on_request}
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