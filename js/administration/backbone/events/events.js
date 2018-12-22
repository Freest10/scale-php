define(['jquery'], function($) {

    return {

        init: function() {

            var EventModel = Backbone.Model.extend({
                defaults: {
                    id: "",
                    date: "",
                    text:""
                }
            });

            var EventsCollection = Backbone.Collection.extend({
                model: EventModel,
                urlRoot: '/admin/logged/api/events'
            });

            var EventsModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/events'
            });

            var listTemplate = "<a href='<%= href %>'><%= text %></a><div class='date_event'>(<%= date %>)</div>";

            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.wrapListBlock=$("#wrap_tab_block");
                    this.ordersCollection = new EventsCollection();
                    this.messagesCollection = new EventsCollection();
                    this.usersCollection = new EventsCollection();
                    this.render();
                },
                plunkCollectionModel: function(collection, type){
                    if(collection.models.length > 0){
                        this.createEventBlock(collection, type);
                    }
                },
                createEventBlock: function(collection, type){
                    this.eventsBlockView = new EventBlockView({
                        collection: collection,
                        type: type
                    });
                    this.wrapListBlock.append(this.eventsBlockView.render().el);
                },
                render: function () {
                    this.getEventsReq();
                    return this;
                },
                getEventsReq: function(){
                    var self = this;
                    this.collectionReq = new EventsModel();

                    var optionsSync = ({
                        error:function(){ console.log("getAddressesReq");
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            self.ordersCollection.set(data.orders);
                            self.messagesCollection.set(data.messages);
                            self.usersCollection.set(data.users);
                            self.plunkCollectionModel(self.ordersCollection, "orders");
                            self.plunkCollectionModel(self.messagesCollection, "messages");
                            self.plunkCollectionModel(self.usersCollection, "users");
                            self.emptyDataBlock();
                        }
                    });
                    generalSettings.sync('read',this.collectionReq, optionsSync);
                },
                emptyDataBlock: function(){
                    if(this.ordersCollection.length == 0 && this.messagesCollection.length == 0 && this.usersCollection.length == 0){
                        this.showEmptyBlock();
                    }
                },
                showEmptyBlock: function(){
                    this.wrapListBlock.html("<div class='empty_events_block'>"+getTranslate("frontend.events.not_new_events")+"</div>");
                }
            });

            var directoryView = new DirectoryView();



            var EventBlockView = Backbone.View.extend({
                tagName: "div",
                className: "events_block",
                render: function () {
                    this.setH3OnType();
                    this.$el.append("<ul class='wrapListEvents'></ul>");
                    this.wrapList = this.$el.find(".wrapListEvents");
                    this.renderList();
                    return this;
                },
                setH3OnType: function(){
                    var h3="";
                    switch (this.options.type) {

                        case "orders":
                            h3 = getTranslate("frontend.events.new_orders");
                            break;
                        case "messages":
                            h3 = getTranslate("frontend.events.new_messages");
                            break;
                        case "users":
                            h3 = getTranslate("frontend.events.new_users");
                            break;
                    }
                    this.$el.append("<h3><span class='length_events'>"+this.collection.length+"</span>"+h3+"</h3>");
                },
                renderList: function(){
                    _.each(this.collection.models, function (item) {
                        this.createList(item);
                    }, this);
                },
                createList: function(item){
                    this.eventListkView = new EventListkView({
                        model: item,
                        type: this.options.type
                    });
                    this.wrapList.append(this.eventListkView.render().el);
                }
            });

            var EventListkView = Backbone.View.extend({
                tagName: "li",
                className: "event_list",
                template: _.template(listTemplate),
                render: function () {
                    var jsonModel = this.model.toJSON();
                    switch (this.options.type) {
                        case "orders":
                            jsonModel["text"] = getTranslate("frontend.events.order_num");
                            jsonModel["text"] += jsonModel["id"];
                            jsonModel["href"] = "#!/emarket/order/";
                            break;
                        case "messages":
                            jsonModel["href"] = "#!/webforms/messages/message/";
                            break;
                        case "users":
                            jsonModel["href"] = "#!/users/user/";
                            break;
                    }
                    jsonModel["href"] += jsonModel["id"];
                    this.$el.html(this.template(jsonModel));
                    return this;
                }
            });
        }


    }
});
