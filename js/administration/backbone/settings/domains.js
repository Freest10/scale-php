define(['jquery', 'tabs'], function ($, tabs) {

    return {
        init: function (tabName, allTabs) {

            var subDomain = "<div class='head_group_block' data-id='<%= id %>'><h4><%= text %> [<%= textId %>]</h4><a class='btn btn-danger deleteButton deleteSubDomain'><i class='fa fa-times'></i></a><button type='button' class='btn btn-primary editSubDomain'><i class='fa fa-edit'></i></button></div>";
            var editSubDomain = '<div class="clear"></div><div class="editGroupBlockForm"><label><div>'+getTranslate("frontend.settings.domains.name")+'</div><input type="text" class="name" name="text" value="<%= text %>" /></label><label><div>'+getTranslate("frontend.settings.domains.prefix")+'</div><input type="text" name="textId" class="textId" value="<%= textId %>" /></label><label><div>'+getTranslate("frontend.settings.domains.default")+'</div><input type="checkbox" name="defaultValue" class="default-domain" <% if (defaultValue) { %> checked <% } %> /></label><button class="save_edited btn btn-danger">'+getTranslate("frontend.footer.buttons.save")+'</button><button class="btn btn-secondary cancel_edited">'+getTranslate("frontend.footer.buttons.cancel")+'</button></div>';

            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.render();
                    this.collectionSubDomains = subDomainCollection;
                    this.initCollectionEvents();
                    this.renderFooterBlock();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                },
                initCollectionEvents: function(){
                    var self = this;
                    this.collectionSubDomains.bind("add", function() {
                        self.renderSubDomains();
                    });
                },
                render: function() {
                    var self = this;
                    this.modelSubDomainsReq = new DomainsModelReq();
                    var optionsSync = ({
                        success: function(data){
                            self.clearWrapper();
                            self.collectionSubDomains.set(data);
                            self.renderSubDomains();
                        }
                    });
                    generalSettings.sync('read',this.modelSubDomainsReq, optionsSync);
                },
                events: {
                    "click button.add": "addSubDomain",
                    "click button.save": "saveSubDomains",
                    "click button.cancel": "cancelSaveSubDomains"
                },
                cancelSaveSubDomains: function(){
                    this.render();
                },
                saveSubDomains: function(){
                    _.each(this.collectionSubDomains.models, function (item) {
                        this.saveSubDomain(item);
                    }, this);
                },
                saveSubDomain: function(item){
                    var self = this;
                    var typeReq = item.id ? "update" : "create";
                    item.setUrlForId(item.id);
                    var optionsSync = ({
                        success: function(data){
                            self.clearWrapper();
                            self.render();
                        }
                    });
                    generalSettings.sync(typeReq, item, optionsSync);
                },
                addSubDomain: function(){
                    this.collectionSubDomains.add(new SubDomainModel());
                },
                clearWrapper: function(){
                    this.wrapTabBlock.empty();
                },
                renderSubDomains: function(){
                    this.clearWrapper();
                    _.each(this.collectionSubDomains.models, function (item) {
                        this.renderSubDomain(item);
                    }, this);
                },
                renderSubDomain: function(item){
                    var subDomainView = new SubDomains({
                        model: item
                    });
                    this.wrapTabBlock.append(subDomainView.render().el);
                },
                renderFooterBlock: function() {
                    this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default add">'+getTranslate("frontend.footer.buttons.add")+'</button><button type="button" class="btn btn-w-m btn-default cancel">' + getTranslate("frontend.footer.buttons.cancel") + '</button><button type="button" class="btn btn-w-m btn-danger save">' + getTranslate("frontend.footer.buttons.save") + '</button></div></footer>');
                }
            });

            var SubDomains = Backbone.View.extend({
                tagName: "div",
                className: "group sub-domain",
                template: _.template(subDomain),
                editTemplate: _.template(editSubDomain),
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));
                    return this;
                },
                events: {
                    "click button.editSubDomain": "editItem",
                    "click .deleteSubDomain": "deleteItem",
                    "click button.save_edited": "saveEdits",
                    "click button.cancel_edited": "cancelEdit",
                    "click .addField": "addField"
                },
                cancelEdit: function(){
                    this.render();
                },
                editItem: function () {
                    this.$el.find('.head_group_block').after(this.editTemplate(this.model.toJSON()));
                    this.$el.find('.head_group_block').find('.btn').remove();
                },
                deleteItem: function(){
                    var self= this;
                    if(!self.model.id){
                        subDomainCollection.remove(self.model.cid);
                        this.remove();
                        return false;
                    }
                    var modelDelReq = new DomainsModelReq();
                    modelDelReq.set("id", this.model.get("id"));
                    var optionsSync = ({
                        error: function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            subDomainCollection.remove(self.model.cid);
                            self.remove();
                        }
                    });
                    generalSettings.sync('delete',modelDelReq, optionsSync);
                },
                saveEdits: function(e){
                    e.preventDefault();
                    var self = this;
                    $(e.target).closest(".editGroupBlockForm").find("input, select").each(function () {
                        var el = $(this);
                        var value = el.val();
                        if(el.attr('type') === "checkbox"){
                            (el.prop('checked')) ? value = 1 : value = 0;
                            if(el.attr("name") === "defaultValue" && value){
                               var defSubDomain = subDomainCollection.findWhere({defaultValue: 1});
                               if(defSubDomain){
                                   defSubDomain.set({defaultValue: 0});
                               }
                            }
                        }
                        self.model.set(el.attr("name"), value);
                    });
                    this.render();
                }
            });
            var directoryView = new DirectoryView();
        }
    }
});