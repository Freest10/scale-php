define(['jquery', 'tabs'], function($, tabs) {

    return {
        init: function(tabName, allTabs) {

            var AboutProgrammModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/about_program'
            });

            var infoDataList = '<span><%= name %>: </span><span><%= value %></span>';

            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    tabs.setTabs(allTabs);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                    this.collection = new AboutProgrammModel();
                    this.getInfo();
                },
                getInfo: function(){
                    var self = this;
                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            self.renderInfoList(data);
                        }
                    });
                    generalSettings.sync('read',this.collection, optionsSync);
                },
                renderInfoList: function(data){
                    var infoList = this.getInfoListName();
                    for(var key in infoList){
                        var model = {
                            name: infoList[key],
                            value: data[key]
                        };
                        var infoListView = new InfoListView({
                            model: model
                        });
                        this.wrapTabBlock.append(infoListView.render().el);
                    }
                },
                getInfoListName: function(){
                    var infoList = {
                        "program_name":getTranslate("frontend.settings.aboutProgramm.program_name"),
                        "version":getTranslate("frontend.settings.aboutProgramm.version"),
                        "date_create":getTranslate("frontend.settings.aboutProgramm.date_create"),
                        "date_update":getTranslate("frontend.settings.aboutProgramm.date_update")
                    }

                    return infoList;
                }

            });

            var InfoListView = Backbone.View.extend({
                tagName: "div",
                className: "info_data_list",
                template: _.template(infoDataList),
                render: function () {
                    this.renderReferenceData();
                    return this;
                },
                renderReferenceData: function () {
                    this.$el.html(this.template(this.model));
                }
            })

            var directoryView = new DirectoryView();
        }
    }
});