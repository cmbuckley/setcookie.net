<?php

include $_SERVER['DOCUMENT_ROOT'] . '/../src/app.php';

$app = new App;
$app->mainIsCanonical();
$main = $app->getMainHost();

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Cookie Quirks - Test site to demo setting cookies.">
    <link rel="canonical" href="https://<?= $main; ?>/quirks/" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/picocss/1.5.6/pico.min.css" />
    <link rel="stylesheet" href="<?= $app->asset('/css/main.css'); ?>" />
    <title>Cookie Quirks</title>
  </head>
  <body>
    <main class="container">
      <h1>Cookie Quirks</h1>

      <p>Not all browsers behave the same when it comes to setting cookies. You should be aware of some key differences in behaviour:</p>

      <h2 id="no-domain-attribute">No <code>Domain</code> attribute</h2>

      <p>According to <a href="https://httpwg.org/specs/rfc6265.html#storage-model">RFC 6265</a>, if a <code>Domain</code> is not specified, a cookie should be treated as host-only, which means that a cookie set with no <code>Domain</code> attribute on <a href="/"><?= $main; ?></a> is not valid for <a href="https://www.<?= $main; ?>">www.<?= $main; ?></a>.</p>

      <p>However, IE and older versions of Edge <a href="https://learn.microsoft.com/en-gb/archive/blogs/ieinternals/internet-explorer-cookie-internals-faq">chose not to support this specification</a> until recently, and cookies without a <code>Domain</code> attribute would still be sent to subdomains. This was fixed in the following versions:</p>

      <ul>
        <li>Edge on Windows 10 RS3</li>
        <li>IE11 on Windows 10 RS4 (April 2018)</li>
      </ul>

      <h2 id="no-path-attribute">No <code>Path</code> attribute</h2>

      <p>As above, <a href="https://httpwg.org/specs/rfc6265.html#sane-path">RFC 6265</a> describes how a <code>Path</code> should be interpreted. When no attribute is provided, it defaults to the “directory” of the request URI’s path, so if the request path is <a href="/foo/bar">/foo/bar</a>, the <code>Path</code> attribute defaults to <code>/foo</code>, so the cookie would be sent to <a href="/foo/baz">/foo/baz</a>.</p>

      <p>However, IE again does not conform to this spec, and defaults the cookie path to the request path, meaning a cookie set on <a href="/foo/bar">/foo/bar</a> would not be sent to <a href="/foo/baz">/foo/baz</a>. Setting the <code>Path</code> explicitly solves this issue.</p>

      <h2 id="samesite-default-lax"><code>SameSite</code> defaulting to <code>Lax</code></h2>

      <p>Cookies that do not pass a <code>SameSite</code> attribute should default to <code>Lax</code> according to the <a href="https://httpwg.org/http-extensions/draft-ietf-httpbis-rfc6265bis.html#name-the-samesite-attribute">draft specification</a>.</p>

      <p>However, Firefox and Safari <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#browser_compatibility">do not follow this behaviour</a>. It is advised to explicitly set this attribute for the most compatible experience.</p>

      <footer>
        <p><small>Created by <a href="https://cmbuckley.co.uk">Chris Buckley</a> for <a href="https://stackoverflow.com/questions/18492576/share-cookie-between-subdomain-and-domain">this Stack Overflow question</a>. <a href="https://github.com/cmbuckley/setcookie.net">View the source here</a>.</small></p>
      </footer>
    </main>
    <script src="<?= $app->asset('/js/main.js'); ?>"></script>
  </body>
</html>

