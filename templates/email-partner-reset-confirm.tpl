=>[subject]
Password Reset Confirmation
=>[plain]
Greetings,

Someone has recently requested that your account password be reset at our site.  If you did not make this request, you can ignore this e-mail message.

To reset your account password, please visit this confirmation URL:
{$config.install_url}/partner.php?r=doreset&id={$confirm_id}

Cheers,
TGP Administrator
{$config.install_url}/partner.php
=>[html]
Greetings,<br />
<br />
Someone has recently requested that your account password be reset at our site.  If you did not make this request, you can ignore this e-mail message.<br />
<br />
To reset your account password, please visit this confirmation URL:<br />
<a href="{$config.install_url}/partner.php?r=doreset&id={$confirm_id}">{$config.install_url}/partner.php?r=doreset&id={$confirm_id}</a><br />
<br />
Cheers,<br />
TGP Administrator<br />
<a href="{$config.install_url}/partner.php">{$config.install_url}/partner.php</a>
