define([
    'jquery',
    'Magento_Ui/js/form/components/button',
    'uiRegistry',
    'Amasty_Shiprestriction/js/model/sources-converter'
], function ($j, Element, registry, converter) {
    'use strict';

    return Element.extend({
        defaults: {
            sources: [],
            listens: {
                sources: 'onSourcesChange'
            },
            modules: {
                listing: 'index = assign_sources_grid'
            }
        },

        initialize: function (config, element) {
            this._super();

            this.chooser = $j(element);
            this.chooserInput = $j(element).parent().prev();

            this.chooser.on('click', this.action.bind(this));
            registry.set(this.name, this);

            return this;
        },

        initObservable: function () {
            this._super();

            this.observe(['sources']);

            return this;
        },

        /**
         * Handler to change input value when sources selected on grid
         *
         * @param {Array} newSources
         */
        onSourcesChange: function (newSources) {
            this.chooserInput.val(converter.toInputValue(newSources));
        },

        /**
         * Handler to change grid rows according to input value
         */
        onModalOpen: function () {
            var inputValue = this.chooserInput.val(),
                listingValue = converter.toListingValue(inputValue);

            this.listing().externalValue(listingValue);
        }
    });
});
