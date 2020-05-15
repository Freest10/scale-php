define(['jquery'], function($) {
	
    return {

        init: function(id) {
			
			var ReferenceDatasModel = Backbone.Model.extend({
				urlRoot: '/admin/logged/api/reference_datas',
				defaults: {
					url: id
				}
			});
			
			var tableTemlpate = '<div class="referenceTable"><input class="search-reference" placeholder='+getTranslate("frontend.share.search")+'><table id="ref_table_datas"></table></div>';
			var refDataList = '<td class="td_name_ref"><a href="#!/references/reference/'+id+'/element/<%= id %>"><%= name %></a></td><td class="editRefDataList"><button type="button" class="btn btn-primary editBtn editRefData"><i class="fa fa-edit"></i></button><button class="btn btn-danger deleteButton deleteElemReference"><i class="fa fa-times"></i></button></td>';
			var editRefDataList = '<div class="editDataRef"><input class="inpChangeNameDataRef" type="text" value="<%= name %>" /><button type="button" class="btn btn-w-m btn-default cancel_save_reference_data_list">'+getTranslate("frontend.footer.buttons.cancel")+'</button><button type="button" class="btn btn-w-m btn-danger save_reference_list">'+getTranslate("frontend.footer.buttons.apply")+'</button></div>'
			
			var ReferenceDataListView = Backbone.View.extend({
				tagName: "tr",
				className: "reference_data_list",
				template: _.template(refDataList),
				templateEditRefDataList: _.template(editRefDataList),
				render: function () {
					this.renderReferenceData();
					this.$el.attr('data-id',this.model.id);
					return this;
				},
				renderReferenceData: function(){
					this.$el.html(this.template(this.model));
					this.addHoverClassOnTr();
				},
				addHoverClassOnTr: function(){
					this.$el.addClass('hover_ref_data');
				},
				events: {
					"click button.editRefData": "editRefData",
					"click button.cancel_save_reference_data_list": "cancelRefData",
					"click button.save_reference_list": "saveReferenceList",
					"click .deleteElemReference": "deleteElemReference"
				},
				deleteElemReference: function(e){
					if(directoryView.collectionModel.attributes.items != null){
						directoryView.collectionModel.attributes.items.splice(getIndex($(e.target).closest( ".reference_data_list" )), 1);
						directoryView.renderRefDatasList();
					}
				},
				saveReferenceList: function(){
					this.setReferenceNameModelCollection();
					this.renderReferenceData();
				},
				editRefData: function(){
					this.$el.off('click', '.editRefData');
					this.$el.html(this.templateEditRefDataList(this.model));
					this.$el.removeClass('hover_ref_data');
				},
				cancelRefData: function(){
					this.renderReferenceData();
				},
				setReferenceNameModelCollection: function(){
					var self = this;
					var fields = {};
					if(directoryView.collectionModel.attributes.items != null){
						directoryView.collectionModel.attributes.items.forEach(function(item){
							if(self.model.id == item.id){
								item.name = self.$el.find('.inpChangeNameDataRef').val();
							}
						})
					}
				}
			});
			
			var DirectoryView = Backbone.View.extend({
				el: $("#content-block"),
				tableTemplate: _.template(tableTemlpate),
				templateDirectoryName: _.template(templateH1),
				templateEditH1: _.template(templateEditH1),
				query: null,
				initialize: function (){
					shareBackboneFunctions.removeView(this);
					this.getReferenceDatas();
					this.renderFooterBlock();
				},
				events:{
					"click button.save_page": "savePage",
					"click button.editH1": "editH1",
					"click button.save_h1": "saveH1",
					"click button.cancel_h1": "cancelEditH1",
					"click button.cancel_ref": "cancelRef",
					"click button.save_ref": "saveRef",
					"click button.add_ref": "addRef",
				},
				addRef: function(){
					if(this.collectionModel.attributes.items == null) this.collectionModel.attributes.items = [];
					var idElem = 'newId'+$('.reference_data_list').length;
					this.collectionModel.attributes.items.push({name:'Новый элемент', id:idElem})
					this.render();
				},
				cancelRef: function(){
					location.reload();
				},
				saveRef: function(){
					var self = this;
					var optionsSync = ({
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(data){
						var message = new Messages();
						message.renderSaveMessage();
						self.getReferenceDatas();
					  }
					});
					generalSettings.sync('update',this.collectionModel, optionsSync);
					//console.log(this);
				},
				editH1: function(){
					this.$el.find('.editH1').remove();
					this.$el.off('click', '.editH1');
					this.$el.find('.directory_name_block').after(this.templateEditH1(this.collectionModel.attributes));
				},
				editH1: function(){
					this.$el.find('.editH1').remove();
					this.$el.off('click', '.editH1');
					this.$el.find('.directory_name_block').after(this.templateEditH1(this.collectionModel.attributes));
				},
				saveH1: function(e){
					this.collectionModel.attributes.name = $(e.target).closest(".editH1BlockForm").find("input").val();
					this.renderH1();
				},
				cancelEditH1: function(){
					this.renderH1();
				},
				getReferenceDatas: function(){
					var self = this;
					var optionsSync = ({
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(data){
						self.collectionModel = new ReferenceDatasModel();
						self.collectionModel.set(data);
						self.renderH1();
						self.render();
					  }
					});
					this.collectionModelReqRef = new ReferenceDatasModel();
					generalSettings.sync('read',this.collectionModelReqRef, optionsSync);
				},
				initSearchList: function(){
					var to;
					var self = this;
					this.query = null;
					$('.search-reference').keyup(function () {
						if(to) { clearTimeout(to); }
						to = setTimeout(function () {
						  self.query = ($('.search-reference').val() || '').trim().toLowerCase();
						  
						  self.renderRefDatasList();
						}, 250);
					});
				},
				renderRefDatasList: function(){
					this.$el.find("#ref_table_datas").empty();
					const resultItems = (this.collectionModel.attributes.items || []).filter(function(item) {
						return this.query ? (item.name || '').trim().toLowerCase().indexOf(this.query) > -1 : true;
					}.bind(this))
					_.each(resultItems, function (item) {
						this.renderRefDataList(item);
					}, this);
				},
				renderRefDataList: function(item){
					var referenceDataListView = new ReferenceDataListView({
						model: item
					});
					this.$el.find('#ref_table_datas').append(referenceDataListView.render().el);
				},
				render: function () {
					this.$el.find('.referenceTable').remove();
					this.$el.append(this.tableTemplate());
					this.initSearchList();
					this.renderRefDatasList();
					return this;
				},
				renderH1: function () {
					this.$el.find('.directory_name_block, .editH1BlockForm').remove();
					this.$el.prepend(this.templateDirectoryName(this.collectionModel.attributes));
				},
				renderFooterBlock: function(){
					this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default add_ref">'+getTranslate("frontend.footer.buttons.add")+'</button><button type="button" class="btn btn-w-m btn-default cancel_ref">'+getTranslate("frontend.footer.buttons.cancel")+'</button><button type="button" class="btn btn-w-m btn-danger save_ref">'+getTranslate("frontend.footer.buttons.save")+'</button></div></footer>');
				}
			});
			
			var directoryView = new DirectoryView();
			
		}
	}

});
