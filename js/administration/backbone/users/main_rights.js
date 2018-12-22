define(['jquery', 'tabs'], function ($, tabs) {
    return {
        init: function (id, tabName, allTabs) {

            var mainRightPath = '/main_rights';
            var UserMainRightsModel = Backbone.Model.extend({
                classIco: null,
                id: null,
                link: null,
                name: null,
                section_text_id: null,
                accesses: {
                    read_right: 0,
                    create_right: 0,
                    edit_right: 0,
                    delete_right: 0
                }
            });

            var UserMainRightsCollection = Backbone.Collection.extend({
                urlRoot: apiPath + mainRightPath,
                defaults: {
                    url: id
                },
                model: UserMainRightsModel
            });

            var UserMainRightsModelToSave = Backbone.Model.extend({
                section_text_id: null,
                accesses: {
                    read_right: 0,
                    create_right: 0,
                    edit_right: 0,
                    delete_right: 0
                }
            });

            var UserMainRightsCollectionToSave = Backbone.Collection.extend({
                urlRoot: apiPath + mainRightPath,
                defaults: {
                    url: id
                },
                model: UserMainRightsModelToSave
            });

            var sectionHeaderTemplate = "<div class='section-right__header'><h4><i class='<%= classIco %>'></i> <%= name %></h4></div>";
            var sectionCheckTemplate = "<span class='section-right__checks'><label><%= editName %> <br/> <input class='section-right__check-right' type='checkbox' name='<%= rightName %>' <% if (checked === 1) { %> checked <% } %> /></label></span>";

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
                    this.getUserMainRights();
                },
                initCollections: function () {
                    this.collectionMainRights = new UserMainRightsCollection();
                    this.collectionToSaveMainRights = new UserMainRightsCollectionToSave();
                    this.collectionToSaveMainRights.bind('change add', function () {
                        $('.save, .cancel').removeAttr('disabled');
                    })
                },
                render: function () {
                    this.wrapTabBlock.append('<ul class="list_sections"></ul>');
                    this.renderFooterBlock();
                },
                events: {
                    'click .save': 'saveRights',
                    'click .cancel': 'cancelRights'
                },
                saveRights: function () {
                    var self = this;
                    var optionsSync = ({
                        success: function (data) {
                            location.reload();
                        }
                    });
                    generalSettings.sync('update', this.collectionToSaveMainRights, optionsSync);
                },
                resetSaveCollection: function(){
                    $('.save, .cancel').attr('disabled', true);
                    this.collectionToSaveMainRights.reset();
                    this.collectionMainRights.reset();
                },
                cancelRights: function () {
                    this.getUserMainRights();
                    this.resetSaveCollection();
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel" disabled>' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger save" disabled>' + getTranslate("frontend.footer.buttons.save") + '</button></div></footer>');
                },
                renderSections: function () {
                    this.clearRightsSection();
                    _.each(this.collectionMainRights.models, function (section) {
                        this.renderSection(section);
                    }, this);
                },
                clearRightsSection: function () {
                    this.wrapTabBlock.find('.list_sections').empty();
                },
                renderSection: function (section) {
                    var SectionRightsViewInstance = new SectionRightsView({
                        model: section,
                        collectionToSaveMainRights: this.collectionToSaveMainRights
                    });
                    this.wrapTabBlock.find('.list_sections').append(SectionRightsViewInstance.render().el);
                },
                renderH1: function (h1) {
                    $('.directory_name_block').remove();
                    this.wrapTabBlock.prepend('<div class="directory_name_block"><h1 class="no-float">' + h1 + '</h1></div>');
                },
                getUserMainRights: function () {
                    var self = this;
                    var optionsSync = ({
                        success: function (data) {
                            self.renderH1(data.name);
                            self.collectionMainRights.set(data.main_rights);
                            self.renderSections();
                        }
                    });
                    generalSettings.sync('read', this.collectionMainRights, optionsSync);
                }
            });

            var SectionRightsView = Backbone.View.extend({
                tagName: "li",
                className: "section-right",
                templateHeader: _.template(sectionHeaderTemplate),
                sectionCheckTemplate: _.template(sectionCheckTemplate),
                checkEditNames: {
                    read_right: getTranslate('frontend.main_rights.read'),
                    edit_right: getTranslate('frontend.main_rights.edit'),
                    create_right: getTranslate('frontend.main_rights.create'),
                    delete_right: getTranslate('frontend.main_rights.delete')
                },
                events: {
                    'change .section-right__check-right': 'changeRight'
                },
                changeRight: function (event) {
                    var $checkBox = $(event.target);
                    var accessName = $checkBox.attr('name');
                    var sectionTextId = this.model.get('section_text_id');
                    var accessValue = $checkBox.prop('checked') ? 1 : 0;
                    var access = this.model.get('accesses');
                    access[accessName] = accessValue;
                    this.model.set('accesses', access);
                    var model = this.getModelFromSaveCollection(sectionTextId);
                    if (model) {
                        model.set('accesses', access);
                        this.options.collectionToSaveMainRights.trigger('change');
                    } else {
                        var objAccess = {
                            accesses: access,
                            section_text_id: sectionTextId
                        };
                        this.options.collectionToSaveMainRights.push(objAccess);
                    }
                },
                getModelFromSaveCollection: function (sectionTextId) {
                    return this.options.collectionToSaveMainRights.findWhere({'section_text_id': sectionTextId});
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
                    this.$el.append(this.sectionCheckTemplate(sectionRight));
                }
            });

            var directoryView = new DirectoryView();
        }
    }
})