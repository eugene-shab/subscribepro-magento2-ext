<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;

/**
 * Order payment after save observer for storing payment vault record in db
 */
class AfterPaymentSaveObserver implements ObserverInterface
{
    const PAYMENT_OBJECT_DATA_KEY = 'payment';

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    private $paymentTokenRepository;

    /**
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        EncryptorInterface $encryptor,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->encryptor = $encryptor;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * Create payment vault record
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $observer->getDataByKey(self::PAYMENT_OBJECT_DATA_KEY);
        if ($payment->getMethod() !== ApplePayConfigProvider::CODE) {
            return $this;
        }
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $this->getPaymentToken($extensionAttributes);
        if ($paymentToken === null) {
            return $this;
        }

        $paymentToken->setIsVisible(false);
        $this->paymentTokenRepository->save($paymentToken);

        return $this;
    }

    /**
     * Reads Payment token from Order Payment
     *
     * @param OrderPaymentExtensionInterface|null $extensionAttributes
     * @return PaymentTokenInterface | null
     */
    protected function getPaymentToken(OrderPaymentExtensionInterface $extensionAttributes = null)
    {
        if (null === $extensionAttributes) {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        if (null === $paymentToken || empty($paymentToken->getGatewayToken())) {
            return null;
        }

        return $paymentToken;
    }
}
