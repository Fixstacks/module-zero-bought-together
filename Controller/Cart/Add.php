<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\BoughtTogether\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Zero\BoughtTogether\Model\Cart;

/**
 * The form's Add to Cart: one of each picked product, then back to the
 * product page (or the cart, per Sales > Checkout > "After Adding a
 * Product Redirect to Shopping Cart").
 */
class Add implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Cart $cart,
        private readonly ManagerInterface $messageManager,
        private readonly RedirectFactory $redirectFactory,
        private readonly RedirectInterface $redirect,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $ids = array_map('intval', (array) $this->request->getParam('with', []));
        if (!$ids) {
            $this->messageManager->addErrorMessage((string) __('Pick at least one item.'));
        } else {
            $result = $this->cart->add($ids);
            foreach ($result['failed'] as $reason) {
                $this->messageManager->addErrorMessage($reason);
            }
            if ($result['added']) {
                $this->messageManager->addSuccessMessage(
                    (string) __('You added %1 to your shopping cart.', implode(', ', $result['added']))
                );
            }
        }
        $toCart = $this->scopeConfig->isSetFlag('checkout/cart/redirect_to_cart', ScopeInterface::SCOPE_STORE);
        $referer = $this->redirect->getRefererUrl();
        $redirect = $this->redirectFactory->create();

        return $toCart || !$referer ? $redirect->setPath('checkout/cart') : $redirect->setUrl($referer);
    }
}
