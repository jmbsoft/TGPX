{assign var=$page_title value="Edit Your Account"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Edit Your Account
</div>

<div class="content-section">

<div style="font-weight: bold; text-align: center;">
<a href="{$config.install_url}/partner.php?r=overview">Account Overview</a> : 
<a href="{$config.install_url}/partner.php?r=galleries">Show Galleries</a> : 
<a href="{$config.install_url}/submit.php">Submit Galleries</a> : 
<a href="mailto:{$config.from_email}">E-mail Administrator</a> :
<a href="{$config.install_url}/partner.php?r=logout">Log Out</a>
</div>

<br />

<form method="POST" action="{$config.install_url}/partner.php">

<table width="600" cellpadding="4" cellspacing="0" align="center">

{* Display any errors encountered during the partner edit process *}
{if $errors}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="error">
Please fix the following errors:<br />
<ol style="margin: 2px; padding-left: 23px; margin-top: 5px;">
{foreach var=$error from=$errors}
<li> {$error|htmlspecialchars}<br />
{/foreach}
</ol>
</div>
</td>
</tr>
{/if}

{* Display a message confirming that the account has been updated *}
{if $updated}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="notice">
Your account information has been succesfully updated
</div>
</td>
</tr>
{/if}

<tr>
<td width="150" align="right">
<b>Name/Nickname</b>
</td>
<td>
<input type="text" size="50" name="name" id="name" value="{$partner.name|htmlspecialchars}" /><br />
</td>
</tr>

<tr>
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
<input type="text" size="50" name="email" id="email" value="{$partner.email|htmlspecialchars}" /><br />
</td>
</tr>
<tr>
<td width="150" align="right">
<b>New Password</b>
</td>
<td>
<input type="password" size="20" name="password" id="password" value="" /><br />
<span class="small">Only enter a new password if you want to change your password, otherwise leave blank</span>
</td>
</tr>
<tr>
<td width="150" align="right">
<b>Confirm New Password</b>
</td>
<td>
<input type="password" size="20" name="confirm_password" id="confirm_password" value="" />
</td>
</tr>

{* Show the user defined fields *}
{foreach var=$field from=$user_fields}
  {if $field.on_edit}
    {if $field.type == FT_CHECKBOX}
<tr>
<td width="150" align="right">
&nbsp;
</td>
<td>
{field from=$field value=$field.value}
<b><label for="{$field.name|htmlspecialchars}">{$field.label|htmlspecialchars}</label></b> 
</td>
</tr>    
    {else}
<tr>
<td width="150" align="right">
<b>{$field.label|htmlspecialchars}</b> 
</td>
<td>
{field from=$field value=$field.value}
</td>
</tr>
    {/if}
  {/if}
{/foreach}

<tr>
<td align="center" colspan="2">
<button type="submit">Update Account</button>
</td>
</tr>
</table>

<input type="hidden" name="r" value="update">
</form>

</div>

{include filename="global-footer.tpl"}