<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the landofcoder.com license that is
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
namespace Lof\ChatSystem\Controller\Chat;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Display Hello on screen
 */
class Msglog extends \Magento\Framework\App\Action\Action
{
    protected $_cacheTypeList;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Lof\ChatSystem\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    protected $_message;

    protected $chat;
    protected $httpRequest;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    protected $blacklistFactory;

    /**
     * @param Context                                             $context              
     * @param \Magento\Store\Model\StoreManager                   $storeManager         
     * @param \Magento\Framework\View\Result\PageFactory          $resultPageFactory    
     * @param \Lof\ChatSystem\Helper\Data                               $helper           
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory 
     * @param \Magento\Framework\Registry                         $registry             
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Lof\ChatSystem\Helper\Data $helper,
        \Lof\ChatSystem\Model\ChatMessage $message,
        \Lof\ChatSystem\Model\Chat $chat,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList, 
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\App\Request\Http $httpRequest,
        \Lof\ChatSystem\Model\BlacklistFactory $blacklistFactory
        ) {
        $this->chat                 = $chat;
        $this->resultPageFactory    = $resultPageFactory;
        $this->_helper              = $helper;
        $this->_message             = $message;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->_coreRegistry        = $registry;
        $this->_cacheTypeList       = $cacheTypeList;
        $this->_customerSession     = $customerSession;
        $this->_request             = $context->getRequest();
        parent::__construct($context);
    }

    /**
     * Default customer account page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $enable_blacklist = $this->_helper->getConfig('chat/enable_blacklist');
        //check if enabled config blacklist, then check if ip in blacklist, then redirect it to home, else continue action
        if ($enable_blacklist) {
            $client_ip = $this->remoteAddress->getRemoteAddress();
            $blacklist_model = $this->blacklistFactory->create(); 
            if ($client_ip) {
                $blacklist_model->loadByIp($client_ip);
                if ((0 < $blacklist_model->getId()) && $blacklist_model->getStatus()) {
                    print __('Your IP was blocked in our blacklist. So, you will not get any messages.');
                    exit;
                }
            }
            if($customer_email = $this->_customerSession->getCustomer()->getEmail()) {
                $customer_id = $this->_customerSession->getCustomerId();
                $blacklist_model->loadByCustomerId((int)$customer_id);
                if ((0 < $blacklist_model->getId()) && $blacklist_model->getStatus()) {
                    print __('Your Account was blocked in our blacklist. So, you will not get any messages.');
                    exit;
                }
                $blacklist_model2 = $this->blacklistFactory->create();
                $blacklist_model2->loadByEmail($customer_email);
                if ((0 < $blacklist_model2->getId()) && $blacklist_model2->getStatus()) {
                    print __('Your Email Address was blocked in our blacklist. So, you will not get any messages.');
                    exit;
                }
            }
        }
        if($this->_customerSession->getCustomer()->getEmail()) {
            $message = $this->_message->getCollection()->addFieldToFilter('customer_email',$this->_customerSession->getCustomer()->getEmail());
        } else {
            $chat = $this->chat->load($this->_helper->getIp(),'ip');
            $message = $this->_message->getCollection()->addFieldToFilter('chat_id',$chat->getId()); 
        }
        $count = count($message);
        $i=0;
        $auto_user_name = $this->_helper->getConfig('chat/auto_user_name');
        $auto_message = $this->_helper->getConfig('chat/auto_message');
        $auto_message = trim($auto_message);
        $count_found_user_replied = 0;
        foreach ($message as $key => $_message) {
            $i++;
            $date_sent = $_message['created_at'];
            $day_sent = substr($date_sent, 8, 2); 
            $month_sent = substr($date_sent, 5, 2); 
            $year_sent = substr($date_sent, 0, 4); 
            $hour_sent = substr($date_sent, 11, 2); 
            $min_sent = substr($date_sent, 14, 2); 
            $body_msg = $this->_helper->xss_clean($_message['body_msg']);
            if (!$_message['user_id'])
            {
                print '<div class="msg-user">
                        <p>'.$body_msg.'</p>
                        <div class="info-msg-user">
                            '.__("You").'
                        </div>
                    </div> ';

            } else {
      
                print '<div class="msg">
                    <p>'.$body_msg.'</p>
                    <div class="info-msg">
                        '.$_message['user_name'].'
                    </div>
                </div>';
                $count_found_user_replied++;
                if($count == $i) {
                    echo "
                    <script>require(['jquery'],function($) { $('.chat-message-counter').css('display','inline'); });</script>
                    ";
                }

            }
        }
        if(!$count_found_user_replied && $auto_message){
            $auto_user_name = $auto_user_name?$auto_user_name:__("Bot");
            print '<div class="msg">
                    <p>'.$auto_message.'</p>
                    <div class="info-msg">
                        '.$auto_user_name.'
                    </div>
                </div>';
        }
        exit;
    }
}