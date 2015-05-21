define(function (require) {
    'use strict';
    var JsplubmBoxView = require('../jsplumb/box'),
        JsplumbWorkflowStepView;

    JsplumbWorkflowStepView = JsplubmBoxView.extend({
        template: require('tpl!oroworkflow/templates/flowchart/editor/step.html'),

        events: {
            'dblclick': 'triggerEditStep',
            'click .jsplumb-source': 'triggerAddStep',
            'click .workflow-step-edit': 'triggerEditStep',
            'click .workflow-step-clone': 'triggerCloneStep',
            'click .workflow-step-delete': 'triggerRemoveStep'
        },

        triggerEditStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestEditStep', this.model);
        },

        triggerRemoveStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestRemoveStep', this.model);
        },

        triggerCloneStep: function (e) {
            e.preventDefault();
            this.areaView.model.trigger('requestCloneStep', this.model);
        },

        triggerAddStep: function () {
            this.areaView.model.trigger('requestAddTransition', this.model);
        }
    });

    return JsplumbWorkflowStepView;
});
