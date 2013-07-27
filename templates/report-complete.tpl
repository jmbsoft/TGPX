{assign var=$page_title value="Report a Gallery"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Report Recorded
</div>


<div class="content-section">
Your bad gallery link report has been recorded, and we will review it shortly.  Thank you for
taking the time to let us know about this situation.  Your report has been assigned ID number
{$report.report_id|htmlspecialchars}.  If you need to contact us for any reason about
this report, you will need to use that ID.

<br />
<br />

<a href="{$report.referrer|htmlspecialchars}" class="reg">Back to your last known location</a>
</div>

{include filename="global-footer.tpl"}