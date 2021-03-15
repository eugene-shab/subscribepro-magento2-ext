<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Phrase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class AddressSession implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;
    /**
     * @var MessageManagerInterface
     */
    private $messageManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;
    /**
     * @var CustomerSession
     */
    private $customerSession;
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request,
        FormKeyValidator $formKeyValidator,
        MessageManagerInterface $messageManager,
        RedirectFactory $redirectFactory,
        CustomerSession $customerSession,
        ResultFactory $resultFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->resultRedirectFactory = $redirectFactory;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        $defaultBilling = $this->request->getParam('default_billing');
        $defaultShipping = $this->request->getParam('default_shipping');
        $previousDefaultAddress = $this->request->getParam('previous_default_address');
        $newDefaultAddress = $this->request->getParam('new_default_address');

        $customerSession = $this->customerSession;

        $customerSession->setSpAddressData([
            'default_billing' => $defaultBilling,
            'default_shipping' => $defaultShipping,
            'previous_default_address' => $previousDefaultAddress,
            'new_default_address' => $newDefaultAddress
        ]);

        $this->messageManager->addSuccessMessage('The address was saved to session');

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setHttpResponseCode(200);
        $resultJson->setData([
            'success' => true,
            'allow_submit' => true
        ]);

        return $resultJson;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Invalid Form Key. Please refresh the page.')]
        );
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $request->isPost() && $this->formKeyValidator->validate($request);
    }
}
