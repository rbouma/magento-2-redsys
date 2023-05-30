<?php

namespace Catgento\Redsys\Model\Ui;

use Catgento\Redsys\Gateway\Config\Config;
use Catgento\Redsys\Model\ConfigInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'redsys';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Config                  $config,
        SessionManagerInterface $session
    )
    {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->session->getStoreId();

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive($storeId),
                    'redirectUrl' => ConfigInterface::REDSYS_REDIRECT_URI
                ]
            ]
        ];
    }

}
