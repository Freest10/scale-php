define(['jquery', 'tabs'], function ($, tabs) {
    var fieldTemplate = "<div class='list_field_block'><div class='field_data_block'><span class='fieldName'><%= name %></span></div><a class='btn btn-danger deleteField deleteButton'><i class='fa fa-times'></i></a><button type='button' class='btn btn-primary editField edit'><i class='fa fa-edit'></i></button></div>";
    var editFieldTemplate = '<div class="clear"></div><div class="editFieldBlockForm"><span class="block_edit_fields"><label><div>' + getTranslate("frontend.templates.name") + '</div><input type="text" class="name" value="<%= name %>" /></label><span class="block_edit_fields"><label><div>' + getTranslate("frontend.webforms.addresses.addresses") + '</div><input type="text" class="addresses" value="<%= addresses %>" /></label><button class="save_field btn btn-danger">' + getTranslate("frontend.footer.buttons.save") + '</button><button class="btn btn-secondary cancel_field">' + getTranslate("frontend.footer.buttons.cancel") + '</button></div>';

    var MessageModel = Backbone.Model.extend({
        defaults: {
            id: "",
            template: "",
            date: "",
            ip: "",
            message: ""
        }
    });
    var templateColumnsName = {
        "id": getTranslate("frontend.webforms.messages.id"),
        "address": getTranslate("frontend.webforms.messages.address"),
        "date": getTranslate("frontend.webforms.messages.date"),
        "ip": getTranslate("frontend.webforms.messages.ip"),
        "message": getTranslate("frontend.webforms.messages.message")
    }

    // Return module with methods
    return {

        init: function (tabName, allTabs, id) {
            var MessageReqModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/messages',
                defaults: {
                    url: id
                }
            });

            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    $("#wrap_tab_block").append("<table id='tableMessage' class='simple_table'></table>");
                    this.table = $("#tableMessage");
                    this.model = new MessageModel();
                    this.render();
                },
                events: {
                    "click button.save": "saveData",
                    "click button.add": "addAdress"
                },
                render: function () {
                    this.renderMessage();
                    return this;
                },
                clearMessageTableBlocks: function () {
                    this.wrapListBlock.html("");
                },
                renderMessage: function () {
                    var self = this;
                    this.modelReq = new MessageReqModel();
                    var optionsSync = ({
                        error: function () {
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function (data) {
                            self.model.set(data.items[0]);
                            self.renderTable();
                        }
                    });
                    generalSettings.sync('read', this.modelReq, optionsSync);
                },
                renderTable: function () {
                    for (var attribute in templateColumnsName) {
                        var tableString = "<tr>";
                        tableString += "<td>" + templateColumnsName[attribute] + "</td>";
                        tableString += "<td>" + this.model.attributes[attribute] + "</td>";
                        tableString += "</tr>";
                        this.table.append(tableString);
                    }
                }
            });
            var directoryView = new DirectoryView();
        }
    }
});
