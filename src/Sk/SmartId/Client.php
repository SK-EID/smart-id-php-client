<?php
namespace Sk\SmartId;

use InvalidArgumentException;
use Sk\SmartId\Api\AbstractApi;

class Client
{
	const VERSION = '5.0';

	/**
	 * @var string
	 */
	private $relyingPartyUUID;

	/**
	 * @var string
	 */
	private $relyingPartyName;

	/**
	 * @var string
	 */
	private $hostUrl;

	/**
	 * @param string $apiName
	 * @throws InvalidArgumentException
	 * @return AbstractApi
	 */
	public function api( $apiName )
	{
		switch ( $apiName )
		{
			case 'authentication':
			{
				// @TODO
			}

			default:
			{
				throw new InvalidArgumentException( 'No such api at present time!' );
			}
		}
	}

	/**
	 * @param string $relyingPartyUUID
	 * @return $this
	 */
	public function setRelyingPartyUUID( $relyingPartyUUID )
	{
		$this->relyingPartyUUID = $relyingPartyUUID;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRelyingPartyUUID()
	{
		return $this->relyingPartyUUID;
	}

	/**
	 * @param string $relyingPartyName
	 * @return $this
	 */
	public function setRelyingPartyName( $relyingPartyName )
	{
		$this->relyingPartyName = $relyingPartyName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRelyingPartyName()
	{
		return $this->relyingPartyName;
	}

	/**
	 * @param string $hostUrl
	 * @return $this
	 */
	public function setHostUrl( $hostUrl )
	{
		$this->hostUrl = $hostUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHostUrl()
	{
		return $this->hostUrl;
	}
}