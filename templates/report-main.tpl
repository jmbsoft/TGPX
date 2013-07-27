{assign var=$page_title value="Report a Gallery"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Report a Gallery
</div>


<div class="content-section">

<form method="POST" action="{$config.install_url}/report.php">

<table align="center" width="100%" cellpadding="3" cellspacing="2">

{* Display any errors encountered during the report submission process *}
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
Use the form below to report a broken gallery link or a gallery that is breaking
our rules.  You do not need to tell us the URL, just give a short description of what
the gallery is doing to break the rules, or simply enter 'This is a broken link' if
the gallery link no longer works.

<br />
<br />

If we determine that your report is correct, we will remove the offending gallery and
possibly ban it from our TGP. Thank you for helping to keep our TGP top quality!
</td>
</tr>


<tr>
<td width="220" align="right">
<b>Gallery URL</b>
</td>
<td>
<a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank">{$gallery.gallery_url|htmlspecialchars}</a>
</td>
</tr>

{if $gallery.description}
<tr>
<td width="220" align="right" valign="top">
<b>Description</b>
</td>
<td>
{$gallery.description|htmlspecialchars}
</td>
</tr>
{/if}

<tr>
<td width="220" align="right" valign="top">
<b>Report Reason</b>
</td>
<td>
<textarea name="reason" rows="5" cols="90">{$report.reason|htmlspecialchars}</textarea>
</td>
</tr>


{* Display verification code if required *}
{if $config.report_captcha}
<tr id="verification">
<td width="220" align="right">
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
<button type="submit">Submit Report</button>
</td>
</tr>

</table>

<input type="hidden" name="id" value="{$gallery.gallery_id|htmlspecialchars}">
<input type="hidden" name="referrer" value="{$referrer|htmlspecialchars}">
</form>
</div>

{include filename="global-footer.tpl"}