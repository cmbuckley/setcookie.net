<?php

$main = 'setcookie.net';
$host = $_SERVER['HTTP_HOST'];
$https = (isset($_SERVER['HTTP_X_FORWARDED_SSL']) ? $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on' : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'));
$name = $value = '';

function samesite() {
    $ss = (isset($_POST['ss']) ? $_POST['ss'] : 'notset');
    if (in_array($ss, ['none', 'lax', 'strict'])) { return ucfirst($ss); }
}

function sentheader() {
    return current(array_filter(headers_list(), function ($h) {
        return stripos($h, 'Set-Cookie') !== false;
    }));
}

// make sure it's using the main URL
if (getenv('FLY_APP_NAME') && strpos($host, $main) === false) {
    header("Location: https://$main");
    return;
}

if (isset($_POST['name'], $_POST['value'])) {
    $warn = $message = '';
    $unsafe = '/[^a-z\d_-]/i'; // purposefully a bit stricter than the spec
    $rawName = $_POST['name'];
    $rawValue = $_POST['value'];
    $name = preg_replace($unsafe, '', $rawName);
    $value = preg_replace($unsafe, '', $rawValue);
    $secure = (isset($_POST['sec']) && $_POST['sec'] === 'on');
    $samesite = samesite();

    if (!empty($name) && !empty($value)) {
        if ($rawName === $name && $rawValue === $value) {
            if (!isset($_POST['dom']) || $_POST['dom'] == 'none') {
                $dom = '';
            } elseif ($_POST['dom'] == 'main') {
                $dom = $main;
            } elseif ($_POST['dom'] == 'dot') {
                $dom = ".$main";
            } else {
                $dom = $host;
            }

            $opts = [
                'expires'  => 0,
                'path'     => '/',
                'domain'   => $dom,
                'secure'   => $secure,
                'httponly' => true,
            ];
            if (isset($samesite)) { $opts['samesite'] = $samesite; }
            setcookie($name, $value, $opts);

            $message = '<p class="success">Sent header: <code>' . sentheader() . '</code></p>';
        }
        else {
            $warn = 'name and value must be alphanumeric or one of <samp>_-</samp>';
        }
    }
    else {
        $warn = 'must supply cookie name and value';
    }
}

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
    if ($warn) {
        echo '<p class="error">' . $warn . '</p>';
    } else {
        echo $message;
    }
}

?>
</div>

<form action="" method="post">
<p>Set cookie
<input name="name" pattern="[A-Za-z0-9_-]+" value="<?= $name; ?>" /> =
<input name="value" pattern="[A-Za-z0-9_-]+" value="<?= $value ?>" /> (alphanumeric or <kbd>_-</kbd>; restricted character set compared to <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#attributes">spec</a>)
</p>

<p>Set cookie on:
<?php if ($main != $host): ?>
<label><input type="radio" name="dom" value="sub" checked /><?= $host; ?></label>
<?php endif; ?>
<label><input type="radio" name="dom" value="main" /><?= $main; ?></label>
<label><input type="radio" name="dom" value="dot" />.<?= $main; ?></label>
<label><input type="radio" name="dom" value="none" checked />(unspecified)</label>
</p>

<p>SameSite:
<?php if ($https): ?>
<label><input type="radio" name="ss" value="none" />None</label>
<?php endif; ?>
<label><input type="radio" name="ss" value="lax" />Lax</label>
<label><input type="radio" name="ss" value="strict" />Strict</label>
<label><input type="radio" name="ss" value="notset" checked />(not set)</label> <i>(<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite#cookies_without_samesite_default_to_samesitelax">behaves like Lax</a> in most browsers, but see <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite#browser_compatibility">exceptions</a>)</i>
</p>

<?php if ($https): ?>
<p><label>Set secure-only cookie: <input type="checkbox" name="sec" /></label></p>
<?php endif; ?>

<p>Will result in the following cookie: <samp /></p>
<input type="submit" />
</form>
<p>Try setting cookies on the <a href="https://<?= $main; ?>">main domain</a>,
either explicitly, with leading dot, or with domain unspecified.
Then try visiting different URLs (e.g. <a href="https://a.<?= $main; ?>">a.<?= $main; ?></a>,
<a href="https://b.<?= $main; ?>">b.<?= $main; ?></a>,
<a href="http://<?= $main; ?>">http instead of https</a>) and see which cookies are sent.</p>

<p>Originally created for <a href="https://stackoverflow.com/questions/18492576/share-cookie-between-subdomain-and-domain">this Stack Overflow question</a>. <a href="https://github.com/cmbuckley/setcookie.net">View the source here</a>.</p>
<script>
function header(form) {
    let header = `Set-Cookie: ${form.name.value}=${form.value.value}; path=/`;

    if (form.dom.value && form.dom.value != 'none') {
        form.dom.forEach(function (input) {
            if (input.value == form.dom.value) {
                header += '; domain=' + input.labels[0].innerText;
            }
        });
    }

    if (form.sec && form.sec.checked) {
        header += '; secure';
    }

    header += '; HttpOnly';

    if (form.ss.value && form.ss.value != 'notset') {
        form.ss.forEach(function (input) {
            if (input.value == form.ss.value) {
                header += '; SameSite=' + input.labels[0].innerText;
            }
        });
    }

    return header;
}
document.querySelector('form').addEventListener('input', function () {
    if (this.name.value && this.value.value) {
        this.querySelector('samp').innerText = header(this);
    }
});
</script>
</body>
</html>
