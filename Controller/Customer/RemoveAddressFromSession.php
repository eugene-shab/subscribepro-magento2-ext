<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Customer;

use Magento\Framework\App\Action\HttpGetActionInterface;
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

class RemoveAddressFromSession implements HttpGetActionInterface, CsrfAwareActionInterface
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
        $customerSession = $this->customerSession;
        $customerSession->setSpAddressData([]);

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setHttpResponseCode(200);
        $resultJson->setData([
            'success' => true,
            'unset' => true
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
        return true;
    }
}
