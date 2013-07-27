{assign var=$page_title value="Partner Account Overview"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Partner Account Overview
</div>


<div class="content-section">

<div style="font-weight: bold; text-align: center;">
<a href="partner.php?r=edit">Edit Account</a> : 
<a href="partner.php?r=galleries">Show Galleries</a> : 
<a href="{$config.install_url}/submit.php">Submit Galleries</a> : 
<a href="mailto:{$config.from_email}">E-mail Administrator</a> :
<a href="partner.php?r=logout">Log Out</a>
</div>

<br />

<table width="300" cellpadding="4" cellspacing="0" align="center">
<tr>
<td class="line-bottom">
<b>Date Created</b>
</td>
<td class="line-bottom">
{$partner.date_added|tdatetime}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Last Submission</b>
</td>
<td class="line-bottom">
{if $partner.date_last_submit}
{$partner.date_last_submit|tdatetime}
{else}
Never
{/if}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Galleries Allowed Per Day</b>
</td>
<td class="line-bottom">
{if $partner.per_day == -1}
Unlimited
{else}
{$partner.per_day|tnumber_format}
{/if}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Account Active On</b>
</td>
<td class="line-bottom">
{if $partner.date_start}
{$partner.date_start|tdatetime}
{else}
Always
{/if}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Account Expires On</b>
</td>
<td class="line-bottom">
{if $partner.date_end}
{$partner.date_end|tdatetime}
{else}
Never
{/if}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Galleries In Database</b>
</td>
<td class="line-bottom">
{$stats.galleries|tnumber_format}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Clicks on Galleries</b>
</td>
<td class="line-bottom">
{$stats.clicks|tnumber_format}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Unconfirmed Galleries</b>
</td>
<td class="line-bottom">
{$stats.unconfirmed|tnumber_format}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Pending Galleries</b>
</td>
<td class="line-bottom">
{$stats.pending|tnumber_format}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Approved Galleries</b>
</td>
<td class="line-bottom">
{$stats.approved|tnumber_format}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Used Galleries</b>
</td>
<td class="line-bottom">
{$stats.used|tnumber_format}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Held Galleries</b>
</td>
<td class="line-bottom">
{$stats.holding|tnumber_format}
</td>
</tr>
<tr>
<td class="line-bottom">
<b>Disabled Galleries</b>
</td>
<td class="line-bottom">
{$stats.disabled|tnumber_format}
</td>
</tr>
</table>

</div>

{include filename="global-footer.tpl"}