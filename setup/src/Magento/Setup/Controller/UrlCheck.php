<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Setup\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use Magento\Framework\Url\SimpleValidator;

class UrlCheck extends AbstractActionController
{
    /**
     * @var SimpleValidator
     */
    private $simpleUrlValidator;

    /**
     * @param SimpleValidator $simpleUrlValidator
     */
    public function __construct(SimpleValidator $simpleUrlValidator)
    {
        $this->simpleUrlValidator = $simpleUrlValidator;
    }

    /**
     * Validate URL
     *
     * @return JsonModel
     */
    public function indexAction()
    {
        $params = Json::decode($this->getRequest()->getContent(), Json::TYPE_ARRAY);
        $result = ['successUrl' => false, 'successSecureUrl' => true];

        $hasBaseUrl = isset($params['address']['actual_base_url']);
        $hasSecureBaseUrl = isset($params['https']['text']);
        $hasSecureAdminUrl = !empty($params['https']['admin']);
        $hasSecureFrontUrl = !empty($params['https']['front']);

        // Validating of Base URL
        if ($hasBaseUrl && $this->simpleUrlValidator->isValid($params['address']['actual_base_url'])) {
            $result['successUrl'] = true;
        }

        // Validating of Secure Base URL
        if ($hasSecureAdminUrl || $hasSecureFrontUrl) {
            if (!($hasSecureBaseUrl && $this->simpleUrlValidator->isValid($params['https']['text']))) {
                $result['successSecureUrl'] = false;
            }
        }

        return new JsonModel(array_merge($result));
    }
}
