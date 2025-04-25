<?php

declare(strict_types=1);

namespace Elgentos\AiCategoryHeroImages\Model;

use Exception;
use OpenAI;
use OpenAI\Client;
use Psr\Log\LoggerInterface;

class ImageGenerator
{
    private ?Client $client = null;

    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate an image using OpenAI's API
     *
     * @param string $prompt The prompt to generate the image from
     * @param array $imageFiles Optional array of image file paths to include in the generation
     * @return string|null Base64 encoded image data or null if generation failed
     */
    public function generateImage(string $prompt, array $imageFiles = []): ?string
    {
        try {
            $client = $this->getClient();

            if (!$client) {
                $this->logger->error('OpenAI client not initialized. Check API key configuration.');
                return null;
            }

            // If we have image files, use the edit endpoint
            if (!empty($imageFiles)) {
                $this->logger->info('Generating image with text prompt and ' . count($imageFiles) . ' product images');

                // Prepare the image files for the API
                $images = $this->prepareImageFiles($imageFiles);

                $base64Image = null;

                // Check if we have valid image resources
                if (empty($images)) {
                    $this->logger->error('Error generating image - empty images variable');
                } else {
                    try {
                        // Use the first image (we're only getting one in prepareImageFiles now)
                        $result = $client->images()->edit([
                            'model' => 'gpt-image-1',
                            'image' => $images,
                            'prompt' => $prompt,
                            'size' => '1024x1024',
                            'quality' => 'high',
                            //'response_format' => 'url',
                            'n' => 1,
                        ]);

                        $base64Image = $result->data[0]->b64_json;
                    } catch (Exception $e) {
                        $this->logger->error('Error generating image: ' . $e->getMessage(), [
                            'exception' => $e
                        ]);
                    }
                }
            } else {
                $this->logger->error('Error generating image - empty imageFiles variable');
            }

            return $base64Image;
        } catch (Exception $e) {
            $this->logger->error('Error generating image: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return null;
        }
    }

    /**
     * Prepare image files for the OpenAI API
     *
     * @param array $imageFiles Array of image file paths
     * @return array Array of image data in the format expected by the OpenAI PHP client
     */
    private function prepareImageFiles(array $imageFiles): array
    {
        $images = [];

        if (count($imageFiles) > 0) {
            foreach ($imageFiles as $imagePath) {
                if (file_exists($imagePath)) {
                    try {
                        // Create a CURLFile object which is what the OpenAI PHP client expects
                        $images[] = new \CURLFile($imagePath, 'image/png',
                            basename($imagePath));
                    } catch (Exception $e) {
                        $this->logger->warning('Error processing image file: ' . $e->getMessage(),
                            [
                                'exception' => $e,
                                'file' => $imagePath
                            ]);
                    }
                } else {
                    $this->logger->warning('Image file does not exist: ' . $imagePath);
                }
            }
        }

        return $images;
    }

    /**
     * Get the OpenAI client
     *
     * @return Client|null
     */
    private function getClient(): ?Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $apiKey = $this->config->getApiKey();
        if (!$apiKey) {
            $this->logger->error('OpenAI API key not configured');
            return null;
        }

        $factory = OpenAI::factory()
            ->withApiKey($apiKey);

        $organizationId = $this->config->getOrganizationId();
        if ($organizationId) {
            $factory->withOrganization($organizationId);
        }

        $this->client = $factory->make();
        return $this->client;
    }
}
