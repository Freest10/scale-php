define(['jquery', 'jquery-ui', 'multi_sortable'], function ($) {


    return {
        init: function (id) {
            var TemplateModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/templates_type'
            });
            var TemplateCollection = Backbone.Collection.extend({
                model: TemplateModel
            });
            var FieldTypesModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/field_types'
            });
            var FieldTypesCollection = Backbone.Collection.extend({
                model: TemplateModel
            });
            var ReferenceModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/references'
            });
            var ReferenceCollection = Backbone.Collection.extend({
                model: ReferenceModel
            });
            var GroupForCollection = Backbone.Model.extend({
                defaults: {
                    name: "",
                    textId: ""
                }
            });
            var GroupColletion = Backbone.Collection.extend({
                model: GroupForCollection
            });

            var groupIdClassNamePrefix = 'groupId_';

            var groupTemplate = "<div class='head_group_block'><h4><%= name %> [<%= textId %>]</h4><a class='btn btn-danger deleteGroup'><i class='fa fa-times'></i></a><button type='button' class='btn btn-primary editGroup'><i class='fa fa-edit'></i></button><button type='button' class='btn btn-outline btn-default addField'>" + getTranslate("frontend.templates.add_field") + "</button></div><ul id='fieldBlockId_<%= id %>' class='fields_of_group_block'></ul>";
            var fieldTemplate = "<div class='field_block'><div class='field_data_block'><span class='fieldName'><%= name %></span><span class='fieldTextId'>[<%= textId %>]</span><span class='fieldType'><%= nameTypeId %></span></div><a class='btn btn-danger deleteField deleteButton'><i class='fa fa-times'></i></a><button type='button' class='btn btn-primary editField edit'><i class='fa fa-edit'></i></button></div>";
            var editGroupTemplate = '<div class="clear"></div><div class="editGroupBlockForm"><label><div>' + getTranslate("frontend.templates.name") + '</div><input type="text" class="name" value="<%= name %>" /></label><label><div>' + getTranslate("frontend.templates.identifier") + '</div><input type="text" class="textId" value="<%= textId %>" /></label><button class="save_group btn btn-danger">' + getTranslate("frontend.footer.buttons.save") + '</button><button class="btn btn-secondary cancel_group">' + getTranslate("frontend.footer.buttons.cancel") + '</button></div>';
            var editFieldTemplate = '<div class="clear"></div><div class="editFieldBlockForm"><span class="block_edit_fields"><label><div>' + getTranslate("frontend.templates.name") + '</div><input type="text" class="name" value="<%= name %>" /></label><label><div>' + getTranslate("frontend.templates.identifier") + '</div><input type="text" class="textId" value="<%= textId %>" /></label><label><div>' + getTranslate("frontend.templates.hint") + '</div><input type="text" class="hint" value="<%= hint %>" /></label><label><div>' + getTranslate("frontend.templates.type") + '</div><select class="typeId" value="<%= typeId %>" ></select></label></span><label><div>' + getTranslate("frontend.templates.necessarily") + '</div><input type="checkbox" class="necessarily" <%= isNesseralyCheck %> /></label><label><div>' + getTranslate("frontend.templates.index") + '</div><input type="checkbox" class="noIndex" <%= isNoIndex %> /></label><label><div>' + getTranslate("frontend.templates.filtered") + '</div><input type="checkbox" class="filtered" <%= isFiltered %> /></label><button class="save_field btn btn-danger">' + getTranslate("frontend.footer.buttons.save") + '</button><button class="btn btn-secondary cancel_field">' + getTranslate("frontend.footer.buttons.cancel") + '</button></div>';
            var templateSpravochnik = '<label class="block_sprav"><div>'+getTranslate('frontend.templates.reference')+'</div><select class="referenceId" ></select></label>';
            var templateH1 = '<div class="directory_name_block"><h1><%= name %></select></h1><button type="button" class="btn btn-primary editH1"><i class="fa fa-edit"></i></button></div><div class="clear"></div>';
            var templateEditH1 = '<div class="clear"></div><div class="editH1BlockForm editBlockForm"><label><div>' + getTranslate("frontend.templates.name") + '</div><input type="text" class="name" value="<%= name %>" /></label><button class="save_h1 btn btn-danger">' + getTranslate("frontend.footer.buttons.save") + '</button><button class="btn btn-secondary cancel_h1">' + getTranslate("frontend.footer.buttons.cancel") + '</button></div>';

            var GroupModel = Backbone.Model.extend({
                defaults: {
                    id: "",
                    name: "",
                    textId: ""
                }
            });
            var FieldModel = Backbone.Model.extend({
                defaults: {
                    id: "",
                    hint: "",
                    name: "",
                    necessarily: "",
                    noIndex: "",
                    filtered: "",
                    parentId: "",
                    parentTextId: "",
                    textId: "",
                    typeId: ""
                }
            });
            var Directory = Backbone.Collection.extend({
                model: GroupModel
            });
            var Group = Backbone.Collection.extend({
                model: FieldModel
            });

            var templateData;
            var typesFields;
            var references;

            var FieldView = Backbone.View.extend({
                tagName: "li",
                className: "field",
                template: _.template(fieldTemplate),
                editTemplate: _.template(editFieldTemplate),
                templateSpravochnikToAdd: _.template(templateSpravochnik),
                render: function () {
                    this.setIdFieldBlock();
                    this.generateFieldJson();
                    return this;
                },
                events: {
                    "click button.editField": "editField",
                    "click .deleteField": "deleteField",
                    "click button.save_field": "saveEdits",
                    "click button.cancel_field": "cancelEdit",
                    "change select.typeId": "changedSelectTypeId"
                },
                generateFieldJson: function () {
                    var jsonModel = this.model.toJSON();
                    jsonModel['nameTypeId'] = typesFields[jsonModel.typeId];
                    this.$el.html(this.template(jsonModel));
                },
                setIdFieldBlock: function () {
                    var attrIdFieldBlock = 'fieldId_' + this.model.toJSON().id;
                    this.$el.attr('id', attrIdFieldBlock);
                    if (this.model.toJSON().id != null) this.$el.attr('data-field-id', this.model.toJSON().id);
                    this.$el.attr('data-field-text-id', this.model.toJSON().textId);
                },
                deleteField: function () {
                    this.remove();
                    var textIdField = this.$el.attr('data-field-text-id');
                    templateData.fields.forEach(function (field, index) {
                        if (field.textId == textIdField) {
                            templateData.fields.splice(index, 1);
                        }
                    })
                },
                editField: function () {
                    var modelToTemplate = this.model.toJSON();
                    (modelToTemplate.necessarily == 1) ? modelToTemplate.isNesseralyCheck = "checked" : modelToTemplate.isNesseralyCheck = "";
                    (modelToTemplate.noIndex == 1) ? modelToTemplate.isNoIndex = "checked" : modelToTemplate.isNoIndex = "";
                    (modelToTemplate.filtered == 1) ? modelToTemplate.isFiltered = "checked" : modelToTemplate.isFiltered = "";

                    this.$el.find('.field_block').after(this.editTemplate(modelToTemplate));
                    this.appendToSelectTypes();
                    this.addOrRemoveReferenceSelect(this.$el.find('select.typeId').val());
                    this.$el.find('.field_block').find('.btn').remove();
                },
                appendToSelectTypes: function () {
                    var selectBlock = this.$el.find('select.typeId');
                    for (var type in typesFields) {
                        selectBlock.append('<option value="' + type + '">' + typesFields[type] + '</option>');
                    }
                    this.setSelectedType();
                },
                setSelectedType: function () {
                    if (this.model.toJSON().typeId != null) this.$el.find('select.typeId').val(this.model.toJSON().typeId);
                },
                saveEdits: function (e) {
                    e.preventDefault();
                    var formData = {};
                    var modelJsonEditField = this.model.toJSON();

                    $(e.target).closest(".editFieldBlockForm").find("input, select").each(function () {
                        var el = $(this);
                        var value = el.val();
                        if (el.attr('type') == "checkbox") {
                            (el.prop('checked')) ? value = 1 : value = 0;
                        }
                        formData[el.attr("class")] = value;

                    });

                    this.model.set(formData);

                    this.render();

                    //update templateData-groups array
                    _.each(templateData.fields, function (field) {
                        if (modelJsonEditField.textId == field.textId) {
                            $.extend(field, formData);
                        }
                    });
                },
                changedSelectTypeId: function (e) {
                    var selectTypeIdValue = $(e.target).val();
                    this.addOrRemoveReferenceSelect(selectTypeIdValue);

                },
                addOrRemoveReferenceSelect: function (selectTypeIdValue) {
                    if (selectTypeIdValue == 4 || selectTypeIdValue == 5 || selectTypeIdValue == 12) {
                        if (this.$el.find('select.referenceId').length == 0) {
                            this.appendSelectSprav();
                        }
                    } else {
                        this.$el.find('.block_sprav').remove();
                    }
                },
                appendSelectSprav: function () {

                    this.$el.find('.block_edit_fields').append(this.templateSpravochnikToAdd(this.model.toJSON()));
                    if (this.isHaveReferencesData()) {
                        this.renderOptionsToSpravochnik()
                    } else {
                        this.getReference();
                    }
                },
                isHaveReferencesData: function () {
                    if (references) {
                        return true;
                    } else {
                        return false;
                    }
                },
                renderOptionsToSpravochnik: function () {
                    var selectBlock = this.$el.find('select.referenceId');
                    for (var item in references.items) {
                        selectBlock.append('<option value="' + references.items[item].id + '">' + references.items[item].name + '</option>');
                    }
                    this.setSelectedReference();
                },
                setSelectedReference: function () {
                    if (this.model.toJSON().referenceId != null) this.$el.find('select.referenceId').val(this.model.toJSON().referenceId);
                },
                getReference: function () {
                    var self = this;
                    var optionsReference = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            references = data.changed;
                            self.renderOptionsToSpravochnik();
                        }
                    });

                    new ReferenceModel().fetch(optionsReference);
                },
                cancelEdit: function () {
                    this.render();
                }
            });

            var GroupView = Backbone.View.extend({
                tagName: "div",
                className: "group",
                template: _.template(groupTemplate),
                editTemplate: _.template(editGroupTemplate),
                render: function () {
                    this.setIdGroupBlock();
                    this.$el.html(this.template(this.model.toJSON()));

                    if (templateData.fields != null) {
                        this.doCollectionFelds();
                    }
                    return this;
                },
                events: {
                    "click button.editGroup": "editGroup",
                    "click .deleteGroup": "deleteGroup",
                    "click button.save_group": "saveEdits",
                    "click button.cancel_group": "cancelEdit",
                    "click .addField": "addField"
                },
                addField: function () {
                    var newField = {};
                    var numField = $('.field').length;
                    newField.id = 'fieldId_' + numField;
                    newField.name = getTranslate('frontend.templates.new_field') + numField;
                    newField.textId = 'newId_' + numField;
                    newField.parentTextId = this.$el.attr('data-group-text-id');
                    newField.typeId = 1;
                    if (templateData.fields == null) templateData.fields = [];
                    templateData.fields.push(newField);
                    this.collection = new Group(templateData.fields);
                    this.render();
                },
                saveEdits: function (e) {
                    e.preventDefault();

                    var formData = {},
                        prev = this.model.previousAttributes();

                    var modelJsonEditGroup = this.model.toJSON();

                    $(e.target).closest(".editGroupBlockForm").find("input").each(function () {
                        var el = $(this);
                        formData[el.attr("class")] = el.val();
                    });

                    //обновляем parentTextId для дочерних полей группы
                    this.editParentTextIdInFields(formData.textId, modelJsonEditGroup.textId);

                    this.model.set(formData);

                    this.render();

                    //update templateData-groups array
                    _.each(templateData.groups, function (group) {
                        if (modelJsonEditGroup.textId == group.textId) {
                            $.extend(group, formData);
                        }
                    });
                },
                editParentTextIdInFields: function (groupTextIdNew, groupTextIdPrev) {
                    _.each(templateData.fields, function (field) {
                        if (field.parentTextId == groupTextIdPrev) {
                            field.parentTextId = groupTextIdNew;
                        }
                    });
                },
                cancelEdit: function () {
                    this.render();
                },
                deleteGroup: function (event) {
                    var deleteGroupTextId = $(event.target).closest('.group').attr('data-group-text-id');
                    templateData.groups = templateData.groups.filter(function (group, index) {
                        return group.textId !== deleteGroupTextId;
                    });
                    this.deleteFieldsForGroup(deleteGroupTextId);
                    directoryView.resetCollection();
                    this.remove();

                },
                deleteFieldsForGroup: function (deleteGroupTextId) {
                    var self = this;
                    var templDataFields = templateData.fields;
                    _.each(templDataFields, function (field) {
                        if (field != null) {
                            if (field.parentTextId == deleteGroupTextId) {
                                templDataFields.splice(_.indexOf(templDataFields, field), 1);
                                self.deleteFieldsForGroup(deleteGroupTextId);
                                return false;
                            }
                        }
                    });
                },
                doCollectionFelds: function () {

                    var collection = [];
                    var self = this;
                    templateData.fields.forEach(function (field) {
                        if (field.parentTextId == self.model.toJSON().textId) {
                            collection.push(field);
                        }
                    })

                    if (collection.length > 0) {
                        this.collection = new Group(collection);
                        this.clearFieldBlock();
                        this.plunkCollectionModel();
                    }
                },
                plunkCollectionModel: function () {
                    _.each(this.collection.models, function (item) {
                        this.renderFields(item);
                    }, this);
                    this.doSortable();
                },
                doSortable: function () {
                    var self = this;
                    setTimeout(function () {
                        $('ul.fields_of_group_block').multisortable();
                        $('ul.fields_of_group_block').sortable('option', 'connectWith', 'ul.fields_of_group_block');
                        $('ul.fields_of_group_block').sortable({cancel: ".editFieldBlockForm"});
                        $("ul.fields_of_group_block").on("sortreceive", function (event, ui) {
                            var fieldTextId = $(ui.item).attr('data-field-text-id');
                            var parentTextId = $(ui.item).closest('.group').attr('data-group-text-id');
                            templateData.fields.forEach(function (field) {
                                if (field.textId == fieldTextId) {
                                    field.parentTextId = parentTextId;
                                }
                            })
                        });
                    }, 700)
                },
                clearFieldBlock: function () {
                    this.$el.find(".fields_of_group_block").empty();
                },
                setIdGroupBlock: function () {
                    var attrIdGroupBlock = groupIdClassNamePrefix + this.model.toJSON().id;
                    if (this.model.toJSON().id != null) this.$el.attr('data-group-id', this.model.toJSON().id);
                    this.$el.attr('data-group-text-id', this.model.toJSON().textId);
                    this.$el.attr('id', attrIdGroupBlock);
                },
                renderFields: function (item) {
                    this.fieldView = new FieldView({
                        model: item
                    });
                    this.$el.find(".fields_of_group_block").append(this.fieldView.render().el);
                },
                editGroup: function () {
                    this.$el.find('.head_group_block').after(this.editTemplate(this.model.toJSON()));
                    this.$el.find('.head_group_block').find('.btn').remove();
                }
            });

            var addingNumGroup = 0;
            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                templateDirectoryName: _.template(templateH1),
                templateEditH1: _.template(templateEditH1),
                initialize: function () {

                    shareBackboneFunctions.removeView(this);
                    var self = this;
                    var optionsFieldTypes = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            typesFields = data.changed;
                            self.groupsBlockRender();
                        }
                    });

                    new FieldTypesModel().fetch(optionsFieldTypes);

                },
                groupsBlockRender: function (justUpdateTemplateData) {
                    var self = this;
                    var optionsTemplate = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            templateData = data.changed;
                            if (!justUpdateTemplateData) {
                                self.renderH1();
                                self.renderButtonAddGroup();
                                self.setCollection();
                                self.render();
                                self.renderFooterBlock();
                            } else {
                                self.render();
                                self.updateGroupView(templateData.groups);
                            }
                        }
                    });
                    new TemplateModel({id: id}).fetch(optionsTemplate);
                },
                setCollection: function () {
                    this.collection = new Directory(templateData.groups);
                },
                events: {
                    "click button.addGroupButton": "addGroup",
                    "click button.save_template": "saveTemplate",
                    "click button.cancel_template": "cancelTemplate",
                    "click button.editH1": "editH1",
                    "click button.save_h1": "saveH1",
                    "click button.cancel_h1": "cancelEditH1",
                },
                saveH1: function (e) {
                    templateData.name = $(e.target).closest(".editH1BlockForm").find("input").val();
                    this.renderH1();
                },
                cancelEditH1: function () {
                    this.renderH1();
                },
                cancelTemplate: function () {
                    location.reload();
                },
                saveTemplate: function () {
                    var self = this;
                    var sortedFields = this.getSortedFields(templateData);
                    optionsSetTemplate = ({
                        type: "POST",
                        data: {templateData: sortedFields},
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            var message = new Messages();
                            message.renderSaveMessage();
                            self.groupsBlockRender(true);
                        }
                    });
                    var setTemplate = new TemplateModel({id: id});
                    setTemplate.fetch(optionsSetTemplate);
                },
                updateGroupView: function (groups) {
                    if (groups) {

                        var self = this;
                        groups.forEach(function (group) {
                            var $elem = $('[data-group-text-id=' + group.textId + ']');
                            $elem.attr('data-group-id', group.id);
                            $elem.attr('id', groupIdClassNamePrefix + group.id);
                        });
                    }
                },
                getSortedFields: function (templateData) {
                    if (templateData.fields) {
                        templateData.fields.forEach(function (field) {
                            field["num"] = $('.field').index($(".field[data-field-id=" + field["id"] + "]"));
                        });
                        return templateData;
                    }
                    return templateData;
                },
                addGroup: function () {
                    var newGroup = {};
                    var randomNum = this.getRandomNum(0, 1000);
                    var groupNum = $('.group').length;
                    newGroup.textId = 'newGroupId_' + groupNum;
                    newGroup.name = getTranslate("frontend.templates.new_group") + groupNum;
                    newGroup.id = 'newGroupId_' + groupNum + addingNumGroup + randomNum;
                    templateData.groups.push(newGroup);
                    addingNumGroup++;
                    this.resetCollection();
                    this.render();
                },
                getRandomNum: function (min, max) {
                    return Math.floor(Math.random() * (max - min + 1)) + min;
                },
                resetCollection: function () {
                    this.collection.models = [];
                    this.collection.reset();
                    this.collection.set(templateData.groups);
                },
                render: function () {
                    this.$el.find("div.group_and_field").remove();
                    this.$el.append('<div class="group_and_field template_group_and_field_block"></div>')
                    _.each(this.collection.models, function (item) {
                        this.renderGroups(item);
                    }, this);
                },
                renderGroups: function (item) {
                    var groupView = new GroupView({
                        model: item
                    });
                    this.$el.find('.group_and_field').append(groupView.render().el);
                },
                renderH1: function () {
                    this.$el.find('.directory_name_block, .editH1BlockForm').remove();
                    this.$el.prepend(this.templateDirectoryName(templateData));
                },
                renderButtonAddGroup: function () {
                    this.$el.append('<button type="button" class="btn btn-outline btn-primary addGroupButton">' + getTranslate("frontend.templates.add_group") + '</button>');
                },
                renderFooterBlock: function () {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel_template">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger save_template">' + getTranslate("frontend.footer.buttons.save") + '</button></div></footer>');
                },
                editH1: function () {
                    this.$el.find('.editH1').remove();
                    this.$el.off('click', '.editH1');
                    this.$el.find('.directory_name_block').after(this.templateEditH1(templateData));
                }
            });

            var directoryView = new DirectoryView();
        }
    };
});
