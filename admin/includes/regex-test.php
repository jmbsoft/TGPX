<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<script language="JavaScript">
function testRegex()
{
    $('#errorbar').hide();
    $('#form').ajaxSubmit({dataType: 'json',
                           success: function(json)
                                    {
                                        if( json.status == JSON_FAILURE )
                                        {
                                            $('#errorbar').html(json.message).show();
                                        }
                                        else
                                        {
                                            $('#matches').html(json.matches);
                                            $('#matched').html(json.matched);
                                        }
                                    }
                           });
}

</script>

<div style="padding: 10px;">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/regex-test.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Use this interface to test your regular expressions before you add them
    </div>

    <div id="errorbar" class="alert margin-bottom" style="display: none;"></div>

    <form action="ajax.php" method="POST" id="form">

    <fieldset>
      <legend>Regular Expression Data</legend>

    <div class="fieldgroup">
        <label for="regex">Regular Expression:</label>
        <input type="text" name="regex" id="regex" size="60" />
    </div>

    <div class="fieldgroup">
        <label for="string">Test String:</label>
        <input type="text" name="string" id="string" size="60" />
    </div>

    </fieldset>

    <div id="results_fields" style="padding: 0; margin: 0;">
    <fieldset>
      <legend>Results</legend>

      <div class="fieldgroup">
        <label>Matched:</label>
        <div id="matches" style="padding-top: 3px;"></div>
      </div>

      <div class="fieldgroup">
        <label>Matched Item:</label>
        <div id="matched" style="padding-top: 3px;"></div>
      </div>
     </fieldset>
     </div>

    <div class="centered margin-top">
      <button type="button" onclick="testRegex()">Test Regex</button>
    </div>

    <input type="hidden" name="r" value="txRegexTest" />
    </form>
</div>

</body>
</html>
