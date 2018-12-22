define(['jquery', 'tabs'], function($, tabs) {

    return {
        init: function(tabName, allTabs, page) {

            var pluginsPath = apiPath + '/plugins_installed';

            var ReqPluginsModel = Backbone.Model.extend({
                urlRoot: pluginsPath,
                params:{
                    begin: 0,
                    limit: 15,
                    total: 0
                }
            });

            var PluginsModel = Backbone.Model.extend({
                text_id: null,
                name: null,
                description: null
            });

            var PluginToDelete = Backbone.Model.extend({
                urlRoot: pluginsPath
            });

            var PluginsCollection = Backbone.Model.extend({
                model: PluginsModel
            });

            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                    this.collectionReqPlugins = new ReqPluginsModel();
                    this.collectionReqPlugins.params.begin = this.getBeginPage(page);
                    this.getPlugins();
                },
                getPlugins: function(){
                    var self = this;

                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            self.collection = new PluginsCollection();
                            self.collection.set(data);
                            self.collectionReqPlugins.params.total = data.total;
                            self.render();
                        }
                    });
                    generalSettings.sync('read',this.collectionReqPlugins, optionsSync);
                },
                getBeginPage: function(pageNum){
                    if(!pageNum) return 0;
                    return (parseInt(pageNum)-1)*this.collectionReqPlugins.params.limit;
                },
                changePage: function(pageNum){
                    this.collectionReqPlugins.params.begin = this.getBeginPage(pageNum);
                    this.getPlugins();
                },
                render: function(){
                    this.renderList();
                },
                renderList: function(){
                    this.wrapTabBlock.find(".list_references_block").remove();
                    this.wrapTabBlock.append('<div class="list_references_block"><ul class="list_references plugin_list"></ul></div>');
                    _.each(this.collection.attributes.items, function (item) {
                        this.renderItems(item);
                    }, this);
                    if(this.collectionReqPlugins.params.limit < this.collectionReqPlugins.params.total){
                        this.renderPagination();
                    }
                },
                renderPagination: function(){
                    var paginationView = new PaginationView({
                        model: this.collectionReqPlugins
                    });
                    paginationView.setUrlButton("/plugins/installed/");
                    paginationView.setViewContent(directoryView);
                    this.$el.find('.list_references_block').append(paginationView.render().el);
                },
                renderItems: function(items){
                    var listReferenceView = new ListReferenceView({
                        model: items,
                        parent: this
                    });
                    this.$el.find('.list_references').append(listReferenceView.render().el);
                },
                renderH1: function () {
                    this.$el.find('.directory_name_block, .editH1BlockForm').remove();
                    this.$el.prepend(this.templateDirectoryName(this.collection.attributes));
                }
            });

            var listViewTemplate = "<div class='list_field_block noFlex'><div class='field_data_block'><span class='fieldName'><a href='#!/plugins/plugin/<%= text_id %>' ><%= name %></a></span></div><a class='btn btn-danger deleteField deleteButton'><i class='fa fa-times'></i></a><div><%= description %></div></div>";
            var ListReferenceView = Backbone.View.extend({
                tagName: "li",
                className: "list_edit",
                template: _.template(listViewTemplate),
                render: function () {
                    this.$el.html(this.template(this.model));
                    this.init();
                    return this;
                },
                init: function () {
                    var self = this;
                    $(document).delegate( 'button.confirm', "click", function() {
                        var textId= $(this).attr('data-text-id');
                        if(self.model.text_id === textId){
                            self.deleteReq();
                        }
                    });
                },
                events:{
                    "click .deleteField": "deleteConfirm",
                    "click .confirm": "deleteField"
                },
                deleteConfirm: function(){
                    $('#triggerToogleModalForm').click();
                    var messageForm = getTranslate('frontend.plugins.confirm');
                    messageForm += ' - ' + '"'+ this.model.name +'" ?';
                    messageForm += '<div class="modal-footer"><div class="center-block"><button type="button" class="btn btn-w-m btn-danger confirm" data-text-id="'+ this.model.text_id +'">' + getTranslate("frontend.footer.buttons.delete") + '</button><button type="button" class="btn btn-w-m btn-default cancel" onclick=\'$("#triggerToogleModalForm").click();\' data-text-id="'+ this.model.text_id +'">' + getTranslate("frontend.footer.buttons.cancel") + '</button></div></div>';
                    $('#modal-form .modal-body').html(messageForm);
                },
                deleteReq: function(){
                    var self = this;
                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            self.$el.remove();
                            self.options.parent.getPlugins();
                            $("#triggerToogleModalForm").click();
                        }
                    });
                    var deletePluginModel = new PluginToDelete;
                    deletePluginModel.urlRoot += '/'+this.model.text_id;
                    generalSettings.sync('delete',deletePluginModel, optionsSync);
                }
            });

            var directoryView = new DirectoryView();
        }
    }
});
