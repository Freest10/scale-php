define(['jquery', 'tabs'], function($, tabs) {
	
	var ListModel = Backbone.Model.extend({
				urlRoot: '/admin/logged/api/mail_templates',
			});
			
			
	var ListCollection = Backbone.Collection.extend({
		model: ListModel
	});

	var listViewTemplate = "<a href='#!/webforms/mail_templates/mail_template/<%= id %>' id='<%= id %>' ><%= name %></a>";

    return {
        init: function(tabName, allTabs, page) {
			
			var DirectoryView = Backbone.View.extend({
				el: $("#content-block"),
				initialize: function () {
					shareBackboneFunctions.removeView(this);
					tabs.setTabs(allTabs);
					tabs.setActiveTab(tabName);
					tabs.render();
					this.$el.append("<div id=\"wrap_tab_block\"></div>");
					this.wrapTabBlock = $('#wrap_tab_block');					
					this.collectionReqRef = new ListModel();
					this.getReferences();
				},
				getReferences: function(){
					var self = this;

					var optionsSync = ({
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(data){
						self.collection = new ListModel();
						self.collection.set(data);
						self.render();
					  }
					});
					generalSettings.sync('read',this.collectionReqRef, optionsSync);
				},
				changePage: function(pageNum){
					this.collectionReqRef.attributes.begin = this.getBeginPage(pageNum);
					this.getReferences();
				},
				render: function(){
					this.renderList();
				},
				renderList: function(){
					this.wrapTabBlock.find(".list_references_block").remove();
					this.wrapTabBlock.append('<div class="list_references_block"><ul class="list_references"></ul></div>');
					_.each(this.collection.attributes.items, function (references) {
						this.renderItems(references);
					}, this);
					if(this.collection.attributes.limit < this.collection.attributes.total){
						this.renderPagination();
					}
				},
				renderPagination: function(){
					var paginationView = new PaginationView({
						model: this.collection.attributes
					});
					paginationView.setViewContent(directoryView);
					this.wrapTabBlock.find('.list_references_block').append(paginationView.render().el);
				},
				renderItems: function(references){
					var ListViewInstance = new ListView({
						model: references
					});
					this.wrapTabBlock.find('.list_references').append(ListViewInstance.render().el);
				}
			});
			
			var ListView = Backbone.View.extend({
				tagName: "li",
				className: "list_reference",
				template: _.template(listViewTemplate),
				render: function () {
					this.$el.html(this.template(this.model));
					return this;
				}
			});
			
			var directoryView = new DirectoryView();
		}
	}
});