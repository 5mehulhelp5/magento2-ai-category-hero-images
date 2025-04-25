<?php

declare(strict_types=1);

namespace Elgentos\AiCategoryHeroImages\Console\Command;

use Elgentos\AiCategoryHeroImages\Model\CategoryImageManager;
use Elgentos\AiCategoryHeroImages\Model\Config;
use Elgentos\AiCategoryHeroImages\Model\ImageGenerator;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCategoryHeroImage extends Command
{
    private const CATEGORY_ID_ARGUMENT = 'category_id';
    private const NUM_PRODUCTS_OPTION = 'num-products';

    public function __construct(
        private readonly State $appState,
        private readonly Config $config,
        private readonly CategoryImageManager $categoryImageManager,
        private readonly ImageGenerator $imageGenerator
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('elgentos:aicategoryhero:generate')
            ->setDescription('Generate an AI hero image for a category')
            ->addArgument(
                self::CATEGORY_ID_ARGUMENT,
                InputArgument::REQUIRED,
                'Category ID'
            )
            ->addOption(
                self::NUM_PRODUCTS_OPTION,
                'p',
                InputOption::VALUE_OPTIONAL,
                'Number of products to include in the image',
                3
            );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Set area code
            try {
                $this->appState->setAreaCode(Area::AREA_GLOBAL);
            } catch (LocalizedException $e) {
                // Area code already set
                unset($e);
            }

            // Check if module is enabled
            if (!$this->config->isEnabled()) {
                $output->writeln('<e>Module is disabled in configuration.</e>');
                return Command::FAILURE;
            }

            // Check if API key is configured
            if (!$this->config->getApiKey()) {
                $output->writeln('<e>OpenAI API key is not configured.</e>');
                return Command::FAILURE;
            }

            // Get category ID from input
            $categoryId = (int)$input->getArgument(self::CATEGORY_ID_ARGUMENT);
            $numProducts = (int)$input->getOption(self::NUM_PRODUCTS_OPTION);

            // Get category
            $category = $this->categoryImageManager->getCategory($categoryId);
            if (!$category) {
                $output->writeln("<e>Category with ID {$categoryId} not found.</e>");
                return Command::FAILURE;
            }

            $output->writeln("<info>Generating hero image for category: {$category->getName()}</info>");

            // Get random products from category
            $output->writeln("<info>Selecting {$numProducts} random products from the category...</info>");
            $products = $this->categoryImageManager->getRandomProductsFromCategory($categoryId, $numProducts);

            if ($products->count() === 0) {
                $output->writeln("<e>No products with images found in this category.</e>");
                return Command::FAILURE;
            }

            $output->writeln("<info>Selected " . $products->count() . " products.</info>");

            // Generate prompt
            $prompt = $this->categoryImageManager->generatePrompt($products, $category);
            $output->writeln("<info>Generated prompt for OpenAI:</info>");
            $output->writeln($prompt);

            // Get product images (for future use)
            $output->writeln("<info>Getting product images...</info>");
            $productImages = $this->categoryImageManager->getProductImagePaths($products, $numProducts);
            $output->writeln("<info>Found " . count($productImages) . " product images.</info>");

            // Generate image
            $output->writeln("<info>Generating image with OpenAI using enhanced prompt...</info>");
            $base64Image = $this->imageGenerator->generateImage($prompt, $productImages);

            if (!$base64Image) {
                $output->writeln("<error>Failed to generate image.</error>");
                return Command::FAILURE;
            }

            // Save image and set as category image
            $output->writeln("<info>Saving image and setting as category image...</info>");
            $success = $this->categoryImageManager->saveCategoryImage($base64Image, $category);

            if (!$success) {
                $output->writeln("<e>Failed to save category image.</e>");

                return Command::FAILURE;
            }

            $output->writeln("<info>Generated and set hero image for category {$category->getName()}.</info>");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<e>An error occurred: {$e->getMessage()}</e>");
            return Command::FAILURE;
        }
    }
}
