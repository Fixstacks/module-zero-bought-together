<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\BoughtTogether\Controller\Total;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\View\Result\LayoutFactory;
use Zero\Core\Controller\PrivateFragmentActionInterface;

/**
 * HTMX: the total and the button for the checked products (with[]).
 */
class Index implements PrivateFragmentActionInterface
{
    private const BLOCK_NAME = 'zero.bought.together.summary';

    public function __construct(
        private readonly LayoutFactory $resultLayoutFactory,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @return Layout
     */
    public function execute()
    {
        $result = $this->resultLayoutFactory->create();
        $result->addDefaultHandle();
        $result->getLayout()->getBlock(self::BLOCK_NAME)?->setData('ids', (array) $this->request->getParam('with', []));
        $result->getLayout()->addOutputElement(self::BLOCK_NAME);

        return $result;
    }
}
