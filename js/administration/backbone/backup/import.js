define(['jquery', 'tabs'], function ($, tabs) {

    var ImportModel = Backbone.Model.extend({
        name: null,
        path: null,
        textId: null,
        defaults: {
            checked: 1,
            showPath: 1
        }
    });

    var ImportCollection = Backbone.Collection.extend({
        model: ImportModel,
        urlRoot: apiPath + '/back_ups',
        params: {
            filePath: null
        }
    });

    var templateOfBackUpInstance = "<h4><label><input class='check_backup' type='checkbox' <% if (checked === 1) { %> checked <% } %> /></i><%= name %></label></h4>" +
        "<% if (showPath == 1) { %><input class='path' value='<%= path %>'><% } %>";

    var fieldBlockImage = '<h4>'+getTranslate('frontend.back_ups.upload_file')+'</h4><input class="value" data-id-field = "download_file" disabled /><a data-toggle="modal" type="button" class="btn btn-primary btn-folder btn_choose_file" href="#modal-form"><i class="fa fa-folder-open-o"></i></a><button class="clear_field clear_file"><i class="fa fa-times"></i></button>';

    return {
        init: function (tabName, allTabs) {
            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.collectionImport = new ImportCollection();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.$el.append("<iframe id=\"download_backup\" style='display: none'></iframe>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                    this.render();
                },
                backUpData: [
                    {
                        name: getTranslate('frontend.back_ups.css'),
                        path: '/',
                        textId: 'css'
                    },
                    {
                        name: getTranslate('frontend.back_ups.js'),
                        path: '/',
                        textId: 'js'
                    },
                    {
                        name: getTranslate('frontend.back_ups.fonts'),
                        path: '/',
                        textId: 'fonts'
                    },
                    {
                        name: getTranslate('frontend.back_ups.images'),
                        path: '/',
                        textId: 'images'
                    },
                    {
                        name: getTranslate('frontend.back_ups.custom_macros'),
                        path: '/',
                        textId: 'custom_macros'
                    },
                    {
                        name: getTranslate('frontend.back_ups.site_templates'),
                        path: '/',
                        textId: 'site_templates'
                    },
                    {
                        name: getTranslate('frontend.back_ups.email_emarket_templates'),
                        path: '/',
                        textId: 'email_emarket_templates'
                    },
                    {
                        name: getTranslate('frontend.back_ups.email_restore_path_templates'),
                        path: '/',
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
                    'click .upload': 'uploadBackUp',
                    'click .cancel': 'cancelBackUp',
                    'click .btn_choose_file': 'chooseFileToDownload',
                    'click .clear_file' : 'clearFile'
                },
                clearFile: function(){
                    this.$el.find('.value').val('');
                },
                cancelBackUp: function () {
                    this.collectionImport.reset();
                    this.collectionImport.add(this.backUpData);
                    this.renderExportsData();
                    this.renderFileUploadBlock();
                },
                render: function () {
                    this.collectionImport.add(this.backUpData);
                    this.renderExportsData();
                    this.renderFileUploadBlock();
                    this.renderFooterBlock();
                },
                uploadBackUp: function () {
                    var self = this;
                    var options = {
                        success: function(){
                            var message = new Messages();
                            message.showMessageByKey('frontend.back_ups.success_restored');
                        }
                    };
                    this.collectionImport.params.filePath = $('[data-id-field = "download_file"]').val();
                    generalSettings.sync('update',this.collectionImport, options);
                },
                renderExportsData: function () {
                    this.wrapTabBlock.empty();
                    _.each(this.collectionImport.models, function (data) {
                        this.renderExportData(data);
                    }, this);
                },
                renderExportData: function (data) {
                    var sectionBackUpView = new SectionBackUpView({
                        model: data
                    });
                    this.wrapTabBlock.append(sectionBackUpView.render().el);
                },
                renderFileUploadBlock: function () {
                    this.wrapTabBlock.append(fieldBlockImage);
                },
                chooseFileToDownload: function (e) {
                    var idModalForm = $(e.target).closest('a').attr('href');
                    $(idModalForm).find('.modal-body').html('<iframe style="width:898px;height:740px;border:none;"  src="/admin/logged/filemanger/"></iframe>');
                    window.fileInput = this.$el.find('.value');
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger upload">' + getTranslate("frontend.footer.buttons.upload") + '</button></div></footer>');
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