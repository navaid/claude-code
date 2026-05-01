var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/shipping-rates-validation-rules': {
                'Amasty_Shiprestriction/js/model/shipping-rates-validation-rules-mixin': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Amasty_Shiprestriction/js/view/shipping-mixin': true
            }
        }
    }
};
