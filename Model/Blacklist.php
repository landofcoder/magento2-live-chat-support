<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * http://landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lof_ChatSystem
 * @copyright  Copyright (c) 2017 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */
namespace Lof\ChatSystem\Model;

class Blacklist extends \Magento\Framework\Model\AbstractModel
{
    /**#@+
     * Form's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    /**
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Lof\ChatSystem\Model\ResourceModel\Blacklist|null            $resource
     * @param \Lof\ChatSystem\Model\ResourceModel\Blacklist\Collection|null $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Lof\ChatSystem\Model\ResourceModel\Blacklist $resource = null,
        \Lof\ChatSystem\Model\ResourceModel\Blacklist\Collection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Lof\ChatSystem\Model\ResourceModel\Blacklist');
    }

    public function loadByCustomerId($customer_id = 0)
    {
        return $this->getResource()->load($this, $customer_id, 'customer_id');
    }

    public function loadByEmail($email_address)
    {
        return $this->getResource()->load($this, $email_address, 'email');
    }

    public function loadByIp($ip_address)
    {
        return $this->getResource()->load($this, $ip_address, 'ip');
    }

    /**
     * Prepare page's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Blocked'), self::STATUS_DISABLED => __('Un Blocked')];
    }
}
