<?php
namespace Sk\SmartId\Api\Data;

abstract class SessionEndResultCode
{
  const OK = 'OK';
  const USER_REFUSED = 'USER_REFUSED';
  const TIMEOUT = 'TIMEOUT';
  const DOCUMENT_UNUSABLE = 'DOCUMENT_UNUSABLE';
}