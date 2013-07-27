{assign var=$page_title value="Account Password Reset"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Account Password Reset
</div>

<div class="content-section">
Your account has been located and a confirmation e-mail message has been sent to {$partner.email|htmlspecialchars} with
instructions on how to reset your account password.  This confirmation e-mail should arrive within a few minutes and will
be valid for 24 hours.
</div>

{include filename="global-footer.tpl"}