define(function(listView) {
    return {
        setParams: function(params){
            this.params = params;
        },
        init: function(){
            var self = this;
            window.ModelDataList = Backbone.Model.extend({
                urlRoot: self.params['urlReq']
            });
            window.listViewTemplate = '<div class="list_elem edit_on_hover"><a class="list_elem_link" href="#!'+this.params['urlLink']+'<%= id %>" id="<%= id %>" ><%= name %></a><span class="list_elemnt_buttons_block"></span>';
            if(self.params['deleteButton']) window.listViewTemplate +='<button class="btn btn-danger deleteButton deleteListData"><i class="fa fa-times"></i></button>';
            if(self.params['editButton']) window.listViewTemplate +='<button type="button" class="btn btn-primary editBtn editListData"><i class="fa fa-edit"></i></button>';
            window.listViewTemplate += '<% if (typeof(date) !== "undefined") { %><div><%= date %></div><% } %>';
            window.listViewTemplate += '</span><div class="clear"></div></div><div class="clear"></div>';
            window.editRefDataList = '<div class="editerDataList"><input class="inpChangeNameDataList" type="text" value="<%= name %>" /><button type="button" class="btn btn-w-m btn-default cancel_save_data_list">'+getTranslate("frontend.footer.buttons.cancel")+'</button><button type="button" class="btn btn-w-m btn-danger save_list">'+getTranslate("frontend.footer.buttons.apply")+'</button></div><div class="clear"></div>';

            window.listDataView = Backbone.View.extend({
                tagName: "li",
                className: "list_reference",
                template: _.template(listViewTemplate),
                templateEditDataList: _.template(editRefDataList),
                render: function () {
                    this.$el.html(this.template(this.model));
                    return this;
                },
                events: {
                    "click button.editListData": "editListData",
                    "click button.deleteListData": "deleteListData",
                    "click button.cancel_save_data_list": "cancelListData",
                    "click button.save_list": "saveDataList"
                },
                saveDataList: function(){
                    this.setNameModelCollection();
                    this.render();

                },
                cancelListData: function(){
                    this.render();
                },
                editListData: function(){
                    this.$el.off('click', '.editRefData');
                    this.$el.html(this.templateEditDataList(this.model));
                    this.$el.removeClass('hover_ref_data');
                },
                deleteListData: function(){
                    var idElement = this.$el.find('.list_elem_link').attr('id');
                    directory.deleteData(idElement);
                },
                setNameModelCollection: function(){
                    var self = this;
                    var fields = {};
                    if(directory.collection.attributes.items != null){
                        directory.collection.attributes.items.forEach(function(item){
                            if(self.model.id == item.id){
                                item.name = self.$el.find('.inpChangeNameDataList').val();
                                directory.updateData(item.id, item.name);
                            }
                        })
                    }
                }
            });
            var directory = new this.directoryView();
            directory.page = this.params['page'];
            directory.urlPagination = this.params['urlPagination'];
            directory.init();
        },
        directoryView: Backbone.View.extend({
            el: $("#content-block"),
            init: function(){
                shareBackboneFunctions.removeView(this);
                this.collectionReq = new ModelDataList();
                this.limit = 15;
                this.collectionReq.attributes.begin = this.getBeginPage(this.page);
                this.collectionReq.attributes.limit = this.limit;
                this.getData();
                this.addToInit();
            },
            addToInit: function(){},
            updateData: function(id, name){
                var collectionToUpdate = new ModelDataList();
                collectionToUpdate.attributes.url = id;
                collectionToUpdate.attributes.name = name;
                generalSettings.sync('update',collectionToUpdate);
            },
            putData: function(){
                var collection = new ModelDataList();
                var self = this;
                var optionsSync = ({
                    error:function(){
                        console.log('error');
                    },
                    success: function(data){
                        var message = new Messages();
                        message.renderSaveMessage();
                        $('.list_block').remove();
                        self.getData();
                    }
                });
                generalSettings.sync('create',collection, optionsSync);
            },
            deleteData: function(id){
                var collection = new ModelDataList();
                collection.attributes.url = id;
                var self = this;
                var optionsSync = ({
                    error:function(){
                        console.log('error');
                    },
                    success: function(data){
                        var message = new Messages();
                        message.renderSaveMessage();
                        $('.list_block').remove();
                        self.getData();
                    }
                });
                generalSettings.sync('delete',collection, optionsSync);
            },
            getBeginPage: function(pageNum){
                if(!pageNum) pageNum = 0;
                return (parseInt(pageNum)-1)*this.limit;
            },
            changePage: function(pageNum){
                this.collectionReq.attributes.begin = this.getBeginPage(pageNum);
                this.getData();
            },
            render: function(){
                this.renderLists();
            },
            renderLists: function(){
                this.$el.find(".list_block_wrap").remove();
                this.$el.prepend('<div class="list_block"><ul class="list_users"></ul><div class="clear"></div></div>');
                _.each(this.collection.attributes.items, function (item) {
                    this.renderListData(item);
                }, this);
                if(this.collection.attributes.limit < this.collection.attributes.total){
                    this.renderPagination();
                }
            },
            renderListData: function(item){
                var listData = new listDataView({
                    model: item
                });
                this.$el.find('.list_users').append(listData.render().el);
            },
            renderPagination: function(){
                var paginationView = new PaginationView({
                    model: this.collection.attributes
                });
                paginationView.setUrlButton(this.urlPagination);
                paginationView.setViewContent(this.directoryView);
                if(!this.$el.find('.list_references_block').length){
                    this.$el.append('<div class="list_references_block"></div>');
                }
                this.$el.find('.list_references_block').html(paginationView.render().el);
            },
            getData: function(){
                var self = this;
                var optionsSync = ({
                    error:function(){
                        console.log('error');
                    },
                    success: function(data){
                        self.collection = new ModelDataList();
                        self.collection.set(data);
                        self.render();
                    }
                });
                generalSettings.sync('read',this.collectionReq, optionsSync);
            }
        })
    };
});