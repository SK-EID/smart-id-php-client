<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Client;

abstract class AbstractApi implements ApiInterface
{
	/**
	 * @var Client
	 */
	protected $client;

	/**
	 * @param Client $client
	 */
	public function __construct( Client $client )
	{
		$this->client = $client;
	}
}