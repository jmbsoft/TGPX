=>[subject]
Account Login Information
=>[plain]
Greetings,

Someone has recently requested that your account login information be reset.  Your current username and new password are listed below.

Username: {$partner.username}
Password: {$partner.password}

Cheers,
TGP Administrator
{$config.install_url}/partner.php
=>[html]
Greetings,<br />
<br />
Someone has recently requested that your account login information be reset.  Your current username and new password are listed below.<br />
<br />
Username: {$partner.username}<br />
Password: {$partner.password}<br />
<br />
Cheers,<br />
TGP Administrator<br />
<a href="{$config.install_url}/partner.php">{$config.install_url}/partner.php</a>
