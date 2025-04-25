<?php

declare(strict_types=1);

namespace Elgentos\AiCategoryHeroImages\Controller\Adminhtml\Image;

use Elgentos\AiCategoryHeroImages\Model\CategoryImageManager;
use Magento\Backend\App\Action;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Config extends Action implements HttpPostActionInterface
{
    /** @var string */
    const ADMIN_RESOURCE = 'Magento_Catalog::categories';  // Adjust ACL as needed

    /**
     * @param \Elgentos\AiCategoryHeroImages\Model\CategoryImageManager $categoryImageManager
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     */
    public function __construct(
        protected CategoryImageManager $categoryImageManager,
        protected Action\Context $context,
        protected JsonFactory $resultJsonFactory,
        private readonly CategoryRepository $categoryRepository,
    ) {
        parent::__construct($context);
    }

    /**
     * Return AI configuration as JSON
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $categoryId = (int) $this->_request->getParam('category_id');

        try {
            $products = $this->categoryImageManager->getRandomProductsFromCategory((int) $categoryId, 5);

            return $resultJson->setData([
                'success'        => true,
                'default_prompt' => $this->categoryImageManager->generatePrompt(
                    $products,
                    $this->categoryRepository->get($categoryId)
                ),
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Failed to load configuration: %1', $e->getMessage())
            ]);
        }
    }
}
