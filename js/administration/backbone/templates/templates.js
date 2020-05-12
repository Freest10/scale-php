
define(['jquery', 'jstree', 'jstree.search'], function($, tree_functions) {
	
	var footerTemplate = '<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default delete-active-tree">'+getTranslate("frontend.footer.buttons.de_select")+'</button><button type="button" class="btn btn-w-m btn-default delete-template" disabled>'+getTranslate("frontend.footer.buttons.delete")+'</button><button type="button" class="btn btn-w-m btn-danger addtemplate">'+getTranslate("frontend.footer.buttons.add_template")+'</button></div></footer>';
	
	var TemplateModel = Backbone.Model.extend({
		urlRoot: '/admin/logged/api/templates_type'
	});
	
	var TemplateCollection = Backbone.Collection.extend({
		model: TemplateModel
	});
	
	var TemplatesModel = Backbone.Model.extend({
		urlRoot: '/admin/logged/api/templates_type'
	});


    // Return module with methods
    return {
		
		
        init: function() {
			
			var ContentPartTemplatesTypeView = Backbone.View.extend({
				el: $("#content-block"),
				templateFooter: _.template(footerTemplate),
				initialize: function () {
					
					shareBackboneFunctions.removeView(this);
					this.treeView = tree.init();
					this.$el.append(tree.getSearchTree());
					this.$el.append(this.treeView.el);
					var treeRequest = tree.requestToData('/templates_type');
					treeRequest.get();
					this.showFooter();
					
					var self = this;
					this.treeView.$el.on("click.jstree", function (e, data) {
						tree.removeClassActiveNode();
						var target = $(e.target);
						if(target.is('li')){
							target.addClass('activeLiTree');
							if($('.activeLiTree').length > 0){
								var idnodeSelected = $(e.target).attr('id');
								self.idSelectedNode = idnodeSelected;
								self.disableDeleteButton(true);
							}
						}else{
							self.deleteActiveTemplate();
							self.disableDeleteButton();
						}
					});
					this.render();
				},
				updateTemplatesTree: function(){
					var self = this;
					var templatesModelOptions = ({
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(data){
						var arrData = [data.changed];
						tree.setNewData(self.treeView.$el.attr('id'), arrData);
						self.idSelectedNode = null;
						self.disableDeleteButton();
					  }
					});
					
					new TemplatesModel().fetch(templatesModelOptions);
				},
				deleteTemplateNode: function(){
					var self = this;
					var dataObject = {};
					if(this.idSelectedNode){
						dataObject.id = this.idSelectedNode;
					}
					var optionsTemplate = ({
					  type: "DELETE",
					  data: dataObject,
					  error:function(data, error){
						if(error.responseJSON.description){
							alert(error.responseJSON.description);
						}else{
							alert(getTranslate("frontend.errors.data_response"));
						}
					  },
					  success: function(data){
						self.updateTemplatesTree();
					  }
					});
					new TemplateModel().fetch(optionsTemplate);
				},
				disableDeleteButton: function(enable){
					if(enable){
						$('.delete-template').removeAttr('disabled');
					}else{
						$('.delete-template').attr('disabled', 'disabled');
					}
				},
				events:{
					"click button.addtemplate": "addTemplate",
					"click button.delete-active-tree": "deleteActiveTemplate",
					"click button.delete-template": "deleteTemplate"
				},
				deleteTemplate: function(){
					this.deleteTemplateNode();
				},
				showFooter: function(){
					this.$el.append(this.templateFooter());
				},
				addTemplate: function(){

					var self = this;
					var dataObject = {};
					if(this.idSelectedNode){
						dataObject.parent_id = this.idSelectedNode;
					}
					var optionsTemplate = ({
					  type: "PUT",
					  data: dataObject,
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(){
						self.updateTemplatesTree();
					  }
					});
					new TemplateModel().fetch(optionsTemplate);
				},
				deleteActiveTemplate: function(){
					this.idSelectedNode = null;
					this.disableDeleteButton();
					tree.removeClassActiveNode();
				}
			});

			var contentPartTemplatesTypeView = new ContentPartTemplatesTypeView();
        }
    }


});
