<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>Error</title>
  <link rel="stylesheet" href="{$config.install_url}/templates/style.css" type="text/css" />
</head>
<body>

<div class="header">Fatal Error</div>
<div class="bold">Error:</div>
<div id="error" style="padding-left: 20px;">{$error}</div>

<br />

{if $trace}
<div class="bold">Trace:</div>
<div style="padding-left: 20px;">{$trace}</div>
{else}
<div class="bold">File:</div>
<div style="padding-left: 20px;">{$file}</div>
<div class="bold">Line:</div>
<div style="padding-left: 20px;">{$line}</div>
{/if}

</body>
</html>