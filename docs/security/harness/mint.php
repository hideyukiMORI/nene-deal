<?php
// Mint an HS256 JWT for the JWT-verification tests (alg / exp / signature / org
// claim). Usage:
//   php mint.php '<payload-json>' '<secret>'  [optional: '<header-json>']
// For alg=none / alg-confusion tests, pass a header explicitly, e.g.
//   php mint.php '{"sub":"x","exp":9999999999}' '' '{"typ":"JWT","alg":"none"}'
$payload    = $argv[1] ?? '{}';
$secret     = $argv[2] ?? '';
$headerJson = $argv[3] ?? '{"typ":"JWT","alg":"HS256"}';
$b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
$h = $b64($headerJson);
$p = $b64($payload);
$sig = $b64(hash_hmac('sha256', "$h.$p", $secret, true));
echo "$h.$p.$sig";
