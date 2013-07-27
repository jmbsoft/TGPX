=>[subject]
Partner Account Expiring
=>[plain]
Greetings,

Your partner account at our TGP is set to expire at {$partner.date_end|tdate::$config.time_format::''} on {$partner.date_end|tdate::$config.date_format::''}.
Please contact us if you would like to renew your partner account so you can continue posting
galleries at our TGP!

Cheers,
TGP Administrator
{$config.install_url}/partner.php
=>[html]
Greetings,<br />
<br />
Your partner account at our TGP is set to expire at {$partner.date_end|tdate::$config.time_format} on {$partner.date_end|tdate::$config.date_format}.<br />
Please contact us if you would like to renew your partner account so you can continue posting<br />
galleries at our TGP!<br />
<br />
Cheers,<br />
TGP Administrator<br />
<a href="{$config.install_url}/partner.php">{$config.install_url}/partner.php</a>
