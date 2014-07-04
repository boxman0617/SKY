?>
<!DOCTYPE html>
<html>
  <head>
    <title>SKY Framework | Oh no!</title>

    <style>
    body{margin:0;padding:0;font:14px helvetica,arial,sans-serif}
    #wrapper{position:absolute;top:0;bottom:0;width:100%}
    #stacktrace_nav,#content_wrapper{position:absolute;top:0;bottom:0;overflow-y:auto}
    #stacktrace_nav{left:0;width:496px;border-right:4px solid #6d8b8d;background-color:#daeaeb}
    #stacktrace_nav .trace{padding:15px;background-color:#effeff;cursor:pointer}
    #stacktrace_nav .trace:hover{background-color:#b0e4f2}
    #stacktrace_nav .active{background-color:#53d2eb}
    #stacktrace_nav .active:hover{background-color:#53d2eb}
    #stacktrace_nav .trace .function{font-weight:bold}
    #stacktrace_nav .active .function{color:#fff}
    #stacktrace_nav .trace .file_name .line{color:#1176d3}
    #content_wrapper{left:500px;right:0;background-color:#bdcbcc}
    #header{position:relative;background-color:#383838;height:100px;border-left:20px solid #fc4949}
    #header .error_type,#header .error_message{position:absolute}
    #header .error_type{color:#fc4949;top:23px;left:30px;font-size:18px}
    #header .error_message{color:#e7e7e7;left:30px;bottom:22px;font-size:30px}
    #code{background-color:#181818;padding:5px 0;border-top:4px solid #37a9d6;border-bottom-left-radius:10px;border-bottom-right-radius:10px;margin:0 225px 0 20px;box-shadow:0 0 5px 2px rgba(15,15,15,0.95);position:relative;z-index:100}
    #code .file_name{padding:5px 10px;background-color:#717171;color:#eaeaea;text-shadow:0 1px 0 #181818}
    #code .prettyprint li.error:before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background-color:rgba(212,73,73,0.24)}
    #code .prettyprint li.error:after{content:'';position:absolute;top:0;bottom:0;left:-55px;width:55px;background-color:rgba(212,73,73,0.24)}
    #code .prettyprint{background-color:#262626;padding:10px 2px;margin:0;border:0}
    #code .prettyprint .linenums{padding:0 0 0 55px}
    #code .prettyprint li{position:relative;list-style-type:decimal !important;color:#a8a8a8;background-color:#262626 !important;line-height:15px}
    pre.prettyprint{display:block;background-color:#333}
    pre .nocode{background-color:none;color:#000}
    pre .str{color:#8aff7d}
    pre .kwd{color:#00c4ac;font-weight:bold}
    pre .com{color:#87ceeb}
    pre .typ{color:#21d9fc}
    pre .lit{color:#f04444}
    pre .pun{color:#fffeeb}
    pre .pln{color:#e6df2b}
    pre .tag{color:#3666fa;font-weight:bold}
    pre .atn{color:#bdb76b;font-weight:bold}
    pre .atv{color:#ffa0a0}
    pre .dec{color:#98fb98}
    ol.linenums{margin-top:0;margin-bottom:0;color:#aeaeae}
    li.L0,li.L1,li.L2,li.L3,li.L5,li.L6,li.L7,li.L8{list-style-type:none}
    @media print{pre.prettyprint{background-color:none}
    pre .str,code .str{color:#060}
    pre .kwd,code .kwd{color:#006;font-weight:bold}
    pre .com,code .com{color:#600;font-style:italic}
    pre .typ,code .typ{color:#404;font-weight:bold}
    pre .lit,code .lit{color:#044}
    pre .pun,code .pun{color:#440}
    pre .pln,code .pln{color:#000}
    pre .tag,code .tag{color:#006;font-weight:bold}
    pre .atn,code .atn{color:#404}
    pre .atv,code .atv{color:#060}
    }
    #info{background-color:#808f96;padding:20px 10px 10px;position:relative;top:-10px;color:#fff}
    #info h3{margin:5px 0;border-bottom:1px solid #3673b6;padding:0 0 5px 0;color:#255c97}
    #info table{margin-bottom:20px}
    #info table:last-child{margin-bottom:0}
    #info table tr td{font-size:13px}
    #info table tr td:first-child{width:215px;font-weight:bold}
    </style>
    <link href="http://cdnjs.cloudflare.com/ajax/libs/prettify/r224/prettify.css" type="text/css">
  </head>
  <body>
    <div id="wrapper">
      <div id="stacktrace_nav">
        <?php
        foreach($trace as $k => $t)
        {
        ?>
        <div class="trace<?php echo ($k === 0) ? ' active' : ''; ?>">
          <div class="function"><?php echo $t['function']; ?></div>
          <div class="file_name"><?php echo $t['file']; ?><span class="line">:<?php echo $t['line']; ?></span></div>
        </div>
        <?php
        }
        ?>
      </div>
      <div id="content_wrapper">
        <div id="header">
          <div class="error_type"><?php echo $header['type']; ?></div>
          <div class="error_message"><?php echo $header['message']; ?></div>
        </div>
        <div id="code">
          <div class="file_name">/var/www/skycore/core/reporting/Error.class.php</div>
<pre class="prettyprint linenums:11 lang-php">
echo "This is PHP!";

function andItsNice()
{
  echo "Super nice!";
}

class Number
{
  public static $num = 0;
}

echo Number::$num + 10;
</pre>
        </div>
        <div id="info">
          <h3>Application</h3>
          <table>
            <tbody>
              <tr><td>Route</td><td>/profile/save</td></tr>
              <tr><td>Controller</td><td>Profile</td></tr>
              <tr><td>Action</td><td>Save</td></tr>
            </tbody>
          </table>

          <h3>Request</h3>
          <table>
            <tbody>
              <tr><td>URI</td><td>http://localhost:80/profile/save</td></tr>
              <tr><td>Method</td><td>GET</td></tr>
              <tr><td>Action</td><td>Save</td></tr>
              <tr><td>Port</td><td>80</td></tr>
              <tr><td>Host</td><td>localhost</td></tr>
            </tbody>
          </table>

          <h3>Controller Params</h3>
          <table>
            <tbody>
              <tr><td>test</td><td>"blah"</td></tr>
              <tr><td>hello</td><td>100</td></tr>
            </tbody>
          </table>

          <h3>$_SERVER</h3>
          <table>
            <tbody>
              <tr><td>DOCUMENT_ROOT</td><td>/demo/dev/whoops/examples</td></tr>
              <tr><td>REMOTE_ADDR</td><td>127.0.0.1</td></tr>
              <tr><td>REMOTE_PORT</td><td>42317</td></tr>
              <tr><td>SERVER_SOFTWARE</td><td>PHP 5.4.6-1ubuntu1.2 Development Server</td></tr>
              <tr><td>SERVER_PROTOCOL</td><td>HTTP/1.1</td></tr>
              <tr><td>SERVER_NAME</td><td>localhost</td></tr>
              <tr><td>SERVER_PORT</td><td>8080</td></tr>
              <tr><td>REQUEST_URI</td><td>/example-silex.php</td></tr>
              <tr><td>REQUEST_METHOD</td><td>GET</td></tr>
              <tr><td>SCRIPT_NAME</td><td>/example-silex.php</td></tr>
              <tr><td>SCRIPT_FILENAME</td><td>/demo/dev/whoops/examples/example-silex.php</td></tr>
              <tr><td>PHP_SELF</td><td>/example-silex.php</td></tr>
              <tr><td>HTTP_HOST</td><td>localhost:8080</td></tr>
              <tr><td>HTTP_CONNECTION</td><td>keep-alive</td></tr>
              <tr><td>HTTP_ACCEPT</td><td>text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8</td></tr>
              <tr><td>HTTP_USER_AGENT</td><td>Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Ubuntu Chromium/25.0.1364.160 Chrome/25.0.1364.160 Safari/537.22</td></tr>
              <tr><td>HTTP_ACCEPT_ENCODING</td><td>gzip,deflate,sdch</td></tr>
              <tr><td>HTTP_ACCEPT_LANGUAGE</td><td>en-US,en;q=0.8</td></tr>
              <tr><td>HTTP_ACCEPT_CHARSET</td><td>ISO-8859-1,utf-8;q=0.7,*;q=0.3</td></tr>
              <tr><td>REQUEST_TIME_FLOAT</td><td>1365585072.0011</td></tr>
              <tr><td>REQUEST_TIME</td><td>1365585072</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <script type="text/javascript" src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/prettify/r224/prettify.js" type="text/javascript"></script>
    <script type="text/javascript">
    $(function() {
        prettyPrint();
    });
    </script>
  </body>
</html>
