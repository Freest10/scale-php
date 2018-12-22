define(['jquery', 'tabs'], function ($, tabs) {

    var ExportModel = Backbone.Model.extend({
        name: null,
        path: null,
        textId: null,
        defaults: {
            checked: 1,
            showPath: 1
        }
    });

    var ExportCollection = Backbone.Collection.extend({
        model: ExportModel
    });

    var templateOfBackUpInstance = "<h4><label><input class='check_backup' type='checkbox' <% if (checked === 1) { %> checked <% } %> /></i><%= name %></label></h4>" +
        "<% if (showPath == 1) { %><input class='path' value='<%= path %>'><% } %>";

    return {
        init: function (tabName, allTabs) {
            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.collectionExport = new ExportCollection();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.$el.append("<iframe id=\"download_backup\" style='display: none'></iframe>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                    this.render();
                },
                backUpPath: apiPath + '/back_ups',
                backUpData: [
                    {
                        name: getTranslate('frontend.back_ups.css'),
                        path: '/css/client',
                        textId: 'css'
                    },
                    {
                        name: getTranslate('frontend.back_ups.js'),
                        path: '/js/client',
                        textId: 'js'
                    },
                    {
                        name: getTranslate('frontend.back_ups.fonts'),
                        path: '/fonts/client',
                        textId: 'fonts'
                    },
                    {
                        name: getTranslate('frontend.back_ups.images'),
                        path: '/images/client',
                        textId: 'images'
                    },
                    {
                        name: getTranslate('frontend.back_ups.files'),
                        path: '/files/images',
                        textId: 'files'
                    },
                    {
                        name: getTranslate('frontend.back_ups.custom_macros'),
                        path: '/client/custom_macros',
                        textId: 'custom_macros'
                    },
                    {
                        name: getTranslate('frontend.back_ups.site_templates'),
                        path: '/client/templates/pug',
                        textId: 'site_templates'
                    },
                    {
                        name: getTranslate('frontend.back_ups.email_emarket_templates'),
                        path: '/client/email/emarket/pug',
                        textId: 'email_emarket_templates'
                    },
                    {
                        name: getTranslate('frontend.back_ups.email_restore_path_templates'),
                        path: '/client/email/users/restore_path/pug',
                        textId: 'email_restore_path_templates'
                    },
                    {
                        name: getTranslate('frontend.back_ups.data_base'),
                        path: null,
                        showPath: 0,
                        textId: 'data_base'
                    }
                ],
                events: {
                    'click .download': 'downloadBackUp',
                    'click .cancel': 'cancelBackUp'
                },
                cancelBackUp: function () {
                    this.collectionExport.reset();
                    this.collectionExport.add(this.backUpData);
                    this.renderExportsData();
                },
                downloadBackUp: function () {
                    var data = {
                        data: this.collectionExport.toJSON()
                    };
                    var dataParam = $.param(data);
                    var fullPath = this.backUpPath + '?' + dataParam;
                    $('#download_backup').attr('src', fullPath);
                },
                render: function () {
                    this.collectionExport.add(this.backUpData);
                    this.renderExportsData();
                    this.renderFooterBlock();
                },
                renderExportsData: function () {
                    this.wrapTabBlock.empty();
                    _.each(this.collectionExport.models, function (data) {
                        this.renderExportData(data);
                    }, this);
                },
                renderExportData: function (data) {
                    var sectionBackUpView = new SectionBackUpView({
                        model: data
                    });
                    this.wrapTabBlock.append(sectionBackUpView.render().el);
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger download">' + getTranslate("frontend.footer.buttons.download") + '</button></div></footer>');
                }
            });

            var SectionBackUpView = Backbone.View.extend({
                tagName: "div",
                className: "section-backup",
                template: _.template(templateOfBackUpInstance),
                events: {
                    'change .path': 'changePath',
                    'change .check_backup': 'changeCheck'
                },
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));
                    return this;
                },
                changePath: function (event) {
                    var $path = $(event.target);
                    var pathVal = $path.val();
                    this.model.set('path', pathVal);
                },
                changeCheck: function () {
                    var isChecked = $(event.target).prop('checked');
                    this.model.set('checked', isChecked);
                }
            });

            var directoryView = new DirectoryView();
        }
    }
});