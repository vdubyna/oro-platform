/*global define*/
define(function (require) {
    'use strict';

    var CommentsView,
        BaseView = require('oroui/js/app/views/base/view'),
        CommentsHeaderView = require('./comments-header-view'),
        CommentItemView = require('./comment-item-view'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view'),
        template = require('text!../../../templates/comment/comments-view.html');

    CommentsView = BaseView.extend({
        template: template,
        events: {
            'click .add-comment-button': 'onAddCommentClick',
            'click .item-remove-button': 'onRemoveCommentClick',
            'click .item-edit-button': 'onEditCommentClick',
            'click a.load-more': 'onLoadMoreClick',
            'mouseover .dropdown-toggle': function (e) {
                $(e.target).trigger('click');
            },
            'mouseleave .dropdown-menu': function (e) {
                $(e.target).parent().find('a.dropdown-toggle').trigger('click');
            }
        },

        getTemplateData: function () {
            var data = CommentsView.__super__.getTemplateData.apply(this, arguments);
            data.canCreate = this.canCreate;
            return data;
        },

        initialize: function (options) {
            this.canCreate = options.canCreate;
            CommentsHeaderView.__super__.initialize.apply(this, arguments);
        },

        render: function () {
            CommentsView.__super__.render.apply(this, arguments);
            this.subview('header', window.globalView = new CommentsHeaderView({
                el: this.$('.comments-view-header'),
                collection: this.collection,
                canCreate: this.canCreate,
                autoRender: true
            }));
            this.subview('list', new BaseCollectionView({
                el: this.$('.comments-view-body'),
                animationDuration: 0,
                collection: this.collection,
                itemView: CommentItemView,
                autoRender: true
            }));
        },

        findModelByEl: function ($el) {
            var listView = this.subview('list');
            return this.collection.find(function (model) {
                var itemView = listView.subview('itemView:' + model.cid);
                return itemView && _.contains($el.parents(), itemView.el);
            });
        },

        onLoadMoreClick: function () {
            this.collection.loadMore();
        },

        onAddCommentClick: function () {
            this.trigger('toAdd');
        },

        onEditCommentClick: function (e) {
            this.trigger('toEdit', this.findModelByEl($(e.target)));
        },

        onRemoveCommentClick: function (e) {
            this.trigger('toRemove', this.findModelByEl($(e.target)));
        }
    });

    return CommentsView;
});
