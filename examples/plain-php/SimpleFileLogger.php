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

use Psr\Log\AbstractLogger;

/**
 * Simple PSR-3 file logger for the examples.
 *
 * In production, use a full-featured logger like Monolog instead.
 */
class SimpleFileLogger extends AbstractLogger
{
    public function __construct(
        private readonly string $filePath,
    ) {
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $line = sprintf("[%s] %s: %s%s\n", $timestamp, strtoupper((string) $level), $message, $contextStr);

        file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
    }
}
