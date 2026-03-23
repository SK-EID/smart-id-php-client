<?php

/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2026 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */

declare(strict_types=1);

namespace Sk\SmartId\Validation\Maps;

use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\AlgorithmIdentifier;
use phpseclib3\File\ASN1\Maps\Certificate;
use phpseclib3\File\ASN1\Maps\CertificateSerialNumber;
use phpseclib3\File\ASN1\Maps\CRLReason;
use phpseclib3\File\ASN1\Maps\Extensions;
use phpseclib3\File\ASN1\Maps\Name;

/**
 * ASN.1 map for BasicOCSPResponse per RFC 6960.
 *
 * BasicOCSPResponse ::= SEQUENCE {
 *     tbsResponseData      ResponseData,
 *     signatureAlgorithm   AlgorithmIdentifier,
 *     signature            BIT STRING,
 *     certs            [0] EXPLICIT SEQUENCE OF Certificate OPTIONAL
 * }
 *
 * ResponseData ::= SEQUENCE {
 *     version              [0] EXPLICIT Version DEFAULT v1,
 *     responderID              ResponderID,
 *     producedAt               GeneralizedTime,
 *     responses                SEQUENCE OF SingleResponse,
 *     responseExtensions   [1] EXPLICIT Extensions OPTIONAL
 * }
 *
 * SingleResponse ::= SEQUENCE {
 *     certID                       CertID,
 *     certStatus                   CertStatus,
 *     thisUpdate                   GeneralizedTime,
 *     nextUpdate           [0] EXPLICIT GeneralizedTime OPTIONAL,
 *     singleExtensions     [1] EXPLICIT Extensions OPTIONAL
 * }
 *
 * CertStatus ::= CHOICE {
 *     good        [0] IMPLICIT NULL,
 *     revoked     [1] IMPLICIT RevokedInfo,
 *     unknown     [2] IMPLICIT NULL
 * }
 */
abstract class OcspBasicResponseMap
{
    public const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'tbsResponseData' => [
                'type' => ASN1::TYPE_SEQUENCE,
                'children' => [
                    'version' => [
                        'type' => ASN1::TYPE_INTEGER,
                        'constant' => 0,
                        'optional' => true,
                        'explicit' => true,
                        'mapping' => ['v1'],
                        'default' => 'v1',
                    ],
                    'responderID' => [
                        'type' => ASN1::TYPE_CHOICE,
                        'children' => [
                            'byName' =>
                                [
                                    'constant' => 1,
                                    'explicit' => true,
                                ] + Name::MAP,
                            'byKey' => [
                                'constant' => 2,
                                'explicit' => true,
                                'type' => ASN1::TYPE_OCTET_STRING,
                            ],
                        ],
                    ],
                    'producedAt' => ['type' => ASN1::TYPE_GENERALIZED_TIME],
                    'responses' => [
                        'type' => ASN1::TYPE_SEQUENCE,
                        'min' => 0,
                        'max' => -1,
                        'children' => [
                            'type' => ASN1::TYPE_SEQUENCE,
                            'children' => [
                                'certID' => [
                                    'type' => ASN1::TYPE_SEQUENCE,
                                    'children' => [
                                        'hashAlgorithm' =>
                                            AlgorithmIdentifier::MAP,
                                        'issuerNameHash' => [
                                            'type' => ASN1::TYPE_OCTET_STRING,
                                        ],
                                        'issuerKeyHash' => [
                                            'type' => ASN1::TYPE_OCTET_STRING,
                                        ],
                                        'serialNumber' =>
                                            CertificateSerialNumber::MAP,
                                    ],
                                ],
                                'certStatus' => [
                                    'type' => ASN1::TYPE_CHOICE,
                                    'children' => [
                                        'good' => [
                                            'constant' => 0,
                                            'implicit' => true,
                                            'type' => ASN1::TYPE_NULL,
                                        ],
                                        'revoked' => [
                                            'constant' => 1,
                                            'implicit' => true,
                                            'type' => ASN1::TYPE_SEQUENCE,
                                            'children' => [
                                                'revokedTime' => [
                                                    'type' =>
                                                        ASN1::TYPE_GENERALIZED_TIME,
                                                ],
                                                'revokedReason' =>
                                                    [
                                                        'constant' => 0,
                                                        'explicit' => true,
                                                        'optional' => true,
                                                    ] + CRLReason::MAP,
                                            ],
                                        ],
                                        'unknown' => [
                                            'constant' => 2,
                                            'implicit' => true,
                                            'type' => ASN1::TYPE_NULL,
                                        ],
                                    ],
                                ],
                                'thisUpdate' => [
                                    'type' => ASN1::TYPE_GENERALIZED_TIME,
                                ],
                                'nextUpdate' => [
                                    'type' => ASN1::TYPE_GENERALIZED_TIME,
                                    'constant' => 0,
                                    'explicit' => true,
                                    'optional' => true,
                                ],
                                'singleExtensions' =>
                                    [
                                        'constant' => 1,
                                        'explicit' => true,
                                        'optional' => true,
                                    ] + Extensions::MAP,
                            ],
                        ],
                    ],
                    'responseExtensions' =>
                        [
                            'constant' => 1,
                            'explicit' => true,
                            'optional' => true,
                        ] + Extensions::MAP,
                ],
            ],
            'signatureAlgorithm' => AlgorithmIdentifier::MAP,
            'signature' => ['type' => ASN1::TYPE_BIT_STRING],
            'certs' => [
                'constant' => 0,
                'explicit' => true,
                'optional' => true,
                'type' => ASN1::TYPE_SEQUENCE,
                'min' => 0,
                'max' => -1,
                'children' => Certificate::MAP,
            ],
        ],
    ];
}
