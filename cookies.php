<?php

$main = 'scripts.cmbuckley.co.uk';
$host = $_SERVER['HTTP_HOST'];
$name = $value = '';

?>
<!doctype html>
<html>
<head>
<style>
.action { border: 1px solid black; width: 50%; padding: 1em; }
.success { color: green; }
.error { color: red; }
</style>
</head>
<body>
<h1>Cookie Test</h1>
<p>Domain: <?= $host; ?></p>

<div class="action">
<?php

if (count($_COOKIE)) {
    echo "<p>Received cookies:</p><ul>\n";

    foreach ($_COOKIE as $n => $v) {
        echo "<li><code>$n = $v</code></li>\n";
    }

    echo "</ul></p>\n\n";
}
else {
    echo '<p>Received no cookies.</p>';
}

if (isset($_POST['name'], $_POST['value'])) {
    $warn = false;
    $name = $_POST['name'];
    $value = $_POST['value'];
    $secure = (isset($_POST['sec']) && $_POST['sec'] === 'on');

    if (!empty($name) && !empty($value)) {
        if (ctype_alnum($name) && ctype_alnum($value)) {
            if ($_POST['dom'] == 'none') {
                header("Set-Cookie: $name=$value; path=/; httponly" . ($secure ? '; secure' : ''));
            }
            else {
                $dom = ($_POST['dom'] == 'main' ? $main : ($_POST['dom'] == 'dot' ? ".$main" : $host));
                setcookie($name, $value, 0, '/', $dom, $secure, true);
            }

            echo '<p class="success">Sent header: <code>' . headers_list()[0] . '</code></p>';
        }
        else {
            $warn = 'name and value must be alpanumeric';
            $name = preg_replace('/[^a-z\d]/i', '', $name);
            $value = preg_replace('/[^a-z\d]/i', '', $value);
        }
    }
    else {
        $warn = 'must supply cookie name and value';
    }

    if ($warn) {
        echo '<p class="error">' . $warn . '</p>';
    }
}

?>
</div>

<form action="" method="post">
<p>Set cookie
<input name="name" pattern="[A-Za-z0-9]+" value="<?= $name; ?>" /> =
<input name="value" pattern="[A-Za-z0-9]+" value="<?= $value ?>" /> (alphanumeric)
</p>

<p>Set cookie on:
<?php if ($main != $host): ?>
<label><input type="radio" name="dom" value="sub" checked /><?= $host; ?></label>
<?php endif; ?>
<label><input type="radio" name="dom" value="main" /><?= $main; ?></label>
<label><input type="radio" name="dom" value="dot" />.<?= $main; ?></label>
<label><input type="radio" name="dom" value="none" />(unspecified)</label>
</p>

<?php if (isset($_SERVER['HTTPS'])): ?>
<p><label>Set secure-only cookie: <input type="checkbox" name="sec" /></label></p>
<?php endif; ?>

<input type="submit" />
</form>
<p>Try setting cookies on the <a href="https://<?= $main; ?>/cookies.php">main domain</a>,
either explicitly, with leading dot, or with domain unspecified.
Then try visiting subdomains (e.g. <a href="https://a.<?= $main; ?>/cookies.php">a.<?= $main; ?></a>,
<a href="https://b.<?= $main; ?>/cookies.php">b.<?= $main; ?></a>,
<a href="http://insecure.<?= $main; ?>/cookies.php">insecure.<?= $main; ?></a>) and see which cookies are sent.</p>

<p><a href="https://gist.github.com/cmbuckley/609c2ed0bbebbbbb569bb81ebedc7abd">Source</a></p>
</body>
</html>
