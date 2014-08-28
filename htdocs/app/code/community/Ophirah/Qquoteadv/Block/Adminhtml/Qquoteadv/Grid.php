<?php

class Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('qquoteGrid');
        $this->setSaveParametersInSession(true);
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('desc');
    }


    /*
     * Adding button Create New Quote
     * 
     */
    protected function  _prepareLayout()
    {
        $this->setChild('priceupdate_deactivate_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('qquoteadv')->__('Create New Quote'),
                    'onclick' => 'setLocation(\'' . $this->getCreateQuoteUrl() . '\')',
                    'class' => 'add'
                ))

        );

        // ADDED FollowUp button
        if ($this->getRequest()->getParam('followup')):

            $data = array('label' => Mage::helper('qquoteadv')->__('Reset Follow up'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/*') . '\')',
                'class' => ''
            );

        else:
            $data = array('label' => Mage::helper('qquoteadv')->__('Follow up'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/*/followup/1') . '\')',
                'class' => ''
            );
        endif;

        $this->setChild('follow_up',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData($data)

        );

        return parent::_prepareLayout();
    }

    public function  getSearchButtonHtml()
    {
        return parent::getSearchButtonHtml() . $this->getChildHtml('priceupdate_deactivate_button') . $this->getChildHtml('follow_up');
    }

    public function getCreateQuoteUrl()
    {
        if (Mage::registry('current_customer')) {
            $customer = '/customer_id/' . Mage::registry('current_customer')->getId();
        } else {
            $customer = "";
        }

        // clear old session data from editing quote
        Mage::getSingleton('adminhtml/session_quote')->clear();

        return $this->getUrl('adminhtml/sales_order_create/start' . $customer);
    }

    /*
     * Setting up grid and adding data for display
     * 
     */

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ?
                $column->getFilterIndex() : $column->getIndex();

            if ($columnIndex == 'increment_id') $columnIndex = 'quote_id';
            $collection->setOrder($columnIndex, $column->getDir());
        }
        return $this;
    }

    protected function _prepareCollection()
    {
        $country_id = Mage::getSingleton('admin/session')
            ->getUser()
            ->getRole()
            ->getData('role_name');

        $collection = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()
            ->addFieldToFilter('is_quote', '1')
            ->addFieldToFilter('customer_id', array('gt' => '0'))
            ->addFieldToFilter('status', array('gteq' => Ophirah_Qquoteadv_Model_Status::STATUS_BEGIN));

        // Adding filter for customer quote
        if (Mage::registry('current_customer')) {
            $collection = $collection->addFieldToFilter('customer_id', Mage::registry('current_customer')->getId());
        }

        // Select only trial quote if in trial mode
        if (Mage::helper('qquoteadv')->getAccessLevelFromKey() == null) {
            $newCollection = clone $collection;
            $filterArray = array();
            foreach ($newCollection as $trialFilter) {
                $createHash = array($trialFilter->getCreateHash(), $trialFilter->getIncrementId());
                // Check Trial Hash
                if (Mage::helper('qquoteadv')->isTrialVersion($createHash)) {
                    $filterArray[] = $trialFilter->getQuoteId();
                }
            }

            // Filter Collection
            $collection = $collection->addFieldToFilter('quote_id', array('in' => $filterArray));
        }

        // ADDED filter for Follow Up
        if ($this->getRequest()->getParam('followup') == '1') {
            $collection->addFieldToFilter('no_followup', '0')
                ->addFieldToFilter('followup', array('notnull' => 1));
            $followupIds = $this->getFollowupIds(clone $collection);

            if (is_array($followupIds)) {
                // Filter Collection
                $collection = $collection->addFieldToFilter('quote_id', array('in' => $followupIds));
            }
            // Order collection by reminder date
            if ($collection) {
                $collection->getSelect()->order('followup ASC');
            }
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Get Quote ids for valid reminder dates
     *
     * @param     collection
     * @return    array
     */
    public function getFollowupIds($collection)
    {
        $filterArray = array();
        $currentDate = date('Ymd', Mage::getModel('core/date')->timestamp(time()));

        // Can be used to add an option in the backend to
        // enable to only view follow up from today
        if (Mage::getStoreConfig('qquoteadv/general/followup') == 1):
            foreach ($collection as $followupFilter) {
                $followupDate = date('Ymd', Mage::getModel('core/date')->timestamp($followupFilter->getData('followup')));
                // Check Follow Up Date
                if ($currentDate <= $followupDate) {
                    $filterArray[] = $followupFilter->getQuoteId();
                }
            }

        else:

            foreach ($collection as $followupFilter) {
                $filterArray[] = $followupFilter->getQuoteId();
            }

        endif;

        return $filterArray;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header' => Mage::helper('qquoteadv')->__('Quote #'),
            'align' => 'left',
            'index' => 'increment_id',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('qquoteadv')->__('Created On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));
        $_collection = Mage::getModel('admin/user')->getCollection();
        $adm = array();
        foreach ($_collection as $model) {
            $name = $model->getFirstname() . ' ' . $model->getLastname();
            $adm[$model->getUserId()] = $name;
        }
		/*
        $this->addColumn('user_id', array(
            'header' => Mage::helper('qquoteadv')->__('Assigned to'),
            'width' => '100px',
            'align' => 'left',
            'sortable' => true,
            'index' => 'user_id',
            'type' => 'options',
            'options' => $adm //Ophirah_Qquoteadv_Model_Status::getGridOptionArray()
        ));
		*/

        $this->addColumn('company', array(
            'header' => Mage::helper('qquoteadv')->__('Company'),
            'index' => 'company',
            'width' => '100',
        ));

        $this->addColumn('firstname', array(
            'header' => Mage::helper('qquoteadv')->__('First Name'),
            'index' => 'firstname'
        ));

        $this->addColumn('lastname', array(
            'header' => Mage::helper('qquoteadv')->__('Last Name'),
            'index' => 'lastname'
        ));

        $this->addColumn('email', array(
            'header' => Mage::helper('qquoteadv')->__('Email'),
            'width' => '150',
            'index' => 'email'
        ));

        $this->addColumn('country_id', array(
            'header' => Mage::helper('qquoteadv')->__('Country'),
            'width' => '150',
            'type' => 'country',
            'index' => 'country_id',
        ));

        $this->addColumn('city', array(
            'header' => Mage::helper('qquoteadv')->__('City'),
            'index' => 'city',
            'width' => '100',
        ));
/*
        $this->addColumn('followup', array(
            'header' => Mage::helper('qquoteadv')->__('Follow Up'),
            'index' => 'followup',
            'type' => 'date',
            'width' => '100'
        ));
*/

        $this->addColumn('grand_total', array(
            'header' => Mage::helper('qquoteadv')->__('Grand Total'),
            'align' => 'left',
            'index' => 'grand_total',
			'type' => 'price',
			'currency_code' => Mage::app()->getStore(0)->getBaseCurrency()->getCode(),
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('qquoteadv')->__('Status'),
            'align' => 'left',
            'width' => '120px',
            'index' => 'status',
            'type' => 'options',
            'options' => Ophirah_Qquoteadv_Model_Status::getGridOptionArray(),
            'renderer' => new Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Renderer_Status()
        ));

        $this->addColumn('action',
            array(
                'header' => Mage::helper('qquoteadv')->__('Action'),
                'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('qquoteadv')->__('View'),
                        'url' => array('base' => '*/*/edit'),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
            ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('qquote_id');
        $this->getMassactionBlock()->setFormFieldName('qquote');

        if (Mage::getSingleton('admin/session')->isAllowed('sales/qquoteadv/actions/delete')) {
            $this->getMassactionBlock()->addItem('delete', array(
                'label' => Mage::helper('qquoteadv')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('qquoteadv')->__('Are you sure?')
            ));
        }

        $statuses = Mage::getSingleton('qquoteadv/status')->getChangeOptionArray(true);
        array_unshift($statuses, array('label' => '', 'value' => ''));
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('qquoteadv')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'label' => Mage::helper('qquoteadv')->__('Status'),
                    'values' => $statuses
                )
            )
        ));

        $this->getMassactionBlock()->addItem('set_followup', array(
            'label' => Mage::helper('qquoteadv')->__('Set Follow Up date'),
            'url' => $this->getUrl('*/*/massFollowup'),
            'additional' => array(
                'valid_from' => array(
                    'name' => 'followup',
                    'type' => 'date',
                    'label' => Mage::helper('qquoteadv')->__('Follow Up Date'),
                    'gmtoffset' => true,
                    'image' => $this->getSkinUrl('images/grid-cal.gif'),
                    'format' => '%d-%m-%Y'
                )
            )
        ));

        $this->getMassactionBlock()->addItem('export', array(
            'label' => Mage::helper('qquoteadv')->__('Export'),
            'url' => $this->getUrl('*/*/export'),
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/qquoteadv/edit', array('id' => $row->getId()));
    }

}
