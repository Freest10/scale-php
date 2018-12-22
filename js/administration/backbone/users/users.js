define(['jquery', 'list_view'], function ($, listView) {
    return {
        init: function (page) {
            var params = {
                'page': page,
                'urlReq': '/admin/logged/api/users',
                'urlLink': '/users/user/',
                'urlPagination': "/users/",
                'editButton': true,
                'deleteButton': true
            };

            listView.setParams(params);

            listView.directoryView.prototype.renderFooterBlock = function () {
                this.$el.append('<footer class="footer_blck"><div class="save_cancel_template_block"><button type="button" class="btn btn-w-m btn-default delete_user">' + getTranslate("frontend.footer.buttons.delete") + '</button><button type="button" class="btn btn-w-m btn-danger create_user">' + getTranslate("frontend.footer.buttons.create_user") + '</button></div></footer>');
            };

            listView.directoryView.prototype.events = {
                "click button.create_user": "createUser"
            };

            listView.directoryView.prototype.createUser = function () {
                this.putData();
            };

            listView.directoryView.prototype.addToInit = function () {
                if(this.renderFooterBlock) this.renderFooterBlock();
            };

            listView.init();
        }
    }
});
