# Smart-ID PHP client

## Introduction
The Smart-ID PHP client can be used for easy integration of the Smart-ID solution to information systems or e-services.

## Features
* Simple interface for user authentication
* Built-in HTTPS public key pinning for secure API communication

## HTTPS pinning

HTTPS public key pinning is used to prevent man-in-the-middle attacks against the Smart-ID API connection.
The SDK handles this automatically — Guzzle's default SSL verification is disabled and replaced with
[CURLOPT_PINNEDPUBLICKEY](https://curl.se/libcurl/c/CURLOPT_PINNEDPUBLICKEY.html) pinning against SK's server certificate public keys.

### Production

For production, you must configure your own SPKI hashes. Get the current certificate hashes from
[SK's HTTPS pinning documentation](https://sk-eid.github.io/smart-id-documentation/https_pinning.html).

Extract the SPKI hash from a PEM certificate:

```bash
openssl x509 -inform PEM -in certificate.pem -noout -pubkey \
  | openssl rsa -pubin -outform der 2>/dev/null \
  | openssl dgst -sha256 -binary \
  | openssl enc -base64
```

Then provide the hashes:

```php
use Sk\SmartId\Api\SmartIdRestConnector;
use Sk\SmartId\Ssl\SslPinnedPublicKeyStore;

$sslKeys = SslPinnedPublicKeyStore::create()
    ->addPublicKeyHash('sha256//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX=')
    ->addPublicKeyHash('sha256//YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY=');

$connector = new SmartIdRestConnector('https://rp-api.smart-id.com/v3', $sslKeys);
```

You can also load hashes from a directory of `.key` files (each containing one `sha256//...` hash):

```php
$sslKeys = SslPinnedPublicKeyStore::loadFromDirectory('/path/to/your/keys');
```

### Demo / testing

For development against `sid.demo.sk.ee`, the SDK bundles demo keys:

```php
$connector = new SmartIdRestConnector(
    'https://sid.demo.sk.ee/smart-id-rp/v3',
    SslPinnedPublicKeyStore::loadDemo(),
);