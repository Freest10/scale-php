define(['jquery'], function($) {

    return {
        init: function(page) {
			var ReferencesModel = Backbone.Model.extend({
				urlRoot: '/admin/logged/api/references',
			});
			var ReferencesCollection = Backbone.Collection.extend({
				model: ReferencesModel
			});
			var listViewTemplate = "<a href='#!/references/reference/<%= id %>' id='<%= id %>' ><%= name %></a>";
			var ListReferenceView = Backbone.View.extend({
				tagName: "li",
				className: "list_reference",
				template: _.template(listViewTemplate),
				render: function () {
					this.$el.html(this.template(this.model));
					return this;
				}
			});

			var DirectoryView = Backbone.View.extend({
				el: $("#content-block"),
				templateDirectoryName: _.template(templateH1),
				initialize: function (){
					shareBackboneFunctions.removeView(this);
					this.collectionReqRef = new ReferencesModel();
					this.limit = 15;
					this.collectionReqRef.attributes.begin = this.getBeginPage(page);
					this.collectionReqRef.attributes.limit = this.limit;
					this.getReferences();
				},
				getReferences: function(){
					var self = this;

					var optionsSync = ({
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(data){
						self.collection = new ReferencesModel();
						self.collection.set(data);
						self.render();
					  }
					});
					generalSettings.sync('read',this.collectionReqRef, optionsSync);
				},
				getBeginPage: function(pageNum){
					if(!pageNum) return 0;
					return (parseInt(pageNum)-1)*this.limit;
				},
				changePage: function(pageNum){
					this.collectionReqRef.attributes.begin = this.getBeginPage(pageNum);
					this.getReferences();
				},
				render: function(){
					this.renderList();
				},
				renderList: function(){
					this.$el.find(".list_references_block").remove();
					this.$el.append('<div class="list_references_block"><ul class="list_references"></ul></div>');
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
                    paginationView.setUrlButton("/references/");
					paginationView.setViewContent(directoryView);
					this.$el.find('.list_references_block').append(paginationView.render().el);
				},
				renderItems: function(references){
					var listReferenceView = new ListReferenceView({
						model: references
					});
					this.$el.find('.list_references').append(listReferenceView.render().el);
				},
				renderFooterBlock: function(){
					this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel_page">'+getTranslate("frontend.footer.buttons.cancel")+'</button><button type="button" class="btn btn-w-m btn-danger save_page">'+getTranslate("frontend.footer.buttons.save")+'</button></div></footer>');
				},
				renderH1: function () {
					this.$el.find('.directory_name_block, .editH1BlockForm').remove();
					this.$el.prepend(this.templateDirectoryName(this.collection.attributes));
				}
			});

			var directoryView = new DirectoryView();

		}
	}

});
