define(['jquery', 'jquery-ui', 'tinymce', 'tinymce_paste', 'tinymce_table', 'tinymce_code', 'tinymce_link', 'tinymce_image', 'tinymce_imagetools', 'tinymce_ru_lang', 'jstree'], function ($) {

    return {
        init: function (id, urlModel, isGetTemplateList, notEditH1, notSendFieldEmptyValues) {
            //urlModel - url по которому получаем данные для страницы
            //isGetTemplateList (boolean) - получать или нет возможные типы данных для страницы (по-умолчанию - не получаем)
            var StructureModel = Backbone.Model.extend({
                urlRoot: urlModel,
                defaults: {
                    url: id
                }
            });

            var AllPagesModelReq = Backbone.Model.extend({
                urlRoot: urlModel
            });

            var ReferenceModelReq = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/reference_datas',
                defaults: {
                    url: ''
                }
            });

            if (notEditH1) templateH1 = '<div class="directory_name_block"><h1><%= name %></select></h1></div><div class="clear"></div>';

            var PagesModelReq = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/page'
            });

            var TemplateLisModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/templates_type_list'
            });

            var StructureCollection = Backbone.Collection.extend({
                model: StructureModel
            });

            var collectionToSavePage = Backbone.Collection.extend({
                model: StructureModel
            });

            var Field = Backbone.Model.extend({
                defaults: {
                    id: "",
                    hint: "",
                    name: "",
                    necessarily: "",
                    parentId: "",
                    typeId: ""
                }
            });

            var FieldsOfGroup = Backbone.Collection.extend({
                model: Field
            });

            var ReferenceModel = Backbone.Model.extend({
                defaults: {
                    id: "",
                    name: "",
                    items: []
                }
            });

            var ReferenceCollection = Backbone.Collection.extend({
                model: ReferenceModel
            });


            var PageLinkModel = Backbone.Model.extend({
                defaults: {
                    id: "",
                    name: ""
                }
            });

            var PageLinkModelCollection = Backbone.Collection.extend({
                model: ReferenceModel
            });

            var SostavModel = Backbone.Model.extend({
                defaults: {
                    id: "",
                    value: ""
                }
            });

            var SostavModelCollection = Backbone.Collection.extend({
                model: SostavModel
            });

            var pages = null;
            var references = new ReferenceCollection();

            var groupPageTemplate = "<div class='head_group_block'><h4><%= name %> [<%= textId %>]</h4></div><ul id='fieldBlockId_<%= id %>' class='fields_of_group_block'></ul>";
            var fieldNamePage = '<div class="field_block_page"><label><div class="name_field_block"><span><%= name %></span><div class="hint_block"><% if (hint != "") { %><div class="hint"><%= hint %></div><% } %></div></div><div class="field_edit_value_part"></div></label></div>';
            var fieldBlockStringPage = '<input type="text" class="value" data-id-field = "<%= id %>" value="<%= value %>" />';
            var fieldBlockPassword = '<input type="password" class="value" data-id-field = "<%= id %>" value="<%= value %>" />';
            var fieldBlockNumberPage = '<input type="number" class="value" data-id-field = "<%= id %>" value="<%= value %>" />';
            var fieldBlockDate = '<input type="date" class="value" data-id-field = "<%= id %>" value="<%= value %>" />';
            var fieldBlockTime = '<input type="time" class="value" data-id-field = "<%= id %>" value="<%= value %>" />';
            var valueSelectTypeTemplate = '<select class="value" data-id-field = "<%= id %>"></select>';
            var multiSelectTypeTemplate = '<select class="value" data-id-field = "<%= id %>" multiple></select>';
            var optionsOfSelect = '<option value = "<%= id %>"  <% if (selected === true) { %> selected <% } %> ><%= name %></option>';
            var fieldBlockCheckBox = '<input type="checkbox" class="value" data-id-field = "<%= id %>" <% if (value == 1) { %> checked <% } %> />';
            var fieldBlockSimpleTextPage = '<textarea class="value simple_text_area" data-id-field = "<%= id %>" ><%= value %></textarea>';
            var fieldBlockHtmlTextPage = '<textarea class="value tinymce" data-id-field = "<%= id %>" ><%= value %></textarea>';
            var defaultOptionsOfSelect = '<option value = ""  <% if (selected === true) { %> selected <% } %> ></option>';
            var fieldBlockImage = '<input class="value" data-id-field = "<%= id %>" value="<%= value %>" disabled /><a data-toggle="modal" type="button" class="btn btn-primary btn_add_file_or_image" href="#modal-form"><i class="fa fa-folder-open-o"></i></a><button class="clear_field clear_file"><i class="fa fa-times"></i></button>';
            var showTreePages = '<div class="block_link_of_pages value" data-id-field = "<%= id %>"></div><a data-toggle="modal" type="button" class="btn btn-primary btn_to_field btn_show_tree_pages" href="#modal-form"><i class="fa fa-external-link"></i></a>';
            var filedOfpageLink = '<div class="link_page_blck" data-id-page="<%= id %>"><span><%= name %></span><button class="clear_field "><i class="fa fa-times delete_page_link"></i></button></div>';
            var fieldSostav = '<div class="block_sostav_values value" data-id-field="<%= id %>"></div><div class="block_add_sostav_value"><select class="ref_to_sostav"></select><input class="sostav_add_value" type="number" /><button class="clear_field"><i class="fa fa-plus add_sostav_value_btn"></i></button></div>';
            var fieldSostavValueList = '<div class="block_sostav_value_list" data-id-ref-data="<%= id %>" data-ref-data-value="<%= value %>"><span class="sostav_name_list"><%= name %></span><span class="sostav_value_list"><%= value %></span><button class="clear_field "><i class="fa fa-times delete_sostav_list"></i></button></div>';
            var stringView = '<div class="simple_string" ><%= value %></div><div class="clear"></div>';

            var FieldView = Backbone.View.extend({
                tagName: "li",
                className: "field",
                templateFieldNamePage: _.template(fieldNamePage),
                fieldBlockStringPage: _.template(fieldBlockStringPage),
                fieldBlockNumberPage: _.template(fieldBlockNumberPage),
                fieldBlockSimpleTextPage: _.template(fieldBlockSimpleTextPage),
                fieldBlockHtmlTextPage: _.template(fieldBlockHtmlTextPage),
                fieldBlockDate: _.template(fieldBlockDate),
                fieldBlockTime: _.template(fieldBlockTime),
                valueSelectTypeTemplate: _.template(valueSelectTypeTemplate),
                selectOptionTemplate: _.template(optionsOfSelect),
                defaultOptionsOfSelect: _.template(defaultOptionsOfSelect),
                checkBoxField: _.template(fieldBlockCheckBox),
                multiSelectTypeTemplate: _.template(multiSelectTypeTemplate),
                fieldBlockImage: _.template(fieldBlockImage),
                showTreePagesTemplt: _.template(showTreePages),
                filedOfpageLinkTemplate: _.template(filedOfpageLink),
                fieldSostavTemplate: _.template(fieldSostav),
                fieldSostavValueListTemplate: _.template(fieldSostavValueList),
                stringViewTemplate: _.template(stringView),
                fieldBlockPasswordTemplate: _.template(fieldBlockPassword),
                initialize: function () {
                    var self = this;
                    this.renderFieldBlock();
                },
                renderFieldBlock: function () {
                    this.$el.html(this.templateFieldNamePage(this.model.toJSON()));
                    //селект для типов данных
                    if (this.model.toJSON().id == -1) {
                        this.renderSelectTypeBlock();
                    } else {
                        this.renderValueBlock(this.model.toJSON().typeId);
                    }
                },
                renderSelectTypeBlock: function () {
                    this.$el.find('.field_edit_value_part').html(this.valueSelectTypeTemplate(this.model.toJSON()));
                    this.renderOptionsTemplateTypes();
                },
                renderOptionsTemplateTypes: function () {
                    if (directoryView.collection.attributes.templates != null) {
                        var self = this;
                        directoryView.collection.attributes.templates.forEach(function (template_type) {
                            //делаем активный опцион  <% if (selected == true) { %> selected="selected" <% } %>
                            (self.model.toJSON().value == template_type.id) ? template_type.selected = true : template_type.selected = false;
                            self.$el.find('select').append(self.selectOptionTemplate(template_type));
                        });
                    }
                },
                events: {
                    'click .btn_add_file_or_image': 'showFileManager',
                    'click .clear_file': 'clearFile',
                    'click .btn_show_tree_pages': 'showTreePages',
                    'click .delete_page_link': 'deletePageLink',
                    'click .add_sostav_value_btn': 'addSostavValue',
                    'click .delete_sostav_list': 'deleteSostavValue'
                },
                deleteSostavValue: function (e) {
                    var blockSostavList = $(e.target).closest('button').parent('.block_sostav_value_list');
                    var idDataRef = blockSostavList.attr('data-id-ref-data');
                    this.sostav.remove(this.sostav.get(idDataRef));
                    this.$el.find('.block_sostav_values').find(blockSostavList).remove();
                },
                addSostavValue: function () {
                    var objectToAdd = {};
                    objectToAdd.id = this.$el.find('.ref_to_sostav').val();
                    objectToAdd.name = this.$el.find('.ref_to_sostav option[value=' + objectToAdd.id + ']').text();
                    objectToAdd.value = this.$el.find('.sostav_add_value').val();
                    if (parseInt(objectToAdd.value) && objectToAdd.id !== '') {
                        this.addSostavOption(objectToAdd);
                    }
                },
                addSostavOption: function (objectToAdd) {
                    this.sostav.push(objectToAdd);
                    this.generateSostavValueLists();
                },
                generateSostavValueLists: function () {
                    this.$el.find('.block_sostav_values').empty();
                    var self = this;
                    this.sostav.models.forEach(function (model) {
                        self.$el.find('.block_sostav_values').append(self.fieldSostavValueListTemplate(model.attributes));
                    });
                },
                deletePageLink: function (e) {
                    if (!$(e.target).hasClass('delete_page_link')) return false;
                    var blockPageLink = $(e.target).closest('button').parent('.link_page_blck');
                    var idPagLink = blockPageLink.attr('data-id-page');
                    this.pageLinks.remove(this.pageLinks.get(idPagLink));
                    this.$el.find('.block_link_of_pages').find(blockPageLink).remove();
                },
                showTreePages: function () {
                    this.getPages()
                },
                getPages: function () {
                    if (pages != null) {
                        this.generateTreePages();
                    } else {
                        this.getReqPages();
                    }
                },
                generateFieldLinksOfPage: function () {
                    var self = this;
                    self.$el.find('.block_link_of_pages').empty();
                    self.pageLinks.models.forEach(function (pageLink) {
                        var objLinkPageToPush = {};
                        objLinkPageToPush.id = pageLink.id;
                        objLinkPageToPush.name = pageLink.attributes.name;
                        self.$el.find('.block_link_of_pages').append(self.filedOfpageLinkTemplate(objLinkPageToPush));
                    });
                },
                generateTreePages: function () {
                    var idModalForm = this.$el.find('.btn_show_tree_pages').attr('href');
                    var self = this;
                    $(idModalForm).find('.modal-body').html('<h3>' + getTranslate("frontend.share.select_page") + '</h3><div class="modal_tree" id="tree_pages_form"></div>');
                    $('#tree_pages_form').jstree({'core': {'data': pages}}).on('changed.jstree', function (e, data) {
                        if (data != null) {
                            if (data.node != null) {
                                var pageLinkObjct = {};
                                pageLinkObjct.id = data.node.a_attr.href;
                                pageLinkObjct.name = data.node.original.text;
                                self.pageLinks.push(pageLinkObjct);
                                self.generateFieldLinksOfPage();
                            }
                        }
                    })
                },
                getReqPages: function () {
                    var self = this;
                    this.modelPagesReq = new AllPagesModelReq();
                    this.modelPagesReq.set('subDomain', getCookie('subDomain'));
                    var optionsSync = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            pages = data;
                            self.generateTreePages();
                        }
                    });
                    generalSettings.sync('read', this.modelPagesReq, optionsSync);
                },
                showFileManager: function (e) {
                    var idModalForm = $(e.target).closest('a').attr('href');
                    $(idModalForm).find('.modal-body').html('<iframe style="width:898px;height:740px;border:none;"  src="/admin/logged/filemanger/"></iframe>');
                    window.fileInput = this.$el.find('.value');
                },
                clearFile: function () {
                    this.$el.find('.value').val('');
                },
                renderValueBlock: function (typeId) {

                    if (typeId == 1) {
                        this.$el.find('.field_edit_value_part').html(this.fieldBlockStringPage(this.model.toJSON()));
                        //Кнопка флажок
                    } else if (typeId == 2 || typeId == 11 || typeId == 13) {
                        this.$el.find('.field_edit_value_part').html(this.fieldBlockNumberPage(this.model.toJSON()));
                    } else if (typeId == 6) {
                        this.$el.find('.field_edit_value_part').html(this.fieldBlockSimpleTextPage(this.model.toJSON()));
                    } else if (typeId == 7) {
                        var elementHtml = this.$el.find('.field_edit_value_part').html(this.fieldBlockHtmlTextPage(this.model.toJSON()));
                        var self = this;
                    } else if (typeId == 3) {
                        this.$el.find('.field_edit_value_part').html(this.checkBoxField(this.model.toJSON()));
                        //выпадающий список
                    } else if (typeId == 4) {
                        this.$el.find('.field_edit_value_part').html(this.valueSelectTypeTemplate(this.model.toJSON()));
                        if (parseInt(this.model.toJSON().referenceId)) this.doOptionsForReference();
                    } else if (typeId == 5) {
                        this.$el.find('.field_edit_value_part').html(this.multiSelectTypeTemplate(this.model.toJSON()));
                        if (parseInt(this.model.toJSON().referenceId)) this.doOptionsForReference();
                    } else if (typeId == 9) {
                        this.$el.find('.field_edit_value_part').html(this.fieldBlockDate(this.model.toJSON()));
                    } else if (typeId == 10) {
                        this.$el.find('.field_edit_value_part').html(this.fieldBlockTime(this.model.toJSON()));
                    } else if (typeId == 8) {
                        this.$el.find('.field_edit_value_part').html(this.fieldBlockImage(this.model.toJSON()));
                    } else if (typeId == 14) {
                        this.pageLinks = new PageLinkModelCollection;
                        this.$el.find('.field_edit_value_part').html(this.showTreePagesTemplt(this.model.toJSON()));
                        this.createPagelinksBlocks();
                    } else if (typeId == 12) {
                        //составное
                        this.$el.find('.field_edit_value_part').html(this.fieldSostavTemplate(this.model.toJSON()));
                        if (parseInt(this.model.toJSON().referenceId)) {
                            this.doOptionsForReferenceSostav();
                            this.sostav = new SostavModelCollection();
                            this.creatSostavLines();
                        }
                        //обычная текстовая строка
                    } else if (typeId == -1) {
                        this.$el.find('.name_field_block').addClass('simpleTextNameField')
                        this.$el.find('.field_edit_value_part').addClass('simpleTextField').html(this.stringViewTemplate(this.model.toJSON()));
                    } else if (typeId == 15) {
                        this.$el.find('.field_edit_value_part').html(this.fieldBlockPasswordTemplate(this.model.toJSON()));
                    }
                },
                creatSostavLines: function () {
                    var self = this;
                    if (this.model.toJSON().value) {
                        this.model.toJSON().value.forEach(function (sostavList) {
                            self.addSostavOption(sostavList);
                        })
                    }
                },
                createPagelinksBlocks: function () {
                    this.pageLinks.set(this.model.toJSON().value);
                    this.generateFieldLinksOfPage('sostav');
                },
                doOptionsForReferenceSostav: function () {
                    if (!references.get(this.model.toJSON().referenceId)) {
                        this.getRefernceElements();
                    } else {
                        this.generateOptionsSelect();
                    }
                },
                doOptionsForReference: function () {
                    if (!references.get(this.model.toJSON().referenceId)) {
                        this.getRefernceElements();
                    } else {
                        this.generateOptionsSelect();
                    }
                },
                getRefernceElements: function (whatGenerate) {
                    var self = this;
                    this.collectionReq = new ReferenceModelReq();
                    this.collectionReq.defaults.url = this.model.toJSON().referenceId;
                    this.collectionReq.attributes.url = this.model.toJSON().referenceId;
                    var optionsSync = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            data.id = self.model.toJSON().referenceId;
                            references.push(data);
                            if (!whatGenerate) self.generateOptionsSelect();
                        }
                    });
                    generalSettings.sync('read', this.collectionReq, optionsSync);
                },
                generateOptionsSelect: function () {
                    var items = references.get(this.model.toJSON().referenceId).attributes.items;
                    if (this.model.toJSON().typeId == 4 || this.model.toJSON().typeId == 12) {
                        this.selectGenerateOptions(items);
                    } else if (this.model.toJSON().typeId == 5) {
                        this.selectGenerateMultiOptions(items);
                    }
                },
                selectGenerateMultiOptions: function (items) {
                    console.log(items, "selectGenerateMultiOptions");
                    var self = this;
                    var defItem = {};
                    (self.model.toJSON().value == null || self.model.toJSON().value == "") ? defItem.selected = true : defItem.selected = false;
                    self.$el.find('select').append(self.defaultOptionsOfSelect(defItem));
                    items.forEach(function (item) {
                        if (self.model.toJSON().value) {
                            (self.model.toJSON().value.indexOf(item.id) > -1) ? item.selected = true : item.selected = false;
                        } else {
                            item.selected = false;
                        }
                        self.$el.find('select').append(self.selectOptionTemplate(item));
                    });
                },
                selectGenerateOptions: function (items) {
                    var self = this;
                    var defItem = {};
                    (self.model.toJSON().value == null || self.model.toJSON().value == "") ? defItem.selected = true : defItem.selected = false;
                    self.$el.find('select').append(self.defaultOptionsOfSelect(defItem));
                    items.forEach(function (item) {
                        (self.model.toJSON().value == item.id) ? item.selected = true : item.selected = false;
                        self.$el.find('select').append(self.selectOptionTemplate(item));
                    });
                }
            });


            var GroupView = Backbone.View.extend({
                tagName: "div",
                className: "group",
                template: _.template(groupPageTemplate),
                render: function () {
                    this.$el.html(this.template(this.model));
                    if (directoryView.collection.attributes.fields != null) {
                        this.doCollectionFelds();
                    }
                    return this;
                },
                renderFieldsForGroup: function (field) {
                    this.fieldView = new FieldView({
                        model: field
                    });
                    this.$el.find(".fields_of_group_block").append(this.fieldView.render().el);
                },
                doCollectionFelds: function () {
                    var collection = [];
                    var self = this;
                    directoryView.collection.attributes.fields.forEach(function (field) {
                        if (field.parentId == self.model.id) {
                            collection.push(field);
                        }
                    })
                    if (collection.length > 0) {
                        this.collection = new FieldsOfGroup(collection);
                        this.plunkCollectionModel();
                    }
                },
                plunkCollectionModel: function () {
                    _.each(this.collection.models, function (field) {
                        this.renderFieldsForGroup(field);
                    }, this);
                }
            });

            var td = '<td><div><%= value %><div></td>';
            var TrView = Backbone.View.extend({
                tagName: "tr",
                templateTd: _.template(td),
                render: function () {
                    var self = this;
                    if (this.options.renderBy != null) {
                        for (var rend in this.options.renderBy) {
                            this.renderTd(self.model[rend]);
                        }
                    }
                    return this;
                },
                renderTd: function (value) {
                    var data = {};
                    data.value = value;
                    this.$el.append(this.templateTd(data));
                }
            });

            var table = '<div id="tableBlock" class="height-30vh"><h3><%= title %></h3><table id="tableStructData"><thead></thead><tbody></tbody></table><% if (total != null) { %><div class="itogoBlock"><span class="itogo_title">' + getTranslate("frontend.share.total_price") + ': </span> <b><%= total %></b></div><% } %></div>';

            var DirectoryView = Backbone.View.extend({
                el: $(this.wrapBlockId || '#content-block'),
                templateDirectoryName: _.template(templateH1),
                templateEditH1: _.template(templateEditH1),
                tableTemplate: _.template(table),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    this.getDirectoryData();
                    this.renderFooterBlock();
                },
                getDirectoryData: function () {
                    if (isGetTemplateList) {
                        this.getTemplatesList();
                    } else {
                        this.getPageData();
                    }
                },
                getPageData: function (templates) {
                    var self = this;
                    this.collection = new StructureModel();
                    var optionsSync = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            self.collection.set(data);
                            self.renderH1();
                            if (templates) self.collection.attributes.templates = templates;
                            self.render();
                            if (data.table) self.renderTable();
                        }
                    });
                    generalSettings.sync('read', this.collection, optionsSync);
                },
                renderTable: function () {
                    this.$el.find('#tableBlock').remove();
                    this.$el.find('.group_and_field').addClass('height-50vh');
                    var tableOptions = {};
                    tableOptions.title = this.collection.attributes.table.tableName;
                    tableOptions.total = this.collection.attributes.table.total;
                    this.$el.append(this.tableTemplate(tableOptions));

                    this.doRendTable();
                },
                doRendTable: function () {
                    _.each(this.collection.attributes.table.columnsName, function (trValue) {
                        this.renderTr(trValue, 'thead');
                    }, this);
                    _.each(this.collection.attributes.table.columnsValue, function (trValue) {
                        this.renderTr(trValue);
                    }, this);
                },
                renderTr: function (trValue, typeTr) {
                    var self = this;
                    var trView = new TrView({
                        model: trValue,
                        renderBy: self.collection.attributes.table.columnsName[0]
                    });
                    (typeTr == 'thead') ? this.$el.find('#tableStructData thead').append(trView.render().el) : this.$el.find('#tableStructData tbody').append(trView.render().el);

                },
                render: function () {
                    this.renderGroups();
                    //после инициализации вьюхи на странице подключаем плагин tinymce
                    _.defer(function (view) {
                        tinymce.init({
                            selector: '.tinymce',
                            relative_urls : true,
                            remove_script_host : true,
                            document_base_url : "/",
                            convert_urls : false,
                            plugins: ["paste", "table", "code", "link", "image", "imagetools"],
                            language: 'ru',
                            valid_elements: "*[*]"
                        });
                    }, this);
                },
                events: {
                    "click button.save_page": "savePage",
                    "click button.cancel_page": "cancelPage",
                    "click button.editH1": "editH1",
                    "click button.save_h1": "saveH1",
                    "click button.cancel_h1": "cancelEditH1"
                },
                cancelPage: function () {
                    location.reload();
                },
                doFieldsValue: function () {
                    var self = this;
                    var fields = {};
                    if (this.collection.attributes.fields != null) {
                        this.collection.attributes.fields.forEach(function (field) {
                            var el = $('[data-id-field=' + field.id + ']');
                            if (el.hasClass('value')) {
                                if (el.attr('type') == "checkbox") {
                                    if (el.prop('checked')) {
                                        field.value = 1;
                                    } else {
                                        field.value = 0;
                                    }
                                    //html текст
                                } else if (field.typeId == 7) {
                                    field.value = tinyMCE.editors[el.attr('id')].getContent();
                                    //ссылки на дерево
                                } else if (field.typeId == 14) {
                                    var pageLinks = [];
                                    el.find('.link_page_blck').each(function (index, link_page) {
                                        var linkPage = parseInt($(link_page).attr('data-id-page'));
                                        if (linkPage) pageLinks.push(linkPage);
                                    });
                                    field.value = pageLinks;
                                    //составное
                                } else if (field.typeId == 12) {
                                    var sostavValues = [];
                                    el.find('.block_sostav_value_list').each(function (index, sostav_list) {
                                        var sostavValue = {};
                                        sostavValue.id = parseInt($(sostav_list).attr('data-id-ref-data'));
                                        sostavValue.value = parseFloat($(sostav_list).attr('data-ref-data-value'));
                                        if (sostavValue.id && sostavValue.value) sostavValues.push(sostavValue);
                                    });
                                    field.value = sostavValues;
                                } else {
                                    field.value = el.val();
                                }
                            }
                        })
                    }
                },
                editH1: function () {
                    this.$el.find('.editH1').remove();
                    this.$el.off('click', '.editH1');
                    this.$el.find('.directory_name_block').after(this.templateEditH1(this.collection.attributes));
                },
                saveH1: function (e) {
                    this.collection.attributes.name = $(e.target).closest(".editH1BlockForm").find("input").val();
                    this.renderH1();
                },
                cancelEditH1: function () {
                    this.renderH1();
                },
                doSaveFields: function () {
                    var self = this;
                    var modelToSave = {};
                    modelToSave.name = this.collection.attributes.name;
                    modelToSave.fields = this.getOnlyNeccessaryPropsFields(this.collection.attributes.fields);
                    modelToSave.templateId = this.collection.attributes.templateId;
                    this.modelToSavePage = new StructureModel(modelToSave);
                    var optionSavePage = {
                        success: function () {
                            self.getDirectoryData();
                            var message = new Messages();
                            message.renderSaveMessage();
                        }
                    };
                    generalSettings.sync('update', this.modelToSavePage, optionSavePage);
                },
                getOnlyNeccessaryPropsFields: function (fields) {
                    var fieldsWithneccesaryProps = [];
                    fields.forEach(function (field) {
                        var fieldNeccessary = {};
                        fieldNeccessary.id = field.id;
                        fieldNeccessary.typeId = field.typeId;
                        fieldNeccessary.value = field.value;
                        fieldsWithneccesaryProps.push(fieldNeccessary);
                    })
                    return fieldsWithneccesaryProps;
                },
                savePage: function (e) {
                    if (this.validateFieldsValue()) {
                        return false;
                    }
                    ;
                    this.doFieldsValue();
                    this.doSaveFields();
                },
                validateFieldsValue: function () {
                    if ($('.field_edit_value_part .value[data-id-field="-2"]').val() == "") {
                        $('#modal-form .modal-body').html('Псевдостатический адрес должен быть указан');
                        $('#triggerToogleModalForm').click();
                        return true;
                    }
                },
                getTemplatesList: function () {
                    var templateReqCollection = new TemplateLisModel();
                    var self = this;
                    var optionsGetTemplateList = ({
                        success: function (data) {
                            self.getPageData(data);
                        }
                    });
                    generalSettings.sync('read', templateReqCollection, optionsGetTemplateList);
                },
                renderGroups: function () {
                    this.$el.find("div.group_and_field").remove();
                    this.$el.append('<div class="group_and_field"></div>')
                    _.each(this.collection.attributes.groups, function (group) {
                        this.renderGroup(group);
                    }, this);
                },
                renderGroup: function (group) {
                    var groupView = new GroupView({
                        model: group
                    });
                    this.$el.find('.group_and_field').append(groupView.render().el);
                },
                renderH1: function () {
                    this.$el.find('.directory_name_block, .editH1BlockForm').remove();
                    this.$el.prepend(this.templateDirectoryName(this.collection.attributes));
                },
                sync: function (method, model, options) {
                    return Backbone.mySync.call(this, method, model, options);
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel_page">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger save_page">' + getTranslate("frontend.footer.buttons.save") + '</button></div></footer>');
                }
            });

            var directoryView = new DirectoryView();
        },
        setWrapBlockId: function (id) {
            this.wrapBlockId = '#' + id;
        }
    }


});
