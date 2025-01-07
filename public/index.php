<?php

include $_SERVER['DOCUMENT_ROOT'] . '/../src/app.php';

$app = new App($_POST);
$main = $app->getMainHost();
$host = $app->getHost();
$https = $app->isHttps();

$name = $value = '';
$path = '/';

// make sure it's using the main URL
if (getenv('APP_ENV') == 'production' && getenv('FLY_APP_NAME') && strpos($host, $main) === false) {
    header("Location: https://$main");
    return;
}

if (isset($_POST['name'], $_POST['value'])) {
    $error = $warn = $message = '';
    $unsafe = '/[^a-z\d_-]/i'; // purposefully a bit stricter than the spec
    $rawName = $_POST['name'];
    $rawValue = $_POST['value'];
    $rawPath = $_POST['path'];
    $name = preg_replace($unsafe, '', $rawName);
    $value = preg_replace($unsafe, '', $rawValue);
    $path = preg_replace('/[^a-z\d\/_-]/i', '', $rawPath);
    $secure = (isset($_POST['sec']) && $_POST['sec'] === 'on');
    $httpOnly = (isset($_POST['httponly']) && $_POST['httponly'] === 'on');
    $expires = $app->expires();
    $samesite = $app->samesite();
    $isFetch = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'fetch';

    try {
        if (empty($name) || empty($value)) {
            throw new Exception('must supply cookie name and value.');
        }

        if ($rawName !== $name || $rawValue !== $value) {
            throw new Exception('name and value must be alphanumeric or one of <samp>_-</samp>.');
        }

        if ($rawPath !== $path) {
            throw new Exception('path must be alphanumeric or one of <samp>/_-</samp>.');
        }

        if (!isset($_POST['dom']) || $_POST['dom'] == 'none') {
            $dom = '';
        } elseif (in_array($_POST['dom'], $app->getDomains())) {
            $dom = $_POST['dom'];
        } else {
            $dom = $host;
        }

        if ($expires === false) {
            throw new Exception('expiry date must be in the future.');
        }

        $opts = [
            'expires'  => $expires,
            'path'     => $path,
            'domain'   => $dom,
            'secure'   => $secure,
            'httponly' => $httpOnly,
        ];

        if ($path != '' && substr($path, 0, 1) !== '/') {
            $warn = 'Paths without a leading / are treated as if no attribute was provided.';
        }
        elseif (!$app->pathMatch($path)) {
            $warn = 'Cookies can be set on non-matching paths, but it would not be sent for a request to this path.';
        }

        if (isset($samesite)) {
            $opts['samesite'] = $samesite;

            if ($samesite == 'None' && !$secure) {
                $warn = 'Cookies with <code>SameSite=None</code> must also set the <code>secure</code> flag.';
            }
        }

        if (preg_match('/^__Secure-/', $name) && (!$https || !$secure)) {
            $warn = 'Cookies with names starting <code>__Secure-</code> must have the <code>secure</code> flag and be set via HTTPS.';
        }
        else if (preg_match('/__Host-/', $name) && (!$https || !$secure || $dom !== '' || $path !== '/')) {
            $warn = 'Cookies with names starting <code>__Host-</code> must have the <code>secure</code> flag, be set via HTTPS, must not have a <code>domain</code> specified and must set <code>path=/</code>.';
        }

        setcookie($name, $value, $opts);
        $message = 'Sent header: <code>' . $app->getSentHeader() . '</code>';
        if($isFetch) { 
            header('Content-type: application/json');
            echo json_encode(['existing_cookies' => $_COOKIE, 'set_cookie_header' => $app->getSentHeader()]);
            exit;
        }
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

function displayUrl($url, $main) {
    $parts = parse_url($url);
    $subdomains = preg_replace('/' . preg_quote($main, '/') . '$/', '', $parts['host']);

    // the main URL
    $formatted = sprintf(
        '<span class="scheme %1$s">%1$s://</span><span class="subs">%2$s</span>%3$s<span class="path">%4$s</span>',
        $parts['scheme'],
        $subdomains,
        $main,
        htmlentities($parts['path'])
    );

    // supplementary info
    $facts = [sprintf('<span class="scheme %s">%s</span>', $parts['scheme'], strtoupper($parts['scheme']))];
    if ($subdomains != '') { $facts[] = '<span class="subs">subdomains</span>'; }
    if ($parts['path'] != '/') { $facts[] = '<span class="path">path</span>'; }

    return '<b class="url">' . $formatted . '</b> <i>(' . implode(', ', $facts) . ')</i>';
}



?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Test site to demo setting cookies.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/picocss/1.5.7/pico.min.css" />
    <link rel="stylesheet" href="<?= $app->asset('/css/main.css'); ?>" />
    <title>Cookie Test</title>
  </head>
  <body>
    <main class="container">
      <hgroup>
        <h1>Cookie Test</h1>
        <p>
          URL: <?= displayUrl($app->getUrl(), $main); ?>

          <?php if (isset($_COOKIE['!proto']) && $_COOKIE['!proto'] != ($https ? 'https:' : 'http:')): ?>
          <small class="proto error">Did you expect an HTTP URL? Your browser might be preventing it.
          See <a href="/no-http/">more information here</a>.</small>
          <?php endif; ?>
        </p>
      </hgroup>


      <p>You can test various cookie options below and how they affect which cookies are sent to different URLs, such as
        <a class="self" href="https://a.<?= $main; ?>"><b class="subs">a.</b><?= $main; ?></a>,
        <a class="self" href="https://a.b.<?= $main; ?>"><b class="subs">a.b.</b><?= $main; ?></a>,
        <a class="self" href="https://<?= $main; ?>/foo"><?= $main; ?><b class="path">/foo</b></a>,
        <a class="self" href="https://<?= $main; ?>"><b class="https">https://</b><?= $main; ?></a>, or
        <a class="self" href="http://<?= $main; ?>"><b class="http">http://</b><?= $main; ?></a>.
        See also some <a href="https://<?= $main; ?>/quirks/">known browser quirks</a>.
      </p>

      <article>
        <a class="reload" href="" title="Reload">↻</a>
<?php

if (count($_COOKIE) && count(array_filter(array_keys($_COOKIE), fn($k) => $k[0] != '!'))) {
    echo "<p>Received cookies:</p><ul>\n";

    foreach ($_COOKIE as $n => $v) {
        if ($n[0] != '!') { echo "<li><code>$n = $v</code></li>\n"; }
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
            <input name="name" id="name" required pattern="[A-Za-z0-9_\-]+" value="<?= $name; ?>" />
          </label>

          <label for="value">
            Cookie value
            <input name="value" id="value" required pattern="[A-Za-z0-9_\-]+" value="<?= $value ?>" />
          </label>
        </div>
        <small>(alphanumeric or <code>_-</code>; restricted character set compared to <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#attributes">spec</a>)</small>

        <label for="path">Path</label>
        <input name="path" id="path" pattern="[A-Za-z0-9\/_\-]+" value="<?= $path; ?>" />
        <small>(alphanumeric or <code>/_-</code>)</small>

        <p>Cookie domain:
        <?php foreach ($app->getDomains() as $domain): ?>
        <label><input type="radio" name="dom" value="<?= $domain ?>" /><?= $domain; ?></label>
        <?php endforeach; ?>
        <label><input type="radio" name="dom" value="none" checked />(unspecified)</label>
        <small>(see <a href="https://<?= $main; ?>/quirks/#no-domain-attribute">quirks</a> about unspecified domain)</small>
        </p>

        <p>SameSite:
        <?php if ($https): ?>
        <label><input type="radio" name="ss" value="none" />None</label>
        <?php endif; ?>
        <label><input type="radio" name="ss" value="lax" />Lax</label>
        <label><input type="radio" name="ss" value="strict" />Strict</label>
        <label><input type="radio" name="ss" value="notset" checked />(not set)</label>
        <small>(<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite#cookies_without_samesite_default_to_samesitelax">behaves like Lax</a> in most browsers, but see <a href="https://<?= $main; ?>/quirks/#samesite-default-lax">exceptions</a>)</small>
        </p>

        <p>
            <label>Set expiry date: <input type="checkbox" name="expires" /></label>
            <label class="hidden"><span class="hidden">Expiry date:</span> <input type="datetime-local" name="expdate" /></label>
            <input type="hidden" name="tz" />
        </p>

        <?php if ($https): ?>
        <p><label>Set secure-only cookie: <input type="checkbox" name="sec" /></label></p>
        <?php endif; ?>

        <p><label>Set HTTP-only cookie: <input type="checkbox" name="httponly" checked /></label></p>

        <p>Will result in the following cookie: <samp /></p>
        <input type="submit" />
      </form>
      <p>Credentials:
      <label><input type="radio" name="credentials" value="same-origin" checked />same-origin</label> 
      <label><input type="radio" name="credentials" value="include" />include</label>
      <label><input type="radio" name="credentials" value="omit" />omit</label>
      <small><a href="https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch#including_credentials">See MDN docs for fetch()#Include credentials</a> </small>
      </p>
      <button onClick="callFetch()">Call fetch()</button>

      <footer>
        <p><small>Created by <a href="https://cmbuckley.co.uk">Chris Buckley</a> for <a href="https://stackoverflow.com/questions/18492576/share-cookie-between-subdomain-and-domain">this Stack Overflow question</a>. <a href="https://github.com/cmbuckley/setcookie.net">View the source here</a>.</small></p>
      </footer>
    </main>
    <script src="<?= $app->asset('/js/main.js'); ?>"></script>
  </body>
</html>
