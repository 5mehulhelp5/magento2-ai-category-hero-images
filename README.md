# Elgentos AI Category Hero Images

This Magento 2 extension generates AI-powered hero images for categories using OpenAI's GPT-Image-1 model.

## Features

- Console command to generate AI hero images for categories
- Selects random products from a category and uses their information to generate a prompt
- Enhances the prompt based on product information
- Uses OpenAI's GPT-Image-1 model to create a visually appealing hero image
- Automatically sets the generated image as the category image
- Configurable through the Magento admin panel

## Requirements

- Magento 2.4.x
- PHP 8.1 or higher
- OpenAI API key

## Installation

### Manual Installation

1. Create the following directory structure in your Magento installation: `app/code/Elgentos/AiCategoryHeroImages`
2. Copy all files from this repository to the directory you created
3. Run the following commands:

```bash
bin/magento module:enable Elgentos_AiCategoryHeroImages
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

### Composer Installation

```bash
composer require elgentos/module-ai-category-hero-images
bin/magento module:enable Elgentos_AiCategoryHeroImages
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

## Configuration

1. Go to Stores > Configuration > Elgentos > AI Category Hero Images
2. Enable the module
3. Enter your OpenAI API key
4. (Optional) Enter your OpenAI Organization ID if you have one

## Usage

Run the following command to generate a hero image for a category:

```bash
bin/magento elgentos:aicategoryhero:generate [category_id]
```

Options:
- `--num-products` or `-p`: Number of products to include in the image (default: 3)

Example:
```bash
bin/magento elgentos:aicategoryhero:generate 4 --num-products=5
```

## How It Works

1. The command selects random products from the specified category
2. It generates a prompt based on the category name and product information
3. It enhances the prompt with information about the products
4. The enhanced prompt is sent to OpenAI's GPT-Image-1 model using a direct cURL request
5. The generated image is saved to the Magento media directory
6. The image is set as the category image

## Technical Notes

This module uses a direct cURL implementation to communicate with the OpenAI API instead of the OpenAI PHP client library. This approach was chosen to avoid RFC 7230 header compatibility issues that were encountered with the client library.

## License

[OSL-3.0](https://opensource.org/licenses/OSL-3.0)
