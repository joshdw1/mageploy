<?php
/**
 * Shopping Cart Price Rule Tracker
 *
 * @author Josh Williams <josh.williams@gmail.com>
 */
class PugMoRe_Mageploy_Model_Action_Promotion_ShoppingCartRule extends PugMoRe_Mageploy_Model_Action_Abstract {
    const VERSION = '1';
    
    protected $_code = 'promo_quote';
    protected $_blankableParams = array('key', 'form_key');

    protected function _getVersion()
    {
        return Mage::helper('pugmore_mageploy')->getVersion(2).'.'.self::VERSION;
    }
    
    public function match() {
        if (!$this->_request) {
            return false;
        }

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'promo_quote') {
                if (in_array($this->_request->getActionName(), array('save'))) {
                    return true;
                }
            }
        }

        return false;
    }
    
    public function encode()
    {
        $result = parent::encode();

        if ($this->_request) {
            $params = $this->_request->getParams();

            /* Customer Group IDs */
            $collection = Mage::getModel('customer/group')
                            ->getCollection()
                            ->addFieldToFilter('customer_group_id', array('in' => $params['customer_group_ids']));

            $params['customer_group_ids'] = array();
            
            foreach ($collection as $r) {
                $params['customer_group_ids'][] = $r->getCustomerGroupCode();    
            }

            foreach ($this->_blankableParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }

            /* Website IDs */
            $collection = Mage::getModel('core/website')
                            ->getCollection()
                            ->addFieldToFilter('website_id', array('in' => $params['website_ids']));

            $params['website_ids'] = array();

            foreach ($collection as $r) {
                $params['website_ids'][] = $r->getCode();
            }

            $new = 'new';
            if (isset($params['rule_id'])) {
                $new = 'existing';
            }

            $result[self::INDEX_EXECUTOR_CLASS] = get_class($this);
            $result[self::INDEX_CONTROLLER_MODULE] = $this->_request->getControllerModule();
            $result[self::INDEX_CONTROLLER_NAME] = $this->_request->getControllerName();
            $result[self::INDEX_ACTION_NAME] = $this->_request->getActionName();
            $result[self::INDEX_ACTION_PARAMS] = $this->_encodeParams($params);
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s shopping cart price rule '%s'", ucfirst($this->_request->getActionName()), $new, $params['name']);
            $result[self::INDEX_VERSION] = $this->_getVersion();
        }
        
        return $result;
    }

    public function decode($encodedParameters, $version)
    {
        // The !empty() ensures that rows without a version number can be 
        // executed (not without any risk).
        if (!empty($version) && $this->_getVersion() != $version) {
            throw new Exception(sprintf("Can't decode the Action encoded with %s Tracker v %s; current Shopping Cart Price Fule Tracker is v %s ", $this->_code, $version, $this->_getVersion()));
        }

        $parameters = $this->_decodeParams($encodedParameters);
        
        /* Customer Group IDs */
        $collection = Mage::getModel('customer/group')
                        ->getCollection()
                        ->addFieldToFilter('customer_group_code', array('in' => $parameters['customer_group_ids']));

        $parameters['customer_group_ids'] = array();
        
        foreach ($collection as $r) {
            $parameters['customer_group_ids'][] = $r->getCustomerGroupId();    
        }

        foreach ($this->_blankableParams as $key) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }

        /* Website IDs */
        $collection = Mage::getModel('core/website')
                        ->getCollection()
                        ->addFieldToFilter('code', array('in' => $parameters['website_ids']));

        $parameters['website_ids'] = array();

        foreach ($collection as $r) {
            $parameters['website_ids'][] = $r->getWebsiteId();
        }

        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request->setQuery($parameters);
        return $request;
    }
    
}
