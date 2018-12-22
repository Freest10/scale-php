var tree = {
			
	init: function(){
		//define individual contact view
		var TreeView = Backbone.View.extend({
			tagName: "div",
			className: "tree",
			id: "tree_sturcture",
			initialize: function() {
				this.render();
			}
		});
		var treeView = new TreeView();
		return treeView;
	},
	requestToData: function(urlRequest){
		var TreeModel = Backbone.Model.extend({
			get: function(path, data){ //�������� ����� ���� ����� ������
				return this.fetch({
					contentType:"application/json",
					type:'GET', //����� ����� ������ � GET � POST
					url:urlRequest,
					success:function(data) {
						var jstreeData = [];
						jstreeData.push(data.attributes);
						$('#tree_sturcture').jstree({ 'core' : {'data' : jstreeData} }).on('changed.jstree', function (e, data) {
							var idToHref = data.node.a_attr.href;
							if(idToHref){
								var activeRouter = routers.activeRouter();
								activeRouter += "/"+idToHref;
								Backbone.history.navigate(activeRouter, {trigger: true}); 
							}
						})
					},
					error: function(){
						console.log('error request json tree of a pages');
					}
				});
			}
		});
		
		requestToData = new TreeModel();
		return requestToData;
	}

};	