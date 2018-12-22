define(['jquery', 'tabs'], function($, tabs) {

		var fieldTemplate = "<div class='list_field_block'><div class='field_data_block'><span class='fieldName'><%= name %></span></div><a class='btn btn-danger deleteField deleteButton'><i class='fa fa-times'></i></a><button type='button' class='btn btn-primary editField edit'><i class='fa fa-edit'></i></button></div>";

		var editFieldTemplate = '<div class="clear"></div><div class="editFieldBlockForm"><span class="block_edit_fields"><label><div>'+getTranslate("frontend.templates.name")+'</div><input type="text" class="name" value="<%= name %>" /></label><span class="block_edit_fields"><label><div>'+getTranslate("frontend.webforms.addresses.addresses")+'</div><input type="text" class="addresses" value="<%= addresses %>" /></label><button class="save_field btn btn-danger">'+getTranslate("frontend.footer.buttons.save")+'</button><button class="btn btn-secondary cancel_field">'+getTranslate("frontend.footer.buttons.cancel")+'</button></div>';

		var AddressModel = Backbone.Model.extend({
			defaults: {
				id: "",
				name: "",
				addresses:""
			}
		});

		var AddressesCollection = Backbone.Collection.extend({
		  model: AddressModel,
		  urlRoot: '/admin/logged/api/addresses'
		});

		var AddressesModel = Backbone.Model.extend({
			urlRoot: '/admin/logged/api/addresses'
		});


    // Return module with methods
    return {

        init: function(tabName, allTabs) {

			var DirectoryView = Backbone.View.extend({
				el: $("#content-block"),
				initialize: function () {
					shareBackboneFunctions.removeView(this);
					tabs.setTabs(allTabs);
					tabs.setActiveTab(tabName);
					tabs.render();
					this.renderFooterBlock();
					this.$el.append("<div id=\"wrap_tab_block\"></div>");
					this.collection = new AddressesCollection();
					this.showChangeCollection();
					this.render();
				},
				events: {
					"click button.save": "saveData",
					"click button.add": "addAddress"
				},
				saveData: function(){
					var self = this;
					var optionsSync = ({
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(data){
						var message = new Messages();
						message.renderSaveMessage();
						self.getAddressesReq();
					  }
					});
					var addressesModel = new AddressesModel;
					console.log(this.collection, 'addressesModel');
					generalSettings.sync('update',this.collection, optionsSync);
				},
				addAddress: function(){
					var address = {
						"name":getTranslate("frontend.webforms.addresses.newAddress")
					}
					this.collection.add(address);
				},
				showChangeCollection: function(){
					var self = this;
					this.collection.bind("add", function(){
						self.clearAddressesBlocks();
						self.plunkCollectionModel();
					});
					this.collection.bind("remove", function(){
						self.clearAddressesBlocks();
						self.plunkCollectionModel();
					});
				},
				render: function () {
					this.renderAddresses();
					return this;
				},
				renderFooterBlock: function(){
					this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default add">'+getTranslate("frontend.footer.buttons.add")+'</button><button type="button" class="btn btn-w-m btn-danger save">'+getTranslate("frontend.footer.buttons.save")+'</button></div></footer>');
				},
				clearAddressesBlocks: function(){
					this.wrapListBlock.html("");
				},
				createAddresses: function(item){
					this.fieldView = new FieldView({
						model: item
					});
					this.wrapListBlock.append(this.fieldView.render().el);
				},
				plunkCollectionModel: function(){
					_.each(this.collection.models, function (item) {
						this.createAddresses(item);
					}, this);
				},
				renderAddresses: function(){
					$("#wrap_tab_block").append("<ul id='list_addresses'></ul>");
					this.wrapListBlock = $("#list_addresses");
					this.getAddressesReq();
				},
				getAddressesReq: function(){
					var self = this;
					this.collectionReq = new AddressesModel();

					var optionsSync = ({
					  error:function(){
						alert(getTranslate("frontend.errors.data_response"));
					  },
					  success: function(data){
						self.collection.set(data.items);
					  }
					});
					generalSettings.sync('read',this.collectionReq, optionsSync);
				},
			});

			var directoryView = new DirectoryView();

			var FieldView = Backbone.View.extend({
				tagName: "li",
				className: "list_edit",
				template: _.template(fieldTemplate),
				editTemplate: _.template(editFieldTemplate),
				render: function () {
					this.generateFieldJson();
					return this;
				},
				events:{
					"click button.editField": "editField",
					"click .deleteField": "deleteField",
					"click button.save_field": "saveEdits",
					"click button.cancel_field": "cancelEdit",
				},
				editField: function(){
					this.$el.off('click', '.editRefData');
					this.$el.html(this.editTemplate(this.model.toJSON()));
				},
				generateFieldJson: function(){
					var jsonModel = this.model.toJSON();
					this.$el.html(this.template(jsonModel));
				},
				saveEdits: function(e){
					e.preventDefault();
					var formData = {};
					var modelJsonEditField = this.model.toJSON();
					this.$el.find("input").each(function () {
						var el = $(this);
						var value = el.val();
						if(el.attr('type') == "checkbox"){
							(el.prop('checked')) ? value = 1 : value = 0;
						}
						formData[el.attr("class")] = value;

					});
					this.model.set(formData);
					this.render();
				},
				cancelEdit: function(){
					this.render();
				},
				deleteField: function(){
					this.model.collection.remove(this.model)
				}
			});
		}


	}
});
