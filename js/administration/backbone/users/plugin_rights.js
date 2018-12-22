define(['jquery', 'tabs'], function ($, tabs) {
    return {
        init: function (id, tabName, allTabs) {

            var mainRightPath = '/plugin_rights';
            var pagePath = '/users/user/plugin_rights';

            var UserPluginRightsModel = Backbone.Model.extend({
                id: null,
                link: null,
                name: null,
                description: null,
                text_id: null,
                accesses: {
                    read_right: 0,
                    create_right: 0,
                    edit_right: 0,
                    delete_right: 0
                }
            });

            var UserPluginRightsModelToSave = Backbone.Model.extend({
                text_id: null,
                accesses: {
                    read_right: 0,
                    create_right: 0,
                    edit_right: 0,
                    delete_right: 0
                }
            });

            var UserPluginRightsCollection = Backbone.Collection.extend({
                urlRoot: apiPath + mainRightPath,
                defaults: {
                    url: id
                },
                model: UserPluginRightsModel
            });

            var ReqUserPluginRights = Backbone.Model.extend({
                urlRoot: apiPath + mainRightPath,
                defaults: {
                    url: id
                },
                params: {
                    begin: 0,
                    limit: 15,
                    total: 0
                }
            });

            var UserPluginRightsCollectionToSave = Backbone.Collection.extend({
                urlRoot: apiPath + mainRightPath,
                defaults: {
                    url: id
                },
                model: UserPluginRightsModelToSave
            });

            var pluginHeaderTemplate = "<div class='plugin-right__header'><h4><%= name %></h4></div>";
            var pluginCheckTemplate = "<span class='plugin-right__checks'><label><%= editName %> <br/> <input class='plugin-right__check-right' type='checkbox' name='<%= rightName %>' <% if (checked === 1) { %> checked <% } %> /></label></span>";

            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.addIdToAllPaths(id);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                    this.initCollections();
                    this.render();
                    this.getUserPluginRights();
                },
                initCollections: function () {
                    this.collectionPluginRights = new UserPluginRightsCollection();
                    this.collectionToSavePluginRights = new UserPluginRightsCollectionToSave();
                    this.reqUserPluginRights = new ReqUserPluginRights();
                    this.collectionToSavePluginRights.bind('change add', function () {
                        $('.save, .cancel').removeAttr('disabled');
                    })
                },
                render: function () {
                    this.wrapTabBlock.append('<ul class="list_plugins"></ul>');
                    this.wrapTabBlock.append('<div class="pagination"></div>');
                    this.$pagination = $('.pagination');
                    this.renderFooterBlock();
                },
                events: {
                    'click .save': 'saveRights',
                    'click .cancel': 'cancelRights'
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel" disabled>' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger save" disabled>' + getTranslate("frontend.footer.buttons.save") + '</button></div></footer>');
                },
                getUserPluginRights: function () {
                    var self = this;
                    var optionsSync = ({
                        success: function (data) {
                            console.log(data, 'data');
                            self.renderH1(data.name);
                            self.collectionPluginRights.set(data.plugin_rights);
                            self.reqUserPluginRights.params.total = data.total;
                            self.reqUserPluginRights.params.begin = data.begin;
                            self.reqUserPluginRights.params.limit = data.limit;
                            self.renderSections();
                            self.renderPagination();
                        }
                    });
                    generalSettings.sync('read', this.reqUserPluginRights, optionsSync);
                },
                renderSections: function () {
                    this.clearRightsSection();
                    _.each(this.collectionPluginRights.models, function (section) {
                        this.renderSection(section);
                    }, this);
                },
                renderSection: function (section) {
                    var pluginRightsViewInstance = new PluginRightsViewInstance({
                        model: section,
                        collectionToSavePluginRights: this.collectionToSavePluginRights
                    });
                    this.wrapTabBlock.find('.list_plugins').append(pluginRightsViewInstance.render().el);
                },
                saveRights: function () {
                    var self = this;
                    var optionsSync = ({
                        success: function (data) {
                            self.resetSaveCollection();
                            self.getUserPluginRights();
                        }
                    });
                    generalSettings.sync('update', this.collectionToSavePluginRights, optionsSync);
                },
                resetSaveCollection: function () {
                    $('.save, .cancel').attr('disabled', true);
                    this.collectionToSavePluginRights.reset();
                    this.collectionPluginRights.reset();
                },
                cancelRights: function () {
                    this.getUserPluginRights();
                    this.resetSaveCollection();
                },
                clearRightsSection: function () {
                    this.wrapTabBlock.find('.list_plugins').empty();
                },
                renderH1: function (h1) {
                    $('.directory_name_block').remove();
                    this.wrapTabBlock.prepend('<div class="directory_name_block"><h1 class="no-float">' + h1 + '</h1></div>');
                },
                renderPagination: function () {
                    var paginationView = new PaginationView({
                        model: this.reqUserPluginRights
                    });
                    console.log(this.$pagination, 'this.$pagination');
                    paginationView.setNoHrefValue(true);
                    paginationView.setUrlButton(pagePath + '/' + id + '/');
                    paginationView.setViewContent(this);
                    console.log(paginationView, 'paginationView');
                    this.$pagination.html(paginationView.render().el);
                },
                getBeginPage: function(pageNum){
                    if(!pageNum) pageNum = 0;
                    return (parseInt(pageNum)-1)*this.reqUserPluginRights.params.limit;
                },
                changePage: function(pageNum){
                    this.reqUserPluginRights.params.begin = this.getBeginPage(pageNum);
                    this.getUserPluginRights();
                    console.log(pageNum, 'pageNum');
                }
            });

            var PluginRightsViewInstance = Backbone.View.extend({
                tagName: "li",
                className: "plugin-right",
                templateHeader: _.template(pluginHeaderTemplate),
                pluginCheckTemplate: _.template(pluginCheckTemplate),
                checkEditNames: {
                    read_right: getTranslate('frontend.main_rights.read'),
                    edit_right: getTranslate('frontend.main_rights.edit'),
                    create_right: getTranslate('frontend.main_rights.create'),
                    delete_right: getTranslate('frontend.main_rights.delete')
                },
                events: {
                    'change .plugin-right__check-right': 'changeRight'
                },
                changeRight: function (event) {
                    var $checkBox = $(event.target);
                    var acccessName = $checkBox.attr('name');
                    var textId = this.model.get('text_id');
                    var acccessValue = $checkBox.prop('checked') ? 1 : 0;
                    var access = this.model.get('accesses');
                    access[acccessName] = acccessValue;
                    this.model.set('accesses', access);
                    var model = this.getModelFromSaveCollection(textId);
                    if (model) {
                        model.set('accesses', access);
                        this.options.collectionToSavePluginRights.trigger('change');
                    } else {
                        var objAccess = {
                            accesses: access,
                            text_id: textId
                        }
                        this.options.collectionToSavePluginRights.push(objAccess);
                    }
                },
                getModelFromSaveCollection: function (sectionTextId) {
                    return this.options.collectionToSavePluginRights.findWhere({'section_text_id': sectionTextId});
                },
                initialize: function () {
                    var self = this;
                    this.render();
                },
                emptyEl: function () {
                    this.$el.empty();
                },
                render: function () {
                    this.$el.html(this.templateHeader(this.model.toJSON()));
                    this.renderSectionCheckers();
                    return this;
                },
                renderSectionCheckers: function () {
                    var modelAccesses = this.model.toJSON().accesses;
                    for (var access in modelAccesses) {
                        this.renderSectionCheck(access, modelAccesses[access]);
                    }
                },
                renderSectionCheck: function (typeAccess, value) {
                    var sectionRight = {
                        rightName: typeAccess,
                        editName: this.checkEditNames[typeAccess],
                        checked: value
                    }
                    this.$el.append(this.pluginCheckTemplate(sectionRight));
                }
            });

            var directoryView = new DirectoryView();
        }
    }
})