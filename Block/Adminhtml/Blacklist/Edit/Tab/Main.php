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

class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
    protected $_helper;

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
        \Lof\ChatSystem\Helper\Data $helper,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->groupRepository = $groupRepository;
        $this->_objectConverter = $objectConverter;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_templatesFactory = $templatesFactory;
        $this->_emailConfig = $emailConfig;
        $this->_helper = $helper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('lofchatsystem_blacklist');

        if ($this->_isAllowedAction('Lof_ChatSystem::blacklist_edit')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }
        $this->_eventManager->dispatch(
            'lof_check_license',
            ['obj' => $this,'ex'=>'Lof_ChatSystem']
        );
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('blacklist_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Blacklist Information')]);

        $message_link = $form_link = '';
        if ($model->getId()) {
            $fieldset->addField('blacklist_id', 'hidden', ['name' => 'blacklist_id']);
        }
        $disable_editable = $isElementDisabled;

        if ($model->getId()) {
            $disable_editable = true;
            $attr = 'readonly';
            $emailAndIpIsUnique='';
        } else {
            $attr = 'disabled';
        }

        $fieldset->addField(
            'ip',
            'text',
            [
                'name'     => 'ip',
                'label'    => __('IP address'),
                'title'    => __('IP address'),
                'required' => false,
                'class' => 'ip',
                $attr => $disable_editable
            ]
        );

        $fieldset->addField(
            'email',
            'text',
            [
                'name'     => 'email',
                'label'    => __('Email address'),
                'title'    => __('Email address'),
                'required' => false,
                'class' => 'email',
                $attr => $disable_editable,

            ]
        );

        $fieldset->addField(
            'form_id',
            'text',
            [
                'name' => 'form_id',
                'label' => __('Form Id'),
                'title' => __('Form Id'),
                $attr => $disable_editable
            ]
        );

        $fieldset->addField(
            'message_id',
            'text',
            [
                'name' => 'message_id',
                'label' => __('Message Id'),
                'title' => __('Message Id'),
                $attr => $disable_editable
            ]
        );

        $fieldset->addField(
            'note',
            'textarea',
            [
                'name'     => 'note',
                'label'    => __('Note'),
                'title'    => __('Note'),
                'required' => false,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'label'    => __('Status'),
                'title'    => __('Status'),
                'name'     => 'status',
                'options'  => $model->getAvailableStatuses(),
                'disabled' => $isElementDisabled
            ]
        );
        if ($model->getId()) {
            if ($form_link) {
                $fieldset->addField(
                    'form_name',
                    'note',
                    [
                        'name' => 'form_name',
                        'label' => __('Form Profile'),
                        'title' => __('Form Profile'),
                        'text' => $form_link,
                        'class' => 'validate-email',
                    ]
                );
            }
            if ($message_link) {
                $fieldset->addField(
                    'message',
                    'note',
                    [
                        'name' => 'message',
                        'label' => __('Message'),
                        'title' => __('Message'),
                        'text' => $message_link
                    ]
                );
            }

            $fieldset->addField(
                'created_time',
                'note',
                [
                    'name' => 'created_time',
                    'label' => __('Created At'),
                    'title' => __('Created At'),
                    'text' => $this->_helper->FormatDateFormBuilder($model->getCreatedTime()),
                ]
            );
        }
        if (!$model->getId()) {
            $model->setData('status', $isElementDisabled ? '1' : '0');
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Blacklist Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Blacklist Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
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

    public function _afterToHtml($html)
    {
        $script = $this->getScript();
        return parent::_afterToHtml($html).$script; // TODO: Change the autogenerated stub
    }
    public function getScript()
    {
        return '
        <script type="text/x-magento-init">
             {
                   "*": {
                       "Lof_ChatSystem/js/LofChatSystemValidationRule": {}
                   }
               }
        </script>
        ';
    }
}
