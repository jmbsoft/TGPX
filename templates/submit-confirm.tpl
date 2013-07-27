{assign var=$page_title value="Confirm a Gallery Submission"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Confirm a Gallery Submission
</div>


<div class="content-section">

<form method="POST" action="submit.php">

<table align="center" width="50%" cellpadding="5" cellspacing="0">
{* Display any errors encountered *}
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
Enter your confirmation code in the box below to confirm your gallery submission
</td>
</tr>

<tr>
<td width="35%" align="right">
<b>Confirmation Code</b>
</td>
<td>
<input type="text" size="30" name="id" value="{$request.id|htmlspecialchars}" />
</td>
</tr>

<tr>
<td align="center" colspan="2">
<button type="submit">Confirm Submission</button>
</td>
</tr>
</table>

<input type="hidden" name="r" value="doconfirm">
</form>
</div>

{include filename="global-footer.tpl"}