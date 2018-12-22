define(['jquery', 'list_view'], function($, listView) {

    return {
        init: function(page) {
			
			var params = {
				'page': page,
				'urlReq': '/admin/logged/api/emarket',
				'urlLink': '/emarket/order/',
				'urlPagination': "/emarket/",
				'editButton': false,
				'deleteButton': true
			}
			
			listView.setParams(params);
			if( listView.directoryView.prototype.renderFooterBlock != null) delete listView.directoryView.prototype.renderFooterBlock;
			listView.init();

		}
	}

});
