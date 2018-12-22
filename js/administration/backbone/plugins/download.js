define(['jquery', 'tabs'], function ($, tabs) {
    return {
        init: function (tabName, allTabs) {

            var pluginsPath = apiPath + '/plugins_installed';
            var remotePluginsPath = apiPath + '/remote_plugins';
            var pagePath = '/plugins/download';

            var ReqPluginsModel = Backbone.Model.extend({
                urlRoot: pluginsPath,
                params:{
                    begin: 0,
                    limit: 15,
                    total: 0
                }
            });

            var PluginReqServer = Backbone.Model.extend({
                urlRoot: remotePluginsPath,
                id: null
            });

            var ReqRemotePluginsModel = Backbone.Model.extend({
                urlRoot: remotePluginsPath,
                params:{
                    begin: 0,
                    limit: 15,
                    total: 0
                }
            });

            var PluginsModel = Backbone.Model.extend({
                text_id: null,
                name: null,
                description: null,
                version: null,
                defaults:{
                    installed: false,
                    update: false,
                    chargeable: false
                }
            });

            var PluginsCollection = Backbone.Collection.extend({
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
                    this.collectionReqRemotePlugins = new ReqRemotePluginsModel();
                    this.getInstalledAndRemotePlugins();
                },
                render: function(){
                    this.renderList();
                },
                renderList: function(){
                    this.wrapTabBlock.find(".list_references_block").remove();
                    this.wrapTabBlock.append('<div class="list_references_block"><ul class="list_references plugin_list"></ul></div>');
                    _.each(this.collectionRemotePlugins.models, function (item) {
                        this.renderItems(item.attributes);
                    }, this);
                    if(this.collectionReqRemotePlugins.params.limit < this.collectionReqRemotePlugins.params.total){
                        this.renderPagination();
                    }
                },
                renderPagination: function(){
                    var paginationView = new PaginationView({
                        model: this.collectionReqRemotePlugins
                    });
                    paginationView.setUrlButton(pagePath);
                    paginationView.setViewContent(directoryView);
                    this.$el.find('.list_references_block').append(paginationView.render().el);
                },
                renderItems: function(items){
                    var listReferenceView = new ListReferenceView({
                        model: items,
                        parent: this,
                        directory: this
                    });
                    this.$el.find('.list_references').append(listReferenceView.render().el);
                },
                renderH1: function () {
                    this.$el.find('.directory_name_block, .editH1BlockForm').remove();
                    this.$el.prepend(this.templateDirectoryName(this.collectionRemotePlugins.attributes));
                },
                getBeginPage: function(pageNum){
                    if(!pageNum) return 0;
                    return (parseInt(pageNum)-1)*this.collectionReqRemotePlugins.params.limit;
                },
                changePage: function(pageNum){
                    this.collectionReqRemotePlugins.params.begin = this.getBeginPage(pageNum);
                    this.getInstalledAndRemotePlugins();
                },
                getInstalledAndRemotePlugins: function(){
                    var self = this;

                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            self.collectionPlugins = new PluginsCollection();
                            self.collectionPlugins.set(data.items);
                            self.getRemotePlugins();
                        }
                    });
                    generalSettings.sync('read',this.collectionReqPlugins, optionsSync);
                },
                getRemotePlugins: function(){
                    var self = this;
                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            self.collectionRemotePlugins = new PluginsCollection();
                            var mergedPlugins = self.getMergedPluginModel(data.plugins);
                            self.collectionRemotePlugins.set(mergedPlugins);
                            self.collectionReqRemotePlugins.params.total = data.total;
                            self.render();
                        }
                    });
                    generalSettings.sync('read',this.collectionReqRemotePlugins, optionsSync);
                },
                getMergedPluginModel: function(plugins){
                    var self = this;
                    return plugins.map(function(plugin){
                        var pluginInst = new PluginsModel();

                        var installedPlugin = self.collectionPlugins.findWhere({text_id: plugin.text_id})
                        if(installedPlugin) {
                            plugin.installed = true;
                            if(versionCompare(plugin.version, installedPlugin.attributes.version) > 0){
                                plugin.update = true;
                            }
                        }

                        pluginInst.set(plugin);
                        return pluginInst;
                    })
                }
            });

            var listViewTemplate = "<div class='list_field_block big-height-list noFlex'><div class='field_data_block'><span class='fieldName'><%= name %></span></div><div><%= description %></div><% if (installed === false) { %><button  type='button' title='"+getTranslate('frontend.footer.buttons.download')+"' class='btn btn-bottom-list installed'><i class='fa fa-download'></i></button><% } %><% if (update === true) { %><button  type='button' class='btn btn-bottom-list update' title='"+getTranslate('frontend.footer.buttons.update')+"'><i class='fa fa-wrench'></i></button><% } %><% if (chargeable === true) { %><button  type='button' class='btn btn-bottom-list pay' title='"+getTranslate('frontend.footer.buttons.buy')+"'><i class='fa fa-dollar-sign'></i></button><% } %><div class='clear'></div></div>";
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
                    "click .installed": "doInstalled",
                    "click .update": "update",
                    "click .pay": "pay"
                },
                pay: function(){
                },
                doInstalled: function(){
                    var self = this;
                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            location.reload();
                        }
                    });
                    var reqPluginModel = new PluginReqServer;
                    reqPluginModel.urlRoot += '/'+this.model.text_id;
                    generalSettings.sync('create',reqPluginModel, optionsSync);
                },
                update: function(){
                    var self = this;
                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            self.options.directory.getInstalledAndRemotePlugins();
                        }
                    });
                    var reqPluginModel = new PluginReqServer;
                    reqPluginModel.urlRoot += '/'+this.model.text_id;
                    generalSettings.sync('update',reqPluginModel, optionsSync);
                }
            });

            var directoryView = new DirectoryView();
        }
    }
});