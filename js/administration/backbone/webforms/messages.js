define(['jquery', 'tabs'], function ($, tabs) {

    // Return module with methods
    return {
        init: function (tabName, allTabs, page) {

            var MessagesModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/messages',
            });

            var MessagesToDeleteModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/messages',
                default: {
                    ids: []
                }
            });

            var messageIdsToRemove = [];

            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.renderFooterBlock();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                    this.collectionReqPlugins = new MessagesModel();
                    this.limit = 15;
                    this.resetMessagesIds();
                    this.collectionReqPlugins.attributes.begin = this.getBeginPage(page);
                    this.collectionReqPlugins.attributes.limit = this.limit;
                    this.getPlugins();

                },
                events: {
                    "click button.save": "saveData",
                    "click button.cancel": "cancelData"
                },
                resetMessagesIds: function () {
                    messageIdsToRemove = [];
                },
                cancelData: function () {
                    this.resetMessagesIds();
                    this.getPlugins();
                },
                saveData: function () {
                    var self = this;
                    var optionsSync = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            var message = new Messages();
                            message.renderSaveMessage();
                            self.getPlugins();
                        }
                    });
                    var deleteMessagesModel = new MessagesToDeleteModel;
                    deleteMessagesModel.set({ids: messageIdsToRemove});
                    generalSettings.sync('delete', deleteMessagesModel, optionsSync);
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger save">' + getTranslate("frontend.footer.buttons.save") + '</button></div></footer>');
                },
                getPlugins: function () {
                    var self = this;

                    var optionsSync = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            self.collection = new MessagesModel();
                            self.collection.set(data);
                            self.render();
                        }
                    });
                    generalSettings.sync('read', this.collectionReqPlugins, optionsSync);
                },
                getBeginPage: function (pageNum) {
                    if (!pageNum) return 0;
                    return (parseInt(pageNum) - 1) * this.limit;
                },
                changePage: function (pageNum) {
                    this.collectionReqPlugins.attributes.begin = this.getBeginPage(pageNum);
                    this.getPlugins();
                },
                render: function () {
                    this.renderList();
                },
                renderList: function () {
                    this.wrapTabBlock.find(".list_references_block").remove();
                    this.wrapTabBlock.append('<div class="list_references_block"><ul class="list_references"></ul></div>');
                    _.each(this.collection.attributes.items, function (references) {
                        this.renderItems(references);
                    }, this);
                    if (this.collection.attributes.limit < this.collection.attributes.total) {
                        this.renderPagination();
                    }
                },
                renderPagination: function () {
                    var paginationView = new PaginationView({
                        model: this.collection.attributes
                    });
                    paginationView.setUrlButton("/webforms/messages/");
                    paginationView.setViewContent(directoryView);
                    this.$el.find('.list_references_block').append(paginationView.render().el);
                },
                renderItems: function (references) {
                    var listReferenceView = new ListReferenceView({
                        model: references
                    });
                    this.$el.find('.list_references').append(listReferenceView.render().el);
                },
                renderH1: function () {
                    this.$el.find('.directory_name_block, .editH1BlockForm').remove();
                    this.$el.prepend(this.templateDirectoryName(this.collection.attributes));
                }
            });

            var listViewTemplate = "<div class='list_field_block noFlex'><div class='field_data_block'><span class='fieldName'><a href='#!/webforms/messages/message/<%= id %>' id='<%= id %>' ><%= name %></a></span></div><a class='btn btn-danger deleteField deleteButton'><i class='fa fa-times'></i></a><div>(<%= date %>)</div></div>";
            var ListReferenceView = Backbone.View.extend({
                tagName: "li",
                className: "list_edit",
                template: _.template(listViewTemplate),
                render: function () {
                    this.$el.html(this.template(this.model));
                    return this;
                },
                events: {
                    "click .deleteField": "deleteField"
                },
                deleteField: function () {
                    this.$el.remove();
                    messageIdsToRemove.push(this.model.id);
                }
            });


            var directoryView = new DirectoryView();
        }
    }
});
