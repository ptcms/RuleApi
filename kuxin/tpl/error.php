<!DOCTYPE html>
<html>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <title>KXcms Framework - 系统发生错误</title>
    <style type="text/css">
        * {
            padding: 0;
            margin: 0;
        }

        html {
            overflow-y: scroll;
        }

        body {
            background: #fff;
            font: 16px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
            "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei",
            SimSun, sans-serif;
            color: #333;
        }

        img {
            border: 0;
        }

        .copyright {
            padding: 12px 48px;
            color: #999;
        }

        .copyright a {
            color: #000;
            text-decoration: none;
        }

        html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, font, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, caption {
            border: 0;
            outline: 0;
            font-size: 100%;
            vertical-align: baseline;
            background: transparent;
            margin: 0;
            padding: 0;
        }

        body {
            line-height: 1;
        }

        ol, ul {
            list-style: none;
        }

        blockquote, q {
            quotes: none;
        }

        blockquote:before, blockquote:after, q:before, q:after {
            content: none;
        }

        :focus {
            outline: 0;
        }

        ins {
            text-decoration: none;
        }

        del {
            text-decoration: line-through;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        body {
            font: normal 9pt "Verdana";
            color: #000;
            background: #fff;
        }

        h1 {
            font: normal 18pt "Verdana";
            color: #f00;
            margin-bottom: .5em;
        }

        h2 {
            font: normal 14pt "Verdana";
            color: #800000;
            margin-bottom: .5em;
        }

        h3 {
            font: bold 11pt "Verdana";
        }

        pre {
            font: normal 11pt Menlo, Consolas, "Lucida Console", Monospace;
        }

        .code pre {
            background-color: #ffe;
            margin: 0.5em 0;
            padding: 0.5em;
            line-height: 125%;
            border: 1px solid #eee;
        }

        .trace.collapsed pre {
            display: none;
        }

        .errorBox {
            padding: 12px 48px;
        }

        .big {
            font: 50px/1.8 "microsoft yahei"
        }

        .info {
            margin-bottom: 20px;
        }

        .title {
            font: bold 20px/1.8 "microsoft yahei"
        }

        .text {
            line-height: 2
        }
        pre span.error{display:block;background:#fce3e3;}
        pre span.ln{color:#999;padding-right:0.5em;border-right:1px solid #ccc;}
    </style>
</head>
<body>
<?php

/* @var array $e */

/**
 * @param $file
 * @param $errorLine
 * @param $maxLines
 * @return string
 */
function renderSourceCode($file, $errorLine, $maxLines)
{
    $errorLine--;
    if ($errorLine < 0 || ($lines = @file($file)) === false || ($lineCount = count($lines)) <= $errorLine)
        return '';

    $halfLines       = (int)($maxLines / 2);
    $beginLine       = $errorLine - $halfLines > 0 ? $errorLine - $halfLines : 0;
    $endLine         = $errorLine + $halfLines < $lineCount ? $errorLine + $halfLines : $lineCount - 1;
    $lineNumberWidth = strlen($endLine + 1);
    $output          = '';
    for ($i = $beginLine; $i <= $endLine; ++$i) {
        $isErrorLine = $i === $errorLine;
        $code        = sprintf("<span class = \"ln" . ($isErrorLine ? ' error-ln' : '') . "\">%0{$lineNumberWidth}d</span> %s", $i + 1, htmlspecialchars(str_replace("\t", ' ', $lines[$i])));
        if (!$isErrorLine)
            $output .= $code;
        else
            $output .= '<span class = "error">' . $code . '</span>';
    }
    return '
<div class = "code">
    <pre>' . $output . '</pre>
</div>
';
}

?>
<div class="errorBox">
    <b class="big">Hi,出错了！</b>
    <h1><?php echo strip_tags($e['message']); ?></h1>
    <div class="content">
        <?php if (!empty($e['file'])) : ?>
            <div class="info">
                <div class="title">
                    错误位置
                </div>
                <div class="text">
                    <p><?php echo $e['file']; ?> &#12288;LINE: <?php echo $e['line']; ?></p>
                </div>
            </div>
            <?php
            $maxLines  = 21;
            $file      = $e["file"];
            $errorLine = $e["line"];
            echo renderSourceCode($file, $errorLine, $maxLines);
        endif; ?>
        <div class="info ">
            <div class="title">TRACE</div>
            <div class="text">
                <pre><?php debug_print_backtrace(); ?></pre>
            </div>
        </div>
        <div class="info">
            <div class="title">文件加载</div>
            <div class="text">
                <pre><?php echo implode("\n", get_included_files()); ?></pre>
            </div>
        </div>
    </div>
</div>
<div class="copyright">
    <p><a title="KXCMS" href="http://www.kxcms.com">KXcms FrameWork </a><sup><?php echo KX_VERSION ?></sup> { Fast & Simple & Light MVC PHP Framework }</p>
</div>
</body>
</html>
