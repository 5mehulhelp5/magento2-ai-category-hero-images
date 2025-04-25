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

                // Check if we have valid image resources
                if (empty($images)) {
                    $this->logger->warning('No valid image resources found. Falling back to text-only prompt.');

                    // Use the create endpoint for text-only prompts
                    $result = $client->images()->create([
                        'model' => 'gpt-image-1',
                        'prompt' => $prompt,
                        'size' => '1024x1024',
                        'quality' => 'high',
                        'n' => 1,
                    ]);

                    $base64Image = $result->data[0]->b64_json;
                } else {
                    try {
                        // Use the first image (we're only getting one in prepareImageFiles now)
                        $result = $client->images()->edit([
                            'model' => 'gpt-image-1',
                            'image' => $images[0], // Use the CURLFile object
                            'prompt' => $prompt . ' The image should incorporate elements from ' . (count($imageFiles) - 1) . ' other product images.',
                            'size' => '1024x1024',
                            'quality' => 'high',
                            'n' => 1,
                        ]);

                        $base64Image = $result->data[0]->b64_json;
                    } catch (Exception $e) {
                        $this->logger->error('Error in image edit: ' . $e->getMessage(), [
                            'exception' => $e
                        ]);
                        // Fall back to text-only generation
                        $result = $client->images()->create([
                            'model' => 'gpt-image-1',
                            'prompt' => $prompt . ' The image should incorporate elements from ' . count($imageFiles) . ' product images.',
                            'size' => '1024x1024',
                            'quality' => 'high',
                            'n' => 1,
                        ]);

                        $base64Image = $result->data[0]->b64_json;
                    }
                }
            } else {
                $this->logger->info('Generating image with text prompt only');

                // Use the create endpoint for text-only prompts
                $result = $client->images()->create([
                    'model' => 'gpt-image-1',
                    'prompt' => $prompt,
                    'size' => '1024x1024',
                    'quality' => 'high',
                    'n' => 1,
                ]);

                $base64Image = $result->data[0]->b64_json;
            }

            return $base64Image;
        } catch (Exception $e) {
            $this->logger->error('Error generating image: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // No need to close file resources as we're using CURLFile objects now

            // If the error is related to the image_inputs parameter or MultipartStreamBuilder, try again with text-only
            if ((strpos($e->getMessage(), 'unknown parameter') !== false ||
                strpos($e->getMessage(), 'MultipartStreamBuilder') !== false) &&
                !empty($imageFiles)) {
                $this->logger->info('Retrying with text-only prompt due to API parameter error');
                try {
                    $result = $client->images()->create([
                        'model' => 'gpt-image-1',
                        'prompt' => $prompt . ' The image should incorporate elements from ' . count($imageFiles) . ' product images.',
                        'size' => '1024x1024',
                        'quality' => 'high',
                        'n' => 1,
                    ]);

                    return $result->data[0]->b64_json;
                } catch (Exception $retryException) {
                    $this->logger->error('Error in retry attempt: ' . $retryException->getMessage(), [
                        'exception' => $retryException
                    ]);
                }
            }

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

        // If we have multiple images, just use the first one for now
        // This is a temporary solution until we can properly handle multiple images
        if (count($imageFiles) > 0) {
            $imagePath = $imageFiles[0];

            if (file_exists($imagePath)) {
                try {
                    // Create a CURLFile object which is what the OpenAI PHP client expects
                    $images[] = new \CURLFile($imagePath, 'image/png', basename($imagePath));
                } catch (Exception $e) {
                    $this->logger->warning('Error processing image file: ' . $e->getMessage(), [
                        'exception' => $e,
                        'file' => $imagePath
                    ]);
                }
            } else {
                $this->logger->warning('Image file does not exist: ' . $imagePath);
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
