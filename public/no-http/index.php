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
    <meta name="description" content="No HTTP?">
    <link rel="canonical" href="https://<?= $main; ?>/no-http/" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/picocss/1.5.6/pico.min.css" />
    <link rel="stylesheet" href="<?= $app->asset('/css/main.css'); ?>" />
    <title>No HTTP?</title>
  </head>
  <body>
    <main class="container">
      <hgroup>
        <h1>No HTTP?</h1>
        <p><a href="/">Back to site</a></p>
      </hgroup>

      <p>Browsers increasingly don’t want you to visit HTTP websites! If you click an HTTP link
      and find yourself on an HTTPS page, update your browser settings to allow insecure content.</p>

      <h2>Chrome</h2>

      <ol>
      <li>Click <b>View site information</b> in the address bar.</li>
      <li>Click <b>Site settings</b>.</li>
      <li>Set the permission for <b>Insecure content</b> to <b>Allow</b>.</li>
      <li>Reload the site page to apply the settings.</li>
      </ol>

      <footer>
        <p><small>Created by <a href="https://cmbuckley.co.uk">Chris Buckley</a> for <a href="https://stackoverflow.com/questions/18492576/share-cookie-between-subdomain-and-domain">this Stack Overflow question</a>. <a href="https://github.com/cmbuckley/setcookie.net">View the source here</a>.</small></p>
      </footer>
    </main>
    <script src="<?= $app->asset('/js/main.js'); ?>"></script>
  </body>
</html>

