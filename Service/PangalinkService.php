<?php
namespace TFox\PangalinkBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use TFox\PangalinkBundle\DependencyInjection\TFoxPangalinkExtension;
use TFox\PangalinkBundle\Exception\AccountNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use TFox\PangalinkBundle\Response\BankResponse;
use TFox\PangalinkBundle\Connector\SwedbankConnector;

class PangalinkService 
{
	/**
	 * @var $container \Symfony\Component\DependencyInjection\Container
	 */
	private $container;
	
	/**
	 * 
	 * @var array
	 */
	private $connectors;
	
	/**
	 *
	 * @var array
	 */
	private $configs;
	
	
	
	
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->connectors = array();		
		$this->configs = array();
	}
	
	/**
	 * Returns an instance of Connector for interaction with pangalink
	 * @param string $accountId
	 * @return 
	 */
	public function getConnector($accountId = 'default')
	{
		if(key_exists($accountId, $this->connectors))
			return $this->connectors[$accountId];
		
		$connector = null;
		$this->initConfig($accountId);
		if(key_exists($accountId, $this->configs)) {
			$accountData = $this->configs[$accountId];
			if(!key_exists('bank', $accountData)) {
				throw new \Exception(sprintf('PangalinkBundle configuration: missing mandatory parameter "bank" for account "%s"', $accountId));
			}
			$bankType = $accountData['bank'];
			switch($bankType) {
				case 'swedbank':
					$connector = new SwedbankConnector($this, $accountId, $accountData);
					break;
				default:
					throw new \Exception(sprintf('PangalinkBundle configuration: unknown bank type "%s" for account "%s"', $bankType, $accountId));
					break;
			}
		} else {
			throw new AccountNotFoundException($accountId);
		}
		$this->connectors[$accountId] = $connector;
		return $connector;
	}		
	
	private function initConfig($accountId)
	{
		if(isset($this->configs[$accountId]))
			return;
		
		//Configuration not found: load it from container parameters
		$config = array();
			
		$containerKey = TFoxPangalinkExtension::PREFIX_CONTAINER_ACCOUNTS.$accountId;
		if(!$this->container->hasParameter($containerKey))
			throw new AccountNotFoundException($accountId);
			
		$config = $this->container->getParameter($containerKey);
		$this->configs[$accountId] = $config;
	}
	
	public function getKernelRootPath()
	{
		return $this->container->getParameter('kernel.root_dir');
	}

}