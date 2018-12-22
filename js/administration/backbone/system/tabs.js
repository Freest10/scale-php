define(function (tabs) {
    return {
        render: function () {
            var htmlToAppend = this.renderHtlmToAppend();
            $("#content-block").prepend(htmlToAppend);
        },
        renderHtlmToAppend: function () {
            var tabsHtml = "<div class='btn-group tabs'>";
            var self = this;
            var data = this.getTabsData();
            for (var tab in data) {
                var activeClass = "";
                if (tab == self.activeTab) activeClass = 'active';
                tabsHtml += '<a href="' + data[tab].href + '" class="btn ' + activeClass + '">' + data[tab].name + '</a>';
            }
            tabsHtml += "</div>";
            return tabsHtml;
        },
        setTabs: function (allTabs) {
            this.allTabs = allTabs;
            this.removeTabsData();
        },
        addIdToAllPaths: function (id) {
            var addingIdPath = '/' + id;
            this.setTabsData();
            for (var tab in this.tabsData) {
                if (this.tabsData[tab].href.indexOf(addingIdPath) < 0) {
                    this.tabsData[tab].href += addingIdPath;
                }
            }
        },
        removeTabsData: function(){
            this.tabsData = null;
        },
        getTabsData: function () {
            if (!this.tabsData) {
                this.setTabsData();
            }
            return this.tabsData;
        },
        setTabsData: function () {
            var stringifyData = JSON.stringify(this.allTabs);
            this.tabsData = JSON.parse(stringifyData);
        },
        setActiveTab: function (tab) {
            this.activeTab = tab;
        }
    }
});