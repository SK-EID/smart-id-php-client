[![Build Status](https://travis-ci.com/SK-EID/smart-id-php-client.svg?branch=master)](https://travis-ci.com/SK-EID/smart-id-php-client)
[![Latest Version](https://img.shields.io/packagist/v/sk-id-solutions/smart-id-php-client?label=version)](https://packagist.org/packages/sk-id-solutions/smart-id-php-client/)
[![License: LGPL v3](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

# Smart-ID PHP client

## Introduction
The Smart-ID PHP client can be used for easy integration of the Smart-ID solution to information systems or e-services.

## Features
* Simple interface for user authentication

Smart-ID PHP client works with PHP 7.3 or later.

**This PHP client cannot be used to create digitally signed containers because PHP does not have a library like DigiDoc4J..**

## Installation
The recommended way to install Smart-ID PHP Client is through [Composer]:

```
composer require sk-id-solutions/smart-id-php-client "2.2"
```

See [packagist](https://packagist.org/packages/sk-id-solutions/smart-id-php-client) for latest published version
and [changelog](CHANGELOG.md) for details.

## Https pinning

   Used to prevent man-in-the-middle attacks. [More on man in the middle attacks in case of using smart id.](https://github.com/SK-EID/smart-id-documentation#35-api-endpoint-authentication)

   Setting the client to trust specific public keys. SSL certificates used can be found [here](https://www.skidsolutions.eu/repositoorium/sk-sertifikaadid).
   
   The setPublicSslKeys method requires a string of sha256 hashes of the public keys used delimited with ";". Instructions for extrecting the hashes from certificates can be found [here](https://curl.haxx.se/libcurl/c/CURLOPT_PINNEDPUBLICKEY.html). You can extract hashes from certificates using next openssl command.
   
   ```
   openssl x509 -inform PEM -in certificate.pem -noout -pubkey | openssl rsa -pubin -outform der 2>/dev/null | openssl dgst -sha256 -binary | openssl enc -base64
   ```
   
   The supplied string should be of format sha256//sha256-hash-of-the-public-key;

```PHP
$this->client = new Client();
$this->client
  ->setRelyingPartyUUID( "YOUR UUID" )
  ->setRelyingPartyName( "YOUR RP NAME" )
  ->setHostUrl("HOST_URL")
  ->setPublicSslKeys("sha256//nTL2Ju/1Mt+WAHeejqZHtgPNRu049iUcXOPq0GmRgJg=;sha256//wkdgNtKpKzMtH/zoLkgeScp1Ux4TLm3sUldobVGA/g4=");
 ```

## How to use it
Take a look at the [examples](https://github.com/SK-EID/smart-id-php-client/wiki/Examples-of-using-it)

[Composer]: http://getcomposer.org
