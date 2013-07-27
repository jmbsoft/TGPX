{assign var=$page_title value="Account Password Reset"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Account Password Reset
</div>

<div class="content-section">
{if $error}
<div class="error">
{$error|htmlspecialchars}
</div>
{else}
Confirmation has been completed and your account login information has been e-mailed to {$partner.email|htmlspecialchars}
{/if}
</div>

{include filename="global-footer.tpl"}
