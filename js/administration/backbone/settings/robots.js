define(['jquery', 'tabs'], function($, tabs) {

    // Return module with methods
    return {
        init: function(tabName, allTabs) {

            var RobotsModel = Backbone.Model.extend({
                urlRoot: '/admin/logged/api/robots'
            });
			
			var DirectoryView = Backbone.View.extend({
				el: $("#content-block"),
				initialize: function () {
					tabs.setTabs(allTabs);
					tabs.setActiveTab(tabName);
					tabs.render();
					this.renderFooterBlock();
					this.$el.append("<div id=\"wrap_tab_block\"></div>");
					this.wrapTabBlock = $('#wrap_tab_block');
					this.appendSearchIndex();
                    this.collection = new RobotsModel();
                    this.getRobots();
				},
				appendSearchIndex: function(){
					this.wrapTabBlock.append("<div class='tab_prop_block'><textarea id='robots' class='robots_text_area'></textarea></div>");
				},
				events:{
					'click button.save' : 'setRobots',
					'click button.cancel' : 'getRobots'
				},
                getRobots: function(){
                    var self = this;

                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                        	if(data.data) $("#robots").val(data.data);
                        }
                    });
                    generalSettings.sync('read',this.collection, optionsSync);
                },
                setRobots: function(){
                    var self = this;

                    var optionsSync = ({
                        error:function(){
                            alert(getTranslate("frontend.errors.data_response"));
                        },
                        success: function(data){
                            if(data.data) $("#robots").val(data.data);
                            self.getRobots();
                        }
                    });
                    this.collection.set({data: $("#robots").val()});
                    generalSettings.sync('update',this.collection, optionsSync);
                },
				renderFooterBlock: function(){
					this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default cancel">'+getTranslate("frontend.footer.buttons.cancel")+'</button><button type="button" class="btn btn-w-m btn-danger save">'+getTranslate("frontend.footer.buttons.save")+'</button></div></footer>');
				}
			});
			
			var directoryView = new DirectoryView();
		}
	}
});	