<?php

declare(strict_types=1);

namespace Elgentos\AiCategoryHeroImages\Controller\Adminhtml\Image;

use Elgentos\AiCategoryHeroImages\Model\CategoryImageManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Elgentos\AiCategoryHeroImages\Model\ImageGenerator;

class Generator extends Action implements HttpPostActionInterface
{
    /** @var string */
    const ADMIN_RESOURCE = 'Magento_Catalog::categories';  // Adjust permission if needed

    /**
     * @param \Elgentos\AiCategoryHeroImages\Model\CategoryImageManager $categoryImageManager
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Elgentos\AiCategoryHeroImages\Model\ImageGenerator $imageGenerator
     */
    public function __construct(
        protected CategoryImageManager $categoryImageManager,
        protected Context $context,
        protected JsonFactory $resultJsonFactory,
        protected ImageGenerator $imageGenerator
    ) {
        parent::__construct($context);
    }

    /**
     * Execute method for AI Image Generation
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        $prompt = $this->getRequest()->getParam('prompt');
        $categoryId = $this->getRequest()->getParam('category_id');
        $numProducts = $this->getRequest()->getParam('num_poducts') ?? 3;

        if (!$prompt) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('No prompt provided.')
            ]);
        }

        if (!$categoryId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('No category ID provided.')
            ]);
        }

        try {
            $products = $this->categoryImageManager->getRandomProductsFromCategory((int) $categoryId, $numProducts);
            $productImages = $this->categoryImageManager->getProductImagePaths($products, $numProducts);
            $category = $this->categoryImageManager->getCategory((int) $categoryId);
            $prompt = $this->categoryImageManager->generatePrompt($products, $category);
            $image = $this->imageGenerator->generateImage($prompt, $productImages);
            $imageData = $this->categoryImageManager->saveCategoryImage($image);

            if ($imageData) {
                return $resultJson->setData([
                    'success' => true,
                    'image'   => $imageData
                ]);
            }

            return $resultJson->setData([
                'success' => false,
                'message' => __('Failed to save generated image.')
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
