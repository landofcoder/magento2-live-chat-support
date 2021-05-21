<?php
/**
 * Copyright © landofcoder All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Lof\ChatSystem\Model;

use Lof\ChatSystem\Api\ChatRepositoryInterface;
use Lof\ChatSystem\Api\Data\ChatInterfaceFactory;
use Lof\ChatSystem\Api\Data\ChatSearchResultsInterfaceFactory;
use Lof\ChatSystem\Model\ResourceModel\Chat as ResourceChat;
use Lof\ChatSystem\Model\ResourceModel\Chat\CollectionFactory as ChatCollectionFactory;
use Lof\ChatSystem\Api\Data\MessageSearchResultsInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class ChatRepository implements ChatRepositoryInterface
{

    protected $resource;

    protected $extensibleDataObjectConverter;
    protected $searchResultsFactory;

    private $storeManager;

    protected $chatFactory;

    protected $dataObjectHelper;

    protected $dataChatFactory;

    protected $chatCollectionFactory;

    protected $dataObjectProcessor;

    protected $extensionAttributesJoinProcessor;

    private $collectionProcessor;

    protected $_helper;
    protected $messageFactory;
    protected $blacklistFactory;
    protected $remoteAddress;
    protected $searchMessageResultsFactory;
    protected $sender;

    /**
     * @param ResourceChat $resource
     * @param ChatFactory $chatFactory
     * @param ChatInterfaceFactory $dataChatFactory
     * @param ChatCollectionFactory $chatCollectionFactory
     * @param ChatSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Lof\ChatSystem\Helper\Data $helper
     * @param ChatMessageFactory $messageFactory
     * @param \Lof\ChatSystem\Model\BlacklistFactory $blacklistFactory
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param MessageSearchResultsInterfaceFactory $searchMessageResultsFactory
     * @param \Lof\ChatSystem\Model\Sender $sender
     */
    public function __construct(
        ResourceChat $resource,
        ChatFactory $chatFactory,
        ChatInterfaceFactory $dataChatFactory,
        ChatCollectionFactory $chatCollectionFactory,
        ChatSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Lof\ChatSystem\Helper\Data $helper,
        ChatMessageFactory $messageFactory,
        \Lof\ChatSystem\Model\BlacklistFactory $blacklistFactory,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        MessageSearchResultsInterfaceFactory $searchMessageResultsFactory,
        \Lof\ChatSystem\Model\Sender $sender
    ) {
        $this->resource = $resource;
        $this->chatFactory = $chatFactory;
        $this->chatCollectionFactory = $chatCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataChatFactory = $dataChatFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->_helper = $helper;
        $this->messageFactory = $messageFactory;
        $this->blacklistFactory = $blacklistFactory;
        $this->remoteAddress = $remoteAddress;
        $this->searchMessageResultsFactory = $searchMessageResultsFactory;
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Lof\ChatSystem\Api\Data\ChatInterface $chat
    ) {
        /* if (empty($chat->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $chat->setStoreId($storeId);
        } */
        
        $chatData = $this->extensibleDataObjectConverter->toNestedArray(
            $chat,
            [],
            \Lof\ChatSystem\Api\Data\ChatInterface::class
        );
        
        $chatModel = $this->chatFactory->create()->setData($chatData);
        
        try {
            $this->resource->save($chatModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the chat: %1',
                $exception->getMessage()
            ));
        }
        return $chatModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($chatId)
    {
        $chat = $this->chatFactory->create();
        $this->resource->load($chat, $chatId);
        if (!$chat->getId()) {
            throw new NoSuchEntityException(__('Chat with id "%1" does not exist.', $chatId));
        }
        return $chat->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->chatCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Lof\ChatSystem\Api\Data\ChatInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Lof\ChatSystem\Api\Data\ChatInterface $chat
    ) {
        try {
            $chatModel = $this->chatFactory->create();
            $this->resource->load($chatModel, $chat->getChatId());
            $this->resource->delete($chatModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Chat: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($chatId)
    {
        return $this->delete($this->get($chatId));
    }

    /**
     * {@inheritdoc}
     */
    public function sendAdminChatMessage(\Lof\ChatSystem\Api\Data\MessageInterface $message){
        if(!$message->getUserId()){
            throw new CouldNotDeleteException(__(
                'User ID is required.'
            ));
        }
        if(!$message->getChatId()){
            throw new CouldNotDeleteException(__(
                'Chat ID is required.'
            ));
        }
        if(!$message->getBodyMsg()){
            throw new CouldNotDeleteException(__(
                'body_msg is required.'
            ));
        }
        $body_msg = $message->getBodyMsg();
        $body_msg = $this->_helper->xss_clean($body_msg);
        $message->setBodyMsg($body_msg);
        $messageModel = $this->messageFactory->create();
        $messageModel->setData($message)->save();

        $chat = $this->chatFactory->create()->load($message->getChatId());
        $number_message = (int)$chat->getData('number_message') + 1;
        $chat
            ->setUserName($message->getUserName())
            ->setData("user_id", (int)$message->getUserId())
            ->setData('is_read',3)
            ->setData('answered',0)
            ->setData('number_message',$number_message)
            ->save();

        if($message->getUserName() && $this->_helper->getConfig('email_settings/enable_email')) {
            $data = $message->__toArray();
            $this->sender->sendAdminChat($data);
        }
        return "sent message to customer!";
    }

    /**
     * {@inheritdoc}
     */
    public function getMyChat($customerId){
        $enable_blacklist = $this->_helper->getConfig('chat/enable_blacklist');
        if ($enable_blacklist) {
            $client_ip = $this->remoteAddress->getRemoteAddress();
            $blacklist_model = $this->blacklistFactory->create(); 
            if ($client_ip) {
                $blacklist_model->loadByIp($client_ip);
                if ((0 < $blacklist_model->getId()) && $blacklist_model->getStatus()) {
                    throw new CouldNotDeleteException(__(
                        'Your IP was blocked in our blacklist. So, you will not get any messages.'
                    ));
                }
            }
            $blacklist_model->loadByCustomerId((int)$customer_id);
            if ((0 < $blacklist_model->getId()) && $blacklist_model->getStatus()) {
                throw new CouldNotDeleteException(__(
                    'Your Account was blocked in our blacklist. So, you will not get any messages.'
                ));
            }
        }
        $searchResults = $this->searchMessageResultsFactory->create();
        $collection = $this->messageFactory->create()->getCollection()->addFieldToFilter('customer_id',$customerId);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function sendCustomerChatMessage($customerId, \Lof\ChatSystem\Api\Data\MessageInterface $message){
        if(!$message->getBodyMsg()){
            throw new CouldNotDeleteException(__(
                'body_msg is required.'
            ));
        }
        if(!$message->getCustomerId()) {
            $message->setCustomerId((int)$customer_id);
        }
        $enable_blacklist = $this->_helper->getConfig('chat/enable_blacklist');
        if ($enable_blacklist) {
            $client_ip = $this->remoteAddress->getRemoteAddress();
            $blacklist_model = $this->blacklistFactory->create(); 
            if ($client_ip) {
                $blacklist_model->loadByIp($client_ip);
                if ((0 < $blacklist_model->getId()) && $blacklist_model->getStatus()) {
                    throw new CouldNotDeleteException(__(
                        'Your IP was blocked in our blacklist. So, you will not get any messages.'
                    ));
                }
            }
            $blacklist_model->loadByCustomerId((int)$customer_id);
            if ((0 < $blacklist_model->getId()) && $blacklist_model->getStatus()) {
                throw new CouldNotDeleteException(__(
                    'Your Account was blocked in our blacklist. So, you will not get any messages.'
                ));
            }
        }

        $message->setIsRead(1);
        $body_msg = $message->getBodyMsg();
        $body_msg = $this->_helper->xss_clean($body_msg);
        $message->setBodyMsg($body_msg);

        $data = $message->__toArray();

        $messageModel = $this->messageFactory->create();
        $messageModel
                    ->setData($data)
                    ->save();
        $chat = $this->chatFactory->create()->load((int)$message->getChatId());
        $number_message = (int)$chat->getData('number_message') + 1;

        $enable_auto_assign_user = $this->_helper->getConfig('system/enable_auto_assign_user');
        $admin_user_id = $this->_helper->getConfig('system/admin_user_id');
        $user_id = 0;
        if($enable_auto_assign_user && $admin_user_id){
            $user_id = (int)$admin_user_id;
        }
        $chat
            ->setData('user_id', (int)$user_id)
            ->setData('is_read',1)
            ->setData('answered',1)
            ->setData('status',1)
            ->setData('number_message',$number_message)
            ->setData('current_url',$message->getCurrentUrl())
            ->setData('ip', $message->getIp())
            ->save();

        if($this->_helper->getConfig('email_settings/enable_email')) {
            $chatId = $chat->getId();
            $messageChatId = $message->getChatId();
            if(!$messageChatId || ($messageChatId != $chatId)){ //only send email at first chat
                $data['url'] = $message->getCurrentUrl();
                $data["user_id"] = (int)$user_id;
                $this->sender->sendEmailChat($data);
            }
        }
        return "sent message to admin!";
    }
}

