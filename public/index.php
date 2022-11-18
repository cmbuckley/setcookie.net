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
    $error = $warn = $message = '';
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

            if (isset($samesite)) {
                $opts['samesite'] = $samesite;

                if ($samesite == 'None' && !$secure) {
                    $warn = 'Cookies with <code>SameSite=None</code> must also set the <code>secure</code> flag.';
                }
            }

            if (preg_match('/^__Secure-/', $name) && (!$https || !$secure)) {
                $warn = 'Cookies with names starting <code>__Secure-</code> must have the <code>secure</code> flag and be set via HTTPS.';
            }
            else if (preg_match('/__Host-/', $name) && (!$https || !$secure || $dom !== '')) {
                $warn = 'Cookies with names starting <code>__Host-</code> must have the <code>secure</code> flag, be set via HTTPS and must not have a <code>domain</code> specified';
            }

            setcookie($name, $value, $opts);
            $message = 'Sent header: <code>' . sentheader() . '</code>';
        }
        else {
            $error = 'name and value must be alphanumeric or one of <samp>_-</samp>';
        }
    }
    else {
        $error = 'must supply cookie name and value';
    }
}

?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/picocss/1.5.6/pico.min.css" />
    <link rel="stylesheet" href="/css/main.css" />
  </head>
  <body>
    <main class="container">
      <hgroup>
        <h1>Cookie Test</h1>
        <p>Domain: <?= $host; ?></p>
      </hgroup>

      <article>
<?php

if (count($_COOKIE)) {
    echo "<p>Received cookies:</p><ul>\n";

    foreach ($_COOKIE as $n => $v) {
        echo "<li><code>$n = $v</code></li>\n";
    }

    echo "</ul>\n\n";
}
else {
    echo '<p>Received no cookies.</p>';
}

if (isset($_POST['name'], $_POST['value'])) {
    if ($error) {
        echo '<p class="error">' . $error . '</p>';
    } else {
        echo '<p class="success">' . $message . '</p>';

        if ($warn) {
            echo '<p class="warning">' . $warn . '</p>';
        }
    }
}

?>
      </article>

      <form action="" method="post">
        <div class="grid">
          <label for="name">
            Cookie name
            <input name="name" id="name" pattern="[A-Za-z0-9_-]+" value="<?= $name; ?>" />
          </label>

          <label for="value">
            Cookie value
            <input name="value" id="value" pattern="[A-Za-z0-9_-]+" value="<?= $value ?>" />
          </label>
        </div>
        <small>(alphanumeric or <code>_-</code>; restricted character set compared to <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#attributes">spec</a>)</small>

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
        <label><input type="radio" name="ss" value="notset" checked />(not set)</label>
        <small>(<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite#cookies_without_samesite_default_to_samesitelax">behaves like Lax</a> in most browsers, but see <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite#browser_compatibility">exceptions</a>)</small>
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
      <footer>
        <p><small>Created by <a href="https://cmbuckley.co.uk">Chris Buckley</a> for <a href="https://stackoverflow.com/questions/18492576/share-cookie-between-subdomain-and-domain">this Stack Overflow question</a>. <a href="https://github.com/cmbuckley/setcookie.net">View the source here</a>.</small></p>
      </footer>
    </main>
    <script src="/js/main.js"></script>
  </body>
</html>
