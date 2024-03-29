<?php
/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2019 SK ID Solutions AS
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
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\AuthenticationSessionRequest;
use Sk\SmartId\Api\Data\AuthenticationSessionResponse;
use Sk\SmartId\Api\Data\SemanticsIdentifier;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusRequest;
use Sk\SmartId\Exception\SessionNotFoundException;

interface SmartIdConnector
{
  /**
   * @param string $documentNumber
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  function authenticate(string $documentNumber, AuthenticationSessionRequest $request ): AuthenticationSessionResponse;

  /**
   * @param SemanticsIdentifier $semanticsIdentifier
   * @param AuthenticationSessionRequest $request
   * @return AuthenticationSessionResponse
   */
  function authenticateWithSemanticsIdentifier(SemanticsIdentifier $semanticsIdentifier, AuthenticationSessionRequest $request): AuthenticationSessionResponse;

  /**
   * @param SessionStatusRequest $request
   * @throws SessionNotFoundException
   * @return SessionStatus
   */
  function getSessionStatus( SessionStatusRequest $request ): SessionStatus;
}
