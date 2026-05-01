define([
    'underscore'
], function (_) {
    'use strict';

    return {

        /**
         * Convert to condition input value
         *
         * @param {Array} rows
         * @return {String}
         */
        toInputValue: function (rows) {
            var sourceCodes = [];

            _.each(rows, function (row) {
                if (row.source_code) {
                    sourceCodes.push(row.source_code);
                }
            });

            return sourceCodes.join(',');
        },

        /**
         * Convert to grid rows value
         *
         * @param {String} inputValue
         * @return {Array}
         */
        toListingValue: function (inputValue) {
            var valueArray = inputValue.trim().split(','),
                rows = [];

            if (_.isEmpty(inputValue.trim())) {
                return rows;
            }

            _.each(valueArray, function (sourceCode) {
                rows.push({source_code: sourceCode})
            });

            return rows;
        }
    };
});
