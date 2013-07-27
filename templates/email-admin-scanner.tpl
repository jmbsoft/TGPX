=>[subject]
TGPX Gallery Scanner Finished
=>[plain]
Greetings,

The TGPX gallery scanner has completed it's task.
{$scanned|tnumber_format} of {$total|tnumber_format} galleries were scanned.
You can view the results of this scan here:
{$config.install_url}/admin/index.php?r=txShScannerResults&config_id={$config_id|urlencode}

Cheers,
TGP Administrator
=>[html]
Greetings,<br />
<br />
The TGPX gallery scanner has completed it's task.<br />
{$scanned|tnumber_format} of {$total|tnumber_format} galleries were scanned.<br />
You can view the results of this scan here:<br />
<a href="{$config.install_url}/admin/index.php?r=txShScannerResults&config_id={$config_id|urlencode}">{$config.install_url}/admin/index.php?r=txShScannerResults&config_id={$config_id|urlencode}</a><br />
<br />
Cheers,<br />
TGP Administrator
