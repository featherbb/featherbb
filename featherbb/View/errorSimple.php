<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Error / FeatherBB</title>
    <style type="text/css">
        <!--
        BODY {margin: 10% 20% auto 20%; font: 10px Verdana, Arial, Helvetica, sans-serif; background-color: #f9f9f9}
        #errorbox {border: 1px solid #029be5}
        H2 {margin: 0; color: #fff; background-color: #029be5; font-size: 1.1em; padding: 5px 4px}
        #errorbox DIV {padding: 6px 5px; background-color: #fff}
        -->
    </style>
</head>
<body>

<div id="errorbox">
    <h2>An error was encountered</h2>
    <div>
        <?php if (!$error['hide']) : ?>
        <strong>File:</strong> <?= $e->getFile() ?><br />
        <strong>Line:</strong> <?= $e->getLine() ?><br /><br />
        <?php endif; ?>
        <strong>FeatherBB reported</strong>: <?= $error['message'] ?>
    </div>
</div>

</body>
</html>
