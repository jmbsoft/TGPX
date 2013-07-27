{define name=globaldupes value=true}
{define name=pagedupes value=false}

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  {if $page_category.meta_description}
  <meta name="description" content="{$page_category.meta_description|htmlspecialchars}">
  {/if}
  {if $page_category.meta_keywords}
  <meta name="keywords" content="{$page_category.meta_keywords|htmlspecialchars}">
  {/if}
  <title>TGP</title>
<style type="text/css">
body { font-size: 12px; font-family: Verdana; }
td { font-size: 12px; font-family: Verdana; }
.powered-by { font-size: 8pt; font-family: Verdana; text-align: center; }
.powered-by > a { font-size: 8pt; font-family: Verdana; }
</style>
</head>
<body bgcolor="#FFFFFF" text="#000000">

<div align="center">
<span style="font-size: 20pt; font-weight: bold;">TGP</span><br />
Links to {$total_thumbnails|tnumber_format} free pictures and movies!<br />
Updated {date value='today' format='m-d-Y'}

<br /><br />

<form method="get" action="{$config.install_url}/search.php">
<input type="text" name="s" size="30" value="">
<select name="c">
  <option value="">All Categories</option>
  {options from=$search_categories key=tag value=name}
</select>
<select name="f">
  <option value="">All Formats</option>
  <option value="pictures">Pictures</option>
  <option value="movies">Movies</option>
</select>
<input type="hidden" name="pp" value="20">
<input type="hidden" name="mt" value="180x130">
<input type="hidden" name="pt" value="150x150">
<input type="submit" value="Search">
</form>

<br />

</div>

<table align="center" cellpadding="5" border="0">
<tr>
{* Load 20 galleries *}
{galleries
var=$galleries
preview=true
type=any
category=MIXED
amount=20
getnew=true
allowused=true
order=date_approved
reorder=date_displayed DESC, date_approved}

{* Display loaded galleries as thumbnails in a 5 per row format *}
{foreach from=$galleries var=$gallery counter=$counter}
<td><a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank"><img src="{$gallery.preview_url|htmlspecialchars}" border="0" alt="Thumb"></a></td>
{insert location=+5 counter=$counter max=15}
</tr><tr>
{/insert}
{/foreach}
</tr>
</table>

<br />

<table align="center">
<tr>
<td valign="top">
{* Load 100 galleries *}
{galleries
var=$galleries
preview=any
type=any
category=MIXED
amount=100
getnew=true
allowused=true
order=date_approved
reorder=date_displayed DESC, date_approved}

{* Display loaded galleries as text links in 2 columns (50 per column) *}
{foreach from=$galleries var=$gallery counter=$counter}
{$gallery.date|tdate} <a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank">{$gallery.thumbnails|htmlspecialchars} {$gallery.category|htmlspecialchars}</a><br />
{if $counter == 50}
</td><td width="100"></td><td valign="top">
{/if}
{/foreach}
</td>
</tr>
</table>


<br />

<div align="center">
<b><a href="{$config.install_url}/submit.php">Submit A Gallery</a></b>
</div>

<br />

</body>
</html>