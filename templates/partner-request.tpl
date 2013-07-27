{assign var=$page_title value="Partner Account Request"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Partner Account Request
</div>


<div class="content-section">

<form method="POST" action="partner.php">

<table align="center" width="100%" cellpadding="3" cellspacing="2">

{* Display any errors encountered during the partner request submission process *}
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


<tr>
<td colspan="2">
To request a partner account for gallery submissions, please fill out
the form below. You will be contacted after your request is reviewed.
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Select a Username</b>
</td>
<td>
<input type="text" size="20" name="username" id="username" value="{$request.username|htmlspecialchars}" />
</td>
</tr>


<tr>
<td width="150" align="right">
<b>Your Name</b>
</td>
<td>
<input type="text" size="40" name="name" value="{$request.name|htmlspecialchars}" />
</td>
</tr>

<tr>
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
<input type="text" size="50" name="email" value="{$request.email|htmlspecialchars}" />
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

{* Display verification code if required *}
{if $config.request_captcha}
<tr id="verification">
<td width="150" align="right">
<b>Verification</b>
</td>
<td>
<div>
<img src="{$config.install_url}/code.php" border="0" style="vertical-align: middle;">
<input type="text" name="captcha" size="20" style="vertical-align: middle;" />
</div>
<span class="small">Copy the characters from the image into the text box for verification</span>
</td>
</tr>
{/if}
<tr>
<td align="center" colspan="2">
<button type="submit">Submit Request</button>
</td>
</tr>

</table>

<input type="hidden" name="r" value="submitrequest">
</form>
</div>

{include filename="global-footer.tpl"}