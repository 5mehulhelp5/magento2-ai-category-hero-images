# Elgentos AI Category Hero Images

This Magento 2 extension generates AI-powered hero images for categories using OpenAI's GPT-Image-1 model.

## Features

- Button "Generate with AI" in the admin when on the Category edit page
- Console command to generate AI hero images in bulk for categories
- Selects random products from a category and uses their information to generate a prompt
- Enhances the prompt based on product information
- Uses OpenAI's GPT-Image-1 model to create a visually appealing hero image
- Automatically sets the generated image as the category image
- Configurable through the Magento admin panel

## Requirements

- Magento 2.4.x
- PHP 8.1 or higher
- OpenAI API key
- A [verified organization](https://help.openai.com/en/articles/10910291-api-organization-verification) (after verification it takes about 30 minutes for existing keys to reflect the change)

## Installation

### Composer Installation

1. `composer require elgentos/magento2-ai-category-hero-images`
1. Run the following commands:

```bash
bin/magento module:enable Elgentos_AiCategoryHeroImages
bin/magento setup:upgrade
```

## Configuration

1. Go to Stores > Configuration > Elgentos > AI Category Hero Images
2. Enable the module
3. Enter your OpenAI API key
4. (Optional) Enter your OpenAI Organization ID if you have one

## Usage

Open a category and click the "Generate with AI" button, followed by optionally adapting the prompt to your liking.

Or run the following command to generate a hero image for a category:

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

1. The extension selects random products from the specified category
1. It generates a prompt based on the category name and product information
1. The enhanced prompt is sent to OpenAI's GPT-Image-1 model using a direct cURL request
1. The generated image is saved to the Magento media directory
1. The image is set as the category image

## License

[OSL-3.0](https://opensource.org/licenses/OSL-3.0)
