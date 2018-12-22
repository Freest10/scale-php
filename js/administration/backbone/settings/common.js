define(['jquery', 'tabs'], function ($, tabs) {

    return {
        init: function (tabName, allTabs) {

            var LangsModelReq = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/langs'
            });


            var SearchModelReq = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/search_index'
            });

            var SettingsModelReq = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/settings'
            });

            var UpdateModelReq = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/update'
            });

            var SiteMapModelReq = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/sitemap'
            });

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
                    this.appendCommonSettings();
                    this.appendEmailAddress();
                    this.getSettings();
                    this.appendUpdateSystem();
                    var self = this;
                    $(document).delegate( '#confirm-update', "click", function() {
                        self.doUpdateSystem();
                    });
                },
                appendCommonSettings: function () {
                    this.appendSelectLang();
                    this.appendSearchIndex();
                    this.appendSitemapIndex();
                },
                appendSearchIndex: function () {
                    this.wrapTabBlock.append("<div class='tab_prop_block'><button id=\"index_search\" class='btn btn-primary'>" + getTranslate("frontend.settings.common.indexSearch") + "</button></div>");
                },
                appendSitemapIndex: function () {
                    this.wrapTabBlock.append("<div class='tab_prop_block'><button id=\"sitemap\" class='btn btn-primary'>" + getTranslate("frontend.settings.common.sitemap") + "</button></div>");
                },
                appendEmailAddress: function () {
                    this.wrapTabBlock.append("<div class='setting_prop'><div>" + getTranslate("frontend.settings.common.adminEmail") + "</div><input id='admin_email' class='value' data-field-name='admin_email' type='string'></div>");
                },
                appendUpdateSystem: function () {
                    this.wrapTabBlock.append("<div class='tab_prop_block'><button id='update-system' class='btn btn-primary'>" + getTranslate("frontend.settings.common.check_for_updates") + "</button></div>");
                },
                appendSelectLang: function () {
                    this.getLangs();
                },
                getLangs: function () {
                    var self = this;
                    this.collectionLangsReq = new LangsModelReq();
                    var optionsSync = ({
                        error: function () {
                        },
                        success: function (data) {
                            self.generateSelectLangs(data);
                        }
                    });
                    generalSettings.sync('read', this.collectionLangsReq, optionsSync);
                },
                getSettings: function () {
                    var self = this;
                    this.collectionCommonSettingsReq = new SettingsModelReq();
                    var optionsSync = ({
                        error: function () {
                        },
                        success: function (data) {
                            self.generateCommonSettings(data);
                        }
                    });
                    generalSettings.sync('read', this.collectionCommonSettingsReq, optionsSync);
                },
                generateCommonSettings: function (data) {
                    if (!data) return false;
                    data.forEach(function (setting) {
                        $("[data-field-name=" + setting.name + "]").val(setting.value);
                    });
                },
                generateSelectLangs: function (langs) {
                    var selectId = "change_lang";
                    var selectLang = "<div class='tab_prop_block'>";
                    var htmlLabel = this.getHtmlLabelOfSetting(getTranslate("frontend.settings.common.language"), selectId);
                    selectLang += htmlLabel;
                    selectLang += "<select class='value' data-field-name='lang' id='" + selectId + "'>";
                    langs.forEach(function (lang) {
                        var selectedAttr = "";
                        if (lang.active != null) {
                            if (lang.active == 1) {
                                selectedAttr = "selected";
                            }
                        }
                        selectLang += "<option value='" + lang.id + "' " + selectedAttr + " >" + lang.name + "</option>"
                    });
                    selectLang += "</select>";
                    selectLang += "</div>";
                    this.wrapTabBlock.append(selectLang);
                },
                events: {
                    'click #index_search': 'indexSearch',
                    'click #sitemap': 'indexSitemap',
                    'click #update-system': 'updateSystem',
                    'click #cancel-update': 'closeModal',
                    'click .save': 'save'
                },
                save: function () {
                    this.setSettingsFieldValues();
                    this.doSaveSettings();
                },
                setSettingsFieldValues: function () {
                    this.dataSettings = {};
                    var self = this;
                    this.$el.find('.value').each(function (index, item) {
                        self.dataSettings[$(item).attr('data-field-name')] = $(item).val();
                    });
                },
                closeModal: function () {
                    $('#triggerToogleModalForm').click();
                },
                doSaveSettings: function () {
                    var self = this;
                    this.collectionSettingsUpdateReq = new SettingsModelReq(this.dataSettings);
                    var options = {
                        success: function () {
                            location.reload();
                        }
                    };
                    generalSettings.sync('update', this.collectionSettingsUpdateReq, options);
                },
                updateSystem: function () {
                    var self = this;
                    var updateModelReq = new UpdateModelReq(this.dataSettings);
                    var options = {
                        success: function () {
                            $('#triggerToogleModalForm').click();
                            $('#modal-form .modal-body').html('<div class="center-block"><h4>'+getTranslate("frontend.settings.common.updates_found")+'</h4></div><div class="modal-footer"><div class="center-block"><button type="button" id="cancel-update" onclick=\'$("#triggerToogleModalForm").click();\' class="btn btn-w-m btn-default cancel">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" id="confirm-update" class="btn btn-w-m btn-danger confirm">' + getTranslate("frontend.footer.buttons.update") + '</button></div></div>');
                        },
                        error: function (error) {
                            $('#triggerToogleModalForm').click();
                            var message = error.status === 404 ? getTranslate("frontend.settings.common.updates_not_found") : getTranslate("frontend.errors.data_response");
                            $('#modal-form .modal-body').html('<div class="center-block">'+message+'</div>');
                        }
                    };
                    generalSettings.sync('read', updateModelReq, options);
                },
                doUpdateSystem: function () {
                    $('#triggerToogleModalForm').click();
                    var self = this;
                    var updateModelReq = new UpdateModelReq(this.dataSettings);
                    var options = {
                        success: function () {
                            $('#triggerToogleModalForm').click();
                            $('#modal-form .modal-body').html('<div class="center-block"><h4>'+getTranslate("frontend.settings.common.success_updated")+'</h4></div>');
                            setTimeout(location.reload, 2000);
                        }
                    };
                    generalSettings.sync('update', updateModelReq, options);
                },
                indexSearch: function () {
                    this.collectionSearchIndexReq = new SearchModelReq();
                    generalSettings.sync('read', this.collectionSearchIndexReq);
                },
                indexSitemap: function () {
                    this.collectionSitemapIndexReq = new SiteMapModelReq();
                    generalSettings.sync('update', this.collectionSitemapIndexReq);
                },
                getHtmlLabelOfSetting: function (labelText, id) {
                    var labelfor = "";
                    if (id) {
                        labelfor = "for='" + id + "'";
                    }
                    var label = "<label " + labelfor + ">" + labelText + ":</label><br/>";
                    return label;
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger save">' + getTranslate("frontend.footer.buttons.save") + '</button></div></footer>');
                }
            });

            var directoryView = new DirectoryView();
        }
    }
});	