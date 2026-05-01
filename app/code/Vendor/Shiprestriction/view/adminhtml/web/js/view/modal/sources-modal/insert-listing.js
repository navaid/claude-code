define([
    'Magento_Ui/js/form/components/insert-listing',
    'uiRegistry'
], function (InsertListing, registry) {
    'use strict';

    return InsertListing.extend({
        setExternalValue: function (newValue) {
            this._super(newValue);

            if (this.currentInput) {
                registry.get(this.currentInput, function (component) {
                    component.sources(newValue);
                }.bind(this));
            }
        }
    });
});
