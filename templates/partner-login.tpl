{assign var=$page_title value="Partner Account Login"}
{include filename="global-header.tpl"}

<div class="header" style="text-align: center;">
Partner Account Login
</div>


<div class="content-section">

<form method="POST" action="partner.php">

<table align="center" width="50%" cellpadding="5" cellspacing="0">
{* Display any errors encountered during the login process *}
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


{if $logged_out}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="notice">
You have been successfully logged out of your partner account
</div>
</td>
</tr>
{/if}

<tr>
<td colspan="2">
Enter your partner account username and password to login.  Through this interface you will be able to 
view the current galleries you have submitted and edit your account.
</td>
</tr>

<tr>
<td width="35%" align="right">
<b>Username</b>
</td>
<td>
<input type="text" size="30" name="login_username" value="{$login.login_username|htmlspecialchars}" />
</td>
</tr>

<tr>
<td width="35%" align="right">
<b>Password</b>
</td>
<td>
<input type="password" size="30" name="login_password" value="" />
</td>
</tr>

<tr>
<td align="center" colspan="2">
<a href="partner.php?r=reset" class="small">Forgot Your Password?</a>
</td>
</tr>

<tr>
<td align="center" colspan="2">
<button type="submit">Login</button>
</td>
</tr>
</table>

<input type="hidden" name="r" value="overview">
</form>
</div>

{include filename="global-footer.tpl"}