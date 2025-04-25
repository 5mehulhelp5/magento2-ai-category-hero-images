define([
    'Magento_Ui/js/form/element/image-uploader',
    'Magento_Ui/js/modal/modal',
    'jquery',
    'mage/url',
    'mage/translate',
    'mage/loader'
], function (ImageUploader, modal, $, urlBuilder, $t) {
    'use strict';

    return ImageUploader.extend({
        defaults: {
            configUrl: '',
            generateUrl: '',
            categoryId: null
        },

        initialize: function () {
            this._super();
            this.initAiButton();1
            this.categoryId = this.source.data.entity_id;

            return this;
        },

        /** Initialize AI Button Click **/
        initAiButton: function () {
            let self = this;

            $(document).on('click', '.ai-generate-button', function () {
                self.openAiModal();
            });
        },

        openAiModal: function () {
            let self = this;

            $('body').loader('show');

            $.ajax({
                url: urlBuilder.build(this.configUrl),
                type: 'POST',
                data: { category_id: this.categoryId },
                success: function (response) {
                    if (response.success) {
                        self.default_prompt = response.default_prompt || '';

                        self.renderAiModal();
                    } else {
                        alert($t('Failed to load AI configuration.'));
                    }
                },
                error: function () {
                    alert($t('Error loading AI configuration.'));
                },
                complete: function () {
                    $('body').loader('hide');
                }
            });
        },

        /** Render the Modal **/
        renderAiModal: function () {
            let self = this;

            if ($('#ai-generate-modal').length) {
                $('#ai-generate-modal').modal('openModal');
                return;
            }

            let modalHtml = `
                <div id="ai-generate-modal">
                    <textarea type="text" id="ai-prompt" placeholder="${$t('Enter image description...')}"
                    style="width: 100%; margin-bottom: 10px;" rows="10">${self.default_prompt}</textarea>
                    <button id="ai-generate-button" class="action-primary">${$t('Generate')}</button>
                </div>
            `;

            $('body').append(modalHtml);

            let options = {
                type: 'popup',
                title: $t('Generate Image with AI'),
                buttons: false
            };

            modal(options, $('#ai-generate-modal'));
            $('#ai-generate-modal').modal('openModal');

            $('#ai-generate-button').on('click', function () {
                let prompt = $('#ai-prompt').val();
                if (prompt.trim() !== '') {
                    self.generateAiImage(prompt);
                } else {
                    alert($t('Please enter a prompt.'));
                }
            });
        },

        /** Generate AI Image using Configured URL **/
        generateAiImage: function (prompt) {
            let self = this;
            $('body').loader('show');

            $.ajax({
                url: self.generateUrl,
                type: 'POST',
                data: { prompt: prompt, category_id: this.categoryId },
                success: function (response) {
                    if (response.success && response.image) {
                        self.addGeneratedImage(response.image);
                        $('#ai-generate-modal').modal('closeModal');
                    } else {
                        alert($t('Image generation failed.'));
                    }
                },
                error: function () {
                    alert($t('An error occurred while generating the image.'));
                },
                complete: function () {
                    $('body').loader('hide');
                }
            });
        },

        addGeneratedImage: function (imageData) {
            this.value(imageData);
        }
    });
});
