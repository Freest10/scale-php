define(['jquery', 'tabs'], function ($, tabs) {
    return {
        init: function(id, tabName, allTabs, structure_page) {
            var DirectoryView = Backbone.View.extend({
                el: $("#content-block"),
                initialize: function () {
                    shareBackboneFunctions.removeView(this);
                    tabs.setTabs(allTabs);
                    tabs.addIdToAllPaths(id);
                    tabs.setActiveTab(tabName);
                    tabs.render();
                    this.$el.append("<div id=\"wrap_tab_block\"></div>");
                    this.wrapTabBlock = $('#wrap_tab_block');
                    structure_page.setWrapBlockId('wrap_tab_block');
                    structure_page.init(id, '/admin/logged/api/users');
                }
            });

            var directoryView = new DirectoryView();
        }
    }
});