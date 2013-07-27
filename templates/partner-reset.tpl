{assign var=$page_title value="Account Password Reset"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Account Password Reset
</div>


<div class="content-section">

<form method="POST" action="partner.php">

<table align="center" width="500" cellpadding="5" cellspacing="0">
<tr>
<td colspan="2">
Enter your e-mail address below to locate your account.  An e-mail message will be sent to this address with a link you will need to visit
in order to reset your account password.
</td>
</tr>

{* Display any errors encountered during the gallery submission process *}
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
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
<input type="text" size="50" name="email" value="{$partner.email|htmlspecialchars}" />
</td>
</tr>
<tr>
<td align="center" colspan="2">
<button type="submit">Submit</button>
</td>
</tr>
</table>

<input type="hidden" name="r" value="sendreset">
</form>
</div>

{include filename="global-footer.tpl"}