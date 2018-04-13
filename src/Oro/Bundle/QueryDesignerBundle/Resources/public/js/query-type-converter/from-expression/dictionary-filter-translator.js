define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/abstract-filter-translator');
    var DictionaryFilterTranslatorToExpression =
        require('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;

    /**
     * @inheritDoc
     */
    var DictionaryFilterTranslator = function DictionaryFilterTranslatorFromExpression() {
        DictionaryFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    DictionaryFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    DictionaryFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(DictionaryFilterTranslator.prototype, {
        constructor: DictionaryFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'dictionary',

        /**
         * @inheritDoc
         */
        operatorMap: DictionaryFilterTranslatorToExpression.prototype.operatorMap,

        /**
         * Checks if node has correct type and value
         *
         * @param {Node} node
         * @return {boolean}
         * @protected
         */
        checkValueAST: function(node) {
            return node instanceof ConstantNode && _.isString(node.attrs.value);
        },

        /**
         * @inheritDoc
         */
        resolveOperatorParams: function(node) {
            if (
                node instanceof BinaryNode &&
                node.nodes[0] instanceof GetAttrNode &&
                this.checkListOperandAST(node.nodes[1], this.checkValueAST)
            ) {
                var criterion = _.findKey(this.operatorMap, function(operatorParams) {
                    return operatorParams.operator === node.attrs.operator;
                });

                if (criterion) {
                    return {criterion: criterion, operator: node.attrs.operator};
                }
            }

            return null;
        },

        /**
         * @inheritDoc
         */
        translate: function(node, filterConfig, operatorParams) {
            var fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));

            var condition = {
                columnName: fieldId,
                criterion: {
                    filter: filterConfig.name,
                    data: {
                        type: operatorParams.criterion,
                        value: _.map(node.nodes[1].getKeyValuePairs(), function(pair) {
                            return String(pair.value.attrs.value);
                        })
                    }
                }
            };

            if (filterConfig.filterParams) {
                condition.criterion.data.params = filterConfig.filterParams;
            }

            return condition;
        }
    });

    return DictionaryFilterTranslator;
});
