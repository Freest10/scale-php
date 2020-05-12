define(['jquery', 'jstree', 'jstree.dnd', 'jstree.search'], function ($, tree_functions) {

    // Return module with methods
    return {
        init: function () {

            var footerTemplate = '<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default delete-active-tree">' + getTranslate("frontend.footer.buttons.de_select") + '</button><button type="button" class="btn btn-w-m btn-default deletePage" disabled>' + getTranslate("frontend.footer.buttons.delete") + '</button><button type="button" class="btn btn-w-m btn-danger addpage">' + getTranslate("frontend.footer.buttons.add") + '</button></div></footer>';

            var PageModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/page',
                subDomainId: null
            });

            var ChangeSortOrParentFoPage = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/change_sort_or_parent_of_page'
            });

            var ContentPartTemplatesTypeView = Backbone.View.extend({
                el: $("#content-block"),
                templateFooter: _.template(footerTemplate),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    var self = this;
                    self.treeView = tree.init();
					self.$el.append(tree.getSearchTree());
					
                    self.$el.append(self.treeView.el);
                    var treeRequest = tree.requestToData('/page', {dnd: true}, {subDomain: getCookie('subDomain')});
                    treeRequest.get();
                    $(self.treeView.el).on('loaded.jstree', function () {
                        $('.jstree-anchor[href="#"]').on('click', function () {
                            return false;
                        })
                    }).on("move_node.jstree", function (e, data) {
                        self.changePageSortOrParent(data);
                    });
                    this.treeView.$el.on("click.jstree", function (e, data) {
                        tree.removeClassActiveNode();
                        var target = $(e.target);
                        if (target.is('li')) {
                            target.addClass('activeLiTree');
                            if ($('.activeLiTree').length > 0) {
                                var idPage = parseInt($(e.target).attr('id'));
                                if (!idPage) {
                                    idPage = 0;
                                }
                                self.idSelectedNode = [idPage];
                                self.disableDeleteButton(true);
                            }
                        } else {
                            self.disableDeleteButton();
                        }
                    });
                    this.showFooter();
                    this.render();
                },
                changePageSortOrParent: function (data) {
                    var parentId = 0;
                    var parseParentId = parseInt(data.parent);
                    if (parseParentId) {
                        parentId = parseParentId;
                    }
                    var pageId = parseInt(data.node.id);
                    var self = this;
                    this.changeSortOrParentFoPage_model = new ChangeSortOrParentFoPage();
                    this.changeSortOrParentFoPage_model.attributes.page_id = pageId;
                    this.changeSortOrParentFoPage_model.attributes.parent_id = parentId;
                    this.changeSortOrParentFoPage_model.attributes.position = data.position;
                    this.changeSortOrParentFoPage_model.attributes.old_position = data.old_position;
                    generalSettings.sync('update', this.changeSortOrParentFoPage_model);
                },
                showFooter: function () {
                    this.$el.append(this.templateFooter());
                },
                events: {
                    "click button.addpage": "addPage",
                    "click button.delete-active-tree": "deleteActiveTemplate",
                    "click button.deletePage": "deletePage"
                },
                updatePagesTree: function () {
                    var self = this;
                    var pagesModelOptions = ({
                        data: {subDomain: getCookie('subDomain')},
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            var arrData = [data.changed];
                            tree.setNewData(self.treeView.$el.attr('id'), arrData);
                            self.idSelectedNode = null;
                            self.disableDeleteButton();
                        }
                    });
                    new PageModel().fetch(pagesModelOptions);
                },
                addPage: function () {
                    var self = this;
                    this.modelToAddPage = new PageModel();
                    (this.idSelectedNode == null) ? idParent = 0 : idParent = this.idSelectedNode[0];
                    this.modelToAddPage.set("id", idParent);
                    this.modelToAddPage.set("subDomain", getCookie('subDomain'));
                    var optionsPage = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            var message = new Messages();
                            message.renderSaveMessage();
                            self.updatePagesTree();
                        }
                    });
                    generalSettings.sync('create', this.modelToAddPage, optionsPage);
                },
                deletePage: function () {
                    var self = this;
                    this.modelToDeletePage = new PageModel();
                    if (typeof this.idSelectedNode == 'undefined') return false;
                    this.modelToDeletePage.attributes.ids = JSON.stringify(this.idSelectedNode);
                    var optionsPage = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function () {
                            self.updatePagesTree();
                        }
                    });
                    generalSettings.sync('delete', this.modelToDeletePage, optionsPage);
                },
                disableDeleteButton: function (enable) {
                    if (enable) {
                        $('.deletePage').removeAttr('disabled');
                    } else {
                        $('.deletePage').attr('disabled', 'disabled');
                    }
                },
                deleteActiveTemplate: function () {
                    this.idSelectedNode = null;
                    this.disableDeleteButton();
                    tree.removeClassActiveNode();
                }
            });
            var contentPartTemplatesTypeView = new ContentPartTemplatesTypeView();
        }
    }


});
