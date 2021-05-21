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
 * @copyright  Copyright (c) 2016 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */
namespace Lof\ChatSystem\Block\Adminhtml\Blacklist\Edit\Tab;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject as ObjectConverter;

class Detail extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Convert\DataObject
     */
    protected $_objectConverter;

    /**
     * @var \Lof\ChatSystem\Model\ChatMessage
     */
    protected $_message_model;

    /**
     * @var \Lof\ChatSystem\Model\Blacklist
     */
    protected $_blacklistModel;

    /**
     * @var \Lof\ChatSystem\Helper\Data
     */
    protected $_formHelper;

    /**
     * [__construct description]
     * @param \Magento\Backend\Block\Template\Context                       $context
     * @param \Magento\Framework\Registry                                   $registry
     * @param \Magento\Framework\Data\FormFactory                           $formFactory
     * @param GroupRepositoryInterface                                      $groupRepository
     * @param ObjectConverter                                               $objectConverter
     * @param SearchCriteriaBuilder                                         $searchCriteriaBuilder
     * @param \Magento\Store\Model\System\Store                             $systemStore
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templatesFactory
     * @param \Magento\Email\Model\Template\Config                          $emailConfig
     * @param \Lof\ChatSystem\Model\Blacklist                              $blacklist
     * @param \Lof\ChatSystem\Helper\Data                                  $_formHelper
     * @param array                                                         $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        GroupRepositoryInterface $groupRepository,
        ObjectConverter $objectConverter,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templatesFactory,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Lof\ChatSystem\Model\Blacklist $blacklist,
        \Lof\ChatSystem\Helper\Data $_formHelper,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->groupRepository = $groupRepository;
        $this->_objectConverter = $objectConverter;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_templatesFactory = $templatesFactory;
        $this->_emailConfig = $emailConfig;
        $this->_blacklistModel = $blacklist;
        $this->_formHelper = $_formHelper;
        parent::__construct($context, $data);
        if ($this->hasData("template") && $this->getData("template")) {
            $this->setTemplate($this->getData("template"));
        } elseif (isset($data['template']) && $data['template']) {
            $this->setTemplate($data['template']);
        } else {
            $this->setTemplate("Lof_ChatSystem::edit/blacklist.phtml");
        }
    }

    public function setMessageModel($message)
    {
        $this->_message_model = $message;
        return $this;
    }

    public function toHtml()
    {
        $message_model = $this->_message_model;
        $blacklist = [];
        $emails = [];
        if ($message_model && $message_model->getId()) {
            $blacklist = $this->_blacklistModel->loadListByMessageId($message_model->getId());
            $params = $message_model->getParams();
            $params = unserialize($params);
            if ($params && isset($params['submit_data']) && $params['submit_data']) {
                $emails = $this->_formHelper->getEmailsFromData($params['submit_data']);
            }
        }
        $this->assign("blacklist", $blacklist);
        $this->assign("message_model", $message_model);
        $this->assign("emails", $emails);
        return parent::toHtml();
    }

    public function getBlacklistUrl()
    {
        return $this->getUrl("*/blacklist/ajaxblock");
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
    public function FormatDateFormBuilder($DateTime)
    {
        return $this->_formHelper->FormatDateFormBuilder($DateTime);
    }
}
