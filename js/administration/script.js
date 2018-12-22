var apiPath = '/admin/logged/api';
var apiPluginPath = '/admin/logged/api/plugin_api';

function setCookie(name, value, options) {
    options = options || {};
    var expires = options.expires;

    if (typeof expires === "number" && expires) {
        var d = new Date();
        d.setTime(d.getTime() + expires * 1000);
        expires = options.expires = d;
    }
    if (expires && expires.toUTCString) {
        options.expires = expires.toUTCString();
    }

    value = encodeURIComponent(value);

    var updatedCookie = name + "=" + value;

    for (var propName in options) {
        updatedCookie += "; " + propName;
        var propValue = options[propName];
        if (propValue !== true) {
            updatedCookie += "=" + propValue;
        }
    }
    document.cookie = updatedCookie;
}

function getCookie(name) {
    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function deleteCookie(name) {
    setCookie(name, "", {
        expires: -1
    })
}

$(document).ready(function () {
    $("#show_main_menu").on("click", toogleMainMenu);
});

function toogleMainMenu() {
    $("#main_menu").toggleClass("active");
    $("#wrapper").toggleClass("z-index700");
}

function hideMainMenu() {
    $("#main_menu").removeClass("active");
    $("#wrapper").removeClass("z-index700");
}

function showPreloader() {
    $("#preloader").show();
}

function hidePreloader() {
    $("#preloader").hide();
}

function versionCompare(v1, v2, options) {
    var lexicographical = options && options.lexicographical,
        zeroExtend = options && options.zeroExtend,
        v1parts = v1.split('.'),
        v2parts = v2.split('.');

    function isValidPart(x) {
        return (lexicographical ? /^\d+[A-Za-z]*$/ : /^\d+$/).test(x);
    }

    if (!v1parts.every(isValidPart) || !v2parts.every(isValidPart)) {
        return NaN;
    }

    if (zeroExtend) {
        while (v1parts.length < v2parts.length) v1parts.push("0");
        while (v2parts.length < v1parts.length) v2parts.push("0");
    }

    if (!lexicographical) {
        v1parts = v1parts.map(Number);
        v2parts = v2parts.map(Number);
    }

    for (var i = 0; i < v1parts.length; ++i) {
        if (v2parts.length == i) {
            return 1;
        }

        if (v1parts[i] == v2parts[i]) {
            continue;
        }
        else if (v1parts[i] > v2parts[i]) {
            return 1;
        }
        else {
            return -1;
        }
    }

    if (v1parts.length != v2parts.length) {
        return -1;
    }

    return 0;
}

Backbone.mySync = function (method, records, options) {
    if (!options) options = {};

    var url = records.urlRoot;
    var data = {};
    var addedUrl = null;
    if (records instanceof Backbone.Collection) {
        data = {"data": records.toJSON()};
        if (records.defaults) {
            addedUrl = records.defaults.url;
        }

    } else if (records instanceof Backbone.Model) {
        data = records.toJSON();
        addedUrl = records.get("url");
    }

    if (records.params) {
        Object.keys(records.params).forEach(function (param) {
            data[param] = records.params[param];
        });
    }

    if (addedUrl) {
        url += '/' + addedUrl;
    }

    if (data.url != null) delete data.url;
    var preloaderTimer;
    var ajaxObject = {
        url: url,
        data: data,
        beforeSend: function () {
            preloaderTimer = setTimeout(function () {
                showPreloader();
            }, 800);
        },
        complete: function () {
            clearTimeout(preloaderTimer);
            hidePreloader();
        },
        success: function (data) {
            if (options.success != null) {
                options.success(data);
            } else if (method === 'create' || method === 'update') {
                var message = new Messages();
                message.renderSaveMessage();
            }
        },
        error: function (req_obj, msg, error) {
            if (!requestErrorHandler(req_obj.status, JSON.parse(req_obj.responseText))) {
                if (options.error != null) {
                    options.error(req_obj, msg, error);
                } else {
                    alert('ошибка передачи данных с сервера');
                }
            }
        },
        dataType: 'json',
        cache: options.cache
    };

    if (method === 'create') {
        ajaxObject.type = 'PUT';
    } else if (method === 'update') {
        ajaxObject.type = 'POST';
    } else if (method === 'delete') {
        ajaxObject.type = 'DELETE';
    }

    var xhr = $.ajax(ajaxObject);
};

//Пагинация
var paginationViewTemplate = "<a <% if (!noHref) { %> href='#!<%= url %><%= number %>' <% } %><% if (active === true) { %> class='active' <% } %> ><%= number %></a>";

var ButtonPaginationView = Backbone.View.extend({
    tagName: "li",
    className: "pagination_li",
    template: _.template(paginationViewTemplate),
    render: function () {
        this.model.noHref = this.noHref;
        this.$el.append(this.template(this.model));
        return this;
    },
    events: {
        "click a": "changePage"
    },
    setViewContent: function (contentView) {
        this.contView = contentView;
    },
    changePage: function (e) {
        if (this.contView) {
            this.contView.changePage(this.model.number);
        }
    }
});

var PaginationView = Backbone.View.extend({
    tagName: "ul",
    className: "pagination",
    render: function () {
        this.renderPaginationButtons();
        return this;
    },
    setViewContent: function (contentView) {
        this.contView = contentView;
    },
    renderPaginationButtons: function () {
        var total = this.model.total || this.model.params.total;
        var limit = this.model.limit || this.model.params.limit;
        var numberOfButtons = Math.ceil(total / limit);
        this.renderTemplatesForButtons(numberOfButtons);
    },
    renderTemplatesForButtons: function (numberOfButtons) {
        if (numberOfButtons < 2) return false;

        var self = this;
        this.showActiveButtonPag();
        for (i = 0; i < numberOfButtons; i++) {
            var num = i;
            num++;
            self.renderTemplateButton(num);
        }
    },
    setUrlButton: function (url) {
        this.url = url;
    },
    setNoHrefValue: function (value) {
        this.noHref = value;
    },
    renderTemplateButton: function (num) {
        var buttonProp = {};
        buttonProp.number = num;
        buttonProp.url = this.url;
        buttonProp.active = this.activeNum === num;

        var buttonPaginationView = new ButtonPaginationView({
            model: buttonProp,
            noHref: this.noHref
        });
        buttonPaginationView.setViewContent(this.contView);
        this.$el.append(buttonPaginationView.render().el);
    },
    showActiveButtonPag: function () {
        var begin = Number.isFinite(this.model.begin) ? this.model.begin : this.model.params.begin;
        var limit = this.model.limit || this.model.params.limit;
        this.activeNum = (begin / limit) + 1;
    }
});


//define individual contact view
var GeneralSettings = Backbone.View.extend({
    sync: function (method, model, options) {
        return Backbone.mySync.call(this, method, model, options);
    }
});

var generalSettings = new GeneralSettings();

var logOutReq = Backbone.Model.extend({
    urlRoot: '/admin/logged/api/log_out'
});

var shareBackboneFunctions = {
    removeView: function (self) {
        self.trigger('remove-view');
        Backbone.View.prototype.on('remove-view', function () {
            Backbone.View.prototype.off();
            self.undelegateEvents();
        });
    }
};

var tree = {
    init: function () {
        //define individual contact view
        var TreeView = Backbone.View.extend({
            tagName: "div",
            className: "tree",
            id: "tree_sturcture",
            initialize: function () {
                this.render();
            }
        });
        var treeView = new TreeView();
        return treeView;
    },
    removeClassActiveNode: function () {
        $('.activeLiTree').removeClass('activeLiTree');
    },
    setNewData: function (idTree, data) {
        $('#' + idTree).jstree(true).settings.core.data = data;
        $('#' + idTree).jstree(true).refresh();
    },
    requestToData: function (urlRequest, options, reqData) {
        var self = this;
        var TreeModel = Backbone.Model.extend({
            get: function (path, data) { //название может быть какое угодно
                return this.fetch({
                    url: apiPath + urlRequest,
                    contentType: "application/json",
                    type: 'GET', //здесь можно писать и GET и POST
                    data: reqData,
                    success: function (data) {
                        var jstreeData = [];
                        jstreeData.push(data.attributes);
                        var jsTreeSettings = {};
                        jsTreeSettings.core = {'data': jstreeData};
                        jsTreeSettings.core.check_callback = true;

                        if (!options) {
                            options = {};
                        }

                        if (options.dnd) {
                            jsTreeSettings.plugins = ["dnd"];
                        }

                        $('#tree_sturcture').jstree(jsTreeSettings).on('changed.jstree', function (e, data) {
                            if (data != null) {
                                if (data.node != null) {
                                    var idToHref = data.node.a_attr.href;
                                    if (idToHref) {
                                        var activeRouter = routers.activeRouter();
                                        activeRouter += "/" + idToHref;
                                        Backbone.history.navigate(activeRouter, {trigger: true});
                                    }
                                }
                            }
                        });
                        /*.on("move_node.jstree", function (e, data) {
                                                    console.log(e, data, "move");
                                                })*/
                    },
                    error: function (req_obj, msg, error) {
                        requestErrorHandler(msg.status);
                        console.log('error request json tree of a pages');
                    }
                });
            }
        });

        requestToData = new TreeModel();
        return requestToData;
    }

};

var contentPart = {
    clear: function () {
        $('#content-block').empty();
    }
}

function requestErrorHandler(codeError, error) {
    //Ошибка авторизации
    if (codeError == 401 || codeError == 0) {
        location.reload();
        return true;
    } else if (codeError == 400) {
        if (error != null) {
            if (error.description != null) {
                $('#triggerToogleModalForm').click();
                $('#modal-form .modal-body').html(error.description);
            }
        }
        return true;
    }
}

function getIndex(elem) {
    var $p = elem.parent().children();
    return $p.index(elem);
}

requirejs.config({
    baseUrl: '/js/administration',
    paths: {
        'jstree': 'treeview/jstree',
        'template_page': 'backbone/templates/template_page',
        'tree_functions': "backbone/system/tree_functions",
        'jquery-ui': "jqueryui/jquery-ui.min",
        'multi_sortable': 'jqueryui/multi_sortable',
        'tinymce': 'tinymce/tinymce',
        'tinymce_paste': 'tinymce/paste/plugin.min',
        'tinymce_table': 'tinymce/table/plugin.min',
        'tinymce_code': 'tinymce/code/plugin.min',
        'tinymce_link': 'tinymce/link/plugin.min',
        'tinymce_image': 'tinymce/image/plugin.min',
        'tinymce_imagetools': 'tinymce/imagetools/plugin.min',
        'tinymce_ru_lang': 'tinymce/lang/ru',
        'jstree.dnd': 'treeview/jstree.dnd',
        'list_view': 'backbone/system/list_data',
        'tabs': 'backbone/system/tabs'
    },
    shim: {
        'jquery-ui': ['jquery'],
        'multi_sortable': ['jquery'],
        'tinymce_paste': ['tinymce'],
        'tinymce_table': ['tinymce'],
        'tinymce_code': ['tinymce'],
        'tinymce_link': ['tinymce'],
        'tinymce_image': ['tinymce'],
        'tinymce_imagetools': ['tinymce'],
        'tinymce_ru_lang': ['tinymce'],
        'jstree.dnd': ['jstree', 'jquery']
    }
});

var initApp = function () {
    var optionSubDomainSelect = '<option value = "<%= id %>" ><%= text %></option>';
    var reqSubDomainsUrl = '/admin/logged/api/sub_domains/';

    window.DomainsModelReq = Backbone.Model.extend({
        urlRoot: reqSubDomainsUrl
    });

    window.SubDomainModel = Backbone.Model.extend({
        urlRoot: reqSubDomainsUrl,
        _urlDef: reqSubDomainsUrl,
        setUrlForId: function (id) {
            if (!id) return false;
            this.urlRoot = this._urlDef;
            this.urlRoot += id + "/";
        },
        defaults: {
            id: null,
            textId: "sub",
            text: getTranslate("frontend.settings.domains.subDomain"),
            defaultValue: 0
        }
    });

    var SubDomainCollection = Backbone.Collection.extend({
        model: SubDomainModel
    });

    window.subDomainCollection = new SubDomainCollection();
    var GeneralView = Backbone.View.extend({
        el: ".general_settings_block",
        subDomainSelect: _.template(getTranslate("frontend.settings.domains.sites") + ' ' + '<select class="value" id="subDomainSelect"></select>'),
        optionSubDomainSelect: _.template(optionSubDomainSelect),
        subDomainCookieName: "subDomain",
        initialize: function () {
            this.render();
            var self = this;
            subDomainCollection.bind("change add remove reset", function () {
                self.renderSubDomains();
            });
        },
        render: function () {
            this.$el.empty();
            this.$el.append("<div id='wrapSelectSubDomain'></div>");
            this.$wrapSubDomain = $("#wrapSelectSubDomain");
            this.$wrapSubDomain.html(this.subDomainSelect());
            this.$subDomainSelect = this.$wrapSubDomain.find("#subDomainSelect");
            this.renderSelectSubDomain();
            this.$el.append('<div id="log_out">' + getTranslate("frontend.header.log_out") + '</div>');
            return this;
        },
        renderSelectSubDomain: function () {
            var self = this;
            this.modelSubDomainsReq = new DomainsModelReq();
            var optionsSync = ({
                error: function (e) {
                    alert(e.description);
                },
                success: function (data) {
                    subDomainCollection.set(data);
                    self.renderSubDomains();
                }
            });
            generalSettings.sync('read', this.modelSubDomainsReq, optionsSync);
        },
        renderSubDomains: function () {
            this.$subDomainSelect.empty();
            _.each(subDomainCollection.models, function (item) {
                this.renderSubDomain(item);
            }, this);
            this.setActiveSubDomain();
        },
        setActiveSubDomain: function () {
            var cookieSubDomainVal = parseInt(getCookie(this.subDomainCookieName));
            if (subDomainCollection.findWhere({id: cookieSubDomainVal})) {
                this.setSubDomainVal(cookieSubDomainVal);
            } else {
                this.setDefaultSubDomain();
            }
        },
        setSubDomainVal: function (id) {
            this.$subDomainSelect.val(id);
        },
        setDefaultSubDomain: function () {
            var defaultModelValue = subDomainCollection.findWhere({defaultValue: 1});
            var activeId = defaultModelValue.get("id");
            this.setActiveSubDomainToCokkie(activeId);
            this.setSubDomainVal(activeId);
        },
        setActiveSubDomainToCokkie: function (val) {
            setCookie(this.subDomainCookieName, val);
        },
        changeSubDomainSelect: function (e) {
            var val = $(e.target).val();
            this.setActiveSubDomainToCokkie(val);
            location.reload();
        },
        renderSubDomain: function (item) {
            this.$subDomainSelect.append(this.optionSubDomainSelect(item.toJSON()));
        },
        events: {
            "click #log_out": "logOut",
            "change #subDomainSelect": "changeSubDomainSelect"
        },
        logOut: function () {
            var logOutModel = new logOutReq();
            var option = {
                success: function () {
                    deleteCookie('PHPSESSID');
                    location.reload();
                }
            };
            generalSettings.sync('read', logOutModel, option);
        }
    });

    var generalView = new GeneralView();
    window.templateH1 = '<div class="directory_name_block"><h1><%= name %></select></h1><button type="button" class="btn btn-primary editH1"><i class="fa fa-edit"></i></button></div><div class="clear"></div><div class="clear"></div>';
    window.templateEditH1 = '<div class="clear"></div><div class="editH1BlockForm editBlockForm"><label><div>' + getTranslate("frontend.share.name") + '</div><input type="text" class="name" value="<%= name %>" /></label><button class="save_h1 btn btn-danger">' + getTranslate("frontend.footer.buttons.save") + '</button><button class="btn btn-secondary cancel_h1">' + getTranslate("frontend.footer.buttons.cancel") + '</button></div>';
    window.successMessage = '<div id="success_save" class="success_message"><div><%= message %></div></div>';
    window.Messages = Backbone.View.extend({
        el: $("#content-block"),
        template: _.template(successMessage),
        render: function () {
            if ($('.success_message').length == 0) {
                this.$el.prepend(this.template(this.messageData));
                this.fadeOutMessageBlock();
            }
        },
        renderSaveMessage: function () {
            this.messageData = {"message": getTranslate("frontend.share.saved")};
            this.render();
        },
        showMessageByKey: function (keyString) {
            this.messageData = {"message": getTranslate(keyString)};
            this.render();
        },
        showMessageByPluginKey: function (keyString, pluginName) {
            this.messageData = {"message": getPluginTranslate(keyString, pluginName)};
            this.render();
        },
        fadeOutMessageBlock: function () {
            $('.success_message').fadeOut(1000, function () {
                $(this).remove();
            });
        }
    });


    (function ($) {
        //define product model
        var Contact = Backbone.Model.extend({
            defaults: {
                photo: "img/placeholder.png"
            }
        });

        //define directory collection
        var Directory = Backbone.Collection.extend({
            model: Contact
        });

        //define individual contact view
        var ContactView = Backbone.View.extend({
            tagName: "li",
            className: "main_menu_list",
            template: $("#contactTemplate").html(),
            render: function () {
                var tmpl = _.template(this.template);
                $(this.el).html(tmpl(this.model.toJSON()));
                return this;
            }
        });

        var MainMenuModel = Backbone.Model.extend({
            urlRoot: '/admin/logged/api/sections'
        });

        var PluginRoutes = Backbone.Model.extend({
            urlRoot: apiPath + '/plugin_routes'
        });

        //define master view
        var DirectoryView = Backbone.View.extend({
            el: $("#side-menu"),
            initialize: function () {
                this.getMainMenu();
            },
            render: function () {
                var that = this;
                _.each(this.collection.models, function (item) {
                    that.renderContact(item);
                }, this);
            },
            getMainMenu: function () {
                var self = this;
                var mainMenuModel = new MainMenuModel();

                var optionsSync = ({
                    error: function () {
                        alert(getTranslate("frontend.errors.data_response"));
                    },
                    success: function (data) {
                        self.collection = new Directory(data);
                        self.render();
                        if (data.length === 0) {
                            console.log('There are not sections');
                            return false;
                        }
                        var firstSection = data[0].link.replace('/', '');

                        var pluginRoutes = new PluginRoutes();
                        var optionsPluginRoutes = {
                            success: function (data) {
                                initRouters(firstSection, data);
                            }
                        };
                        generalSettings.sync('read', pluginRoutes, optionsPluginRoutes);
                    }
                });
                generalSettings.sync('read', mainMenuModel, optionsSync);
            },
            renderContact: function (item) {
                var contactView = new ContactView({
                    model: item
                });
                this.$el.append(contactView.render().el);
            }
        });

        //create instance of master view
        var directory = new DirectoryView();
    }(jQuery));


    var RouterFactory = function () {
        this.routes = {
            "": "events",
            "!/": "events",
            "!/events": "events",
            "!/structure": "structure",
            "!/structure/:id": "structure_by_id",
            "!/type_template_data": "type_template_data",
            "!/type_template_data/:id": "type_template_data_by_id",
            "!/references/:page": "references_page",
            "!/references": "references_page",
            "!/references/reference/:id": "reference",
            "!/references/reference/:ref_id/element/:elem_id": "reference_element",
            "!/webforms": "addresses",
            "!/webforms/addresses": "addresses",
            "!/webforms/mail_templates": "mail_templates",
            "!/webforms/mail_templates/mail_template/:id": "mail_template",
            "!/webforms/messages": "messages",
            "!/webforms/messages/:page": "messages",
            "!/webforms/messages/message/:id": "message",
            "!/users/:page": "users",
            "!/users": "users",
            "!/users/user/:id": "user",
            "!/users/user/main_rights/:id": "main_rights",
            "!/users/user/plugin_rights/:id": "plugin_rights",
            "!/plugins": "installed_plugins",
            "!/plugins/installed": "installed_plugins",
            "!/plugins/installed/:page": "installed_plugins",
            "!/plugins/download": "download_plugins",
            "!/plugins/download/:page": "download_plugins",
            "!/emarket": "emarket",
            "!/emarket/:page": "emarket",
            "!/emarket/order/:page": "order",
            "!/backups": "export",
            "!/backups/export": "export",
            "!/backups/import": "import",
            "!/settings": "common_settings",
            "!/settings/common": "common_settings",
            "!/settings/robots": "robots_settings",
            "!/settings/domains": "domains",
            "!/settings/about_program": "about_program"
        };

        this.tabs = {
            extend: {
                plugins: {}
            },
            userTabs: {
                common: {
                    name: getTranslate("frontend.users.main"),
                    href: "#!/users/user"
                },
                main_rights: {
                    name: getTranslate("frontend.users.main_rights"),
                    href: "#!/users/user/main_rights"
                },
                plugin_rights: {
                    name: getTranslate("frontend.users.plugin_rights"),
                    href: "#!/users/user/plugin_rights"
                }
            }, pluginTabs: {
                installed: {
                    name: getTranslate("frontend.plugins.installed"),
                    href: "#!/plugins/installed"
                },
                download: {
                    name: getTranslate("frontend.plugins.download"),
                    href: "#!/plugins/download"
                }
            }, settingsTabs: {
                common: {
                    name: getTranslate("frontend.settings.common.common"),
                    href: "#!/settings/common"
                },
                robots: {
                    name: getTranslate("frontend.settings.common.robots"),
                    href: "#!/settings/robots"
                },
                domains: {
                    name: getTranslate("frontend.settings.domains.domains"),
                    href: "#!/settings/domains"
                },
                about_program: {
                    name: getTranslate("frontend.settings.aboutProgramm.aboutProgramm"),
                    href: "#!/settings/about_program"
                }
            },
            webformSettingsTabs: {
                addresses: {
                    name: getTranslate("frontend.webforms.addresses.addresses"),
                    href: "#!/webforms/addresses"
                },
                mail_templates: {
                    name: getTranslate("frontend.webforms.mail_templates.mail_templates"),
                    href: "#!/webforms/mail_templates"
                },
                messages: {
                    name: getTranslate("frontend.webforms.messages.messages"),
                    href: "#!/webforms/messages"
                }
            },
            backUpTabs: {
                export: {
                    name: getTranslate("frontend.back_ups.export"),
                    href: "#!/backups/export"
                },
                import: {
                    name: getTranslate("frontend.back_ups.import"),
                    href: "#!/backups/import"
                }
            }
        }

        var self = this;
        this.routerMethods = {
            robots_settings: function () {
                contentPart.clear();
                require(["backbone/settings/robots"], function (references) {
                    references.init("robots", self.tabs.settingsTabs);
                });
            },
            about_program: function () {
                contentPart.clear();
                require(["backbone/settings/about_program"], function (references) {
                    references.init("about_program", self.tabs.settingsTabs);
                });
            },
            installed_plugins: function (page) {
                contentPart.clear();
                if (!page) page = 1;
                require(["backbone/plugins/installed"], function (plugins) {
                    plugins.init("installed", self.tabs.pluginTabs, page);
                });
            },
            download_plugins: function (page) {
                contentPart.clear();
                if (!page) page = 1;
                require(["backbone/plugins/download"], function (plugins) {
                    plugins.init("download", self.tabs.pluginTabs, page);
                });
            },
            domains: function () {
                contentPart.clear();
                require(["backbone/settings/domains"], function (references) {
                    references.init("domains", self.tabs.settingsTabs);
                });
            },
            common_settings: function () {
                contentPart.clear();
                require(["backbone/settings/common"], function (references) {
                    references.init("common", self.tabs.settingsTabs);
                });
            },
            users: function (page) {
                contentPart.clear();
                if (!page) page = 1;
                require(["backbone/users/users"], function (references) {
                    references.init(page, self.tabs.settingsTabs);
                });
            },
            order: function (id) {
                contentPart.clear();
                require(["backbone/structure/structure_page"], function (structure_page) {
                    structure_page.init(id, '/admin/logged/api/emarket', null, true);
                });
            },
            user: function (id) {
                contentPart.clear();
                require(['backbone/users/user', 'backbone/structure/structure_page'], function (user, structure_page) {
                    user.init(id, 'common', self.tabs.userTabs, structure_page);
                });
            },
            main_rights: function (id) {
                contentPart.clear();
                require(["backbone/users/main_rights"], function (main) {
                    main.init(id, 'main_rights', self.tabs.userTabs);
                });
            },
            plugin_rights: function (id) {
                contentPart.clear();
                require(["backbone/users/plugin_rights"], function (main) {
                    main.init(id, 'plugin_rights', self.tabs.userTabs);
                });
            },
            emarket: function (page) {
                contentPart.clear();
                if (!page) page = 1;
                require(["backbone/emarket/emarket"], function (emarket) {
                    emarket.init(page);
                });
            },
            settings: function () {
                contentPart.clear();
            },
            addresses: function () {
                contentPart.clear();
                require(["backbone/webforms/addresses"], function (instance) {
                    instance.init("addresses", self.tabs.webformSettingsTabs);
                });
            },
            mail_templates: function (page) {
                contentPart.clear();
                if (!page) page = 1;
                require(["backbone/webforms/mail_templates"], function (instance) {
                    instance.init("mail_templates", self.tabs.webformSettingsTabs, page);
                });
            },
            mail_template: function (id) {
                contentPart.clear();
                require(["backbone/structure/structure_page"], function (structure_page) {
                    structure_page.init(id, '/admin/logged/api/mail_templates');
                });
            },
            messages: function (page) {
                contentPart.clear();
                if (!page) page = 1;
                require(["backbone/webforms/messages"], function (instance) {
                    instance.init("messages", self.tabs.webformSettingsTabs, page);
                });
            },
            message: function (id) {
                contentPart.clear();
                require(["backbone/webforms/message"], function (page) {
                    page.init("messages", self.tabs.webformSettingsTabs, id);
                });
            },
            events: function () {
                contentPart.clear();
                require(["backbone/events/events"], function (instance) {
                    instance.init();
                });
            },
            structure: function () {
                contentPart.clear();
                require(["backbone/structure/structure"], function (structure) {
                    structure.init();
                });
            },
            reference_element: function (ref_id, elem_id) {
                contentPart.clear();
                require(["backbone/structure/structure_page"], function (structure_page) {
                    structure_page.init(elem_id, '/admin/logged/api/reference_element');
                });
            },
            reference: function (id) {
                contentPart.clear();
                require(["backbone/reference/reference_datas"], function (references) {
                    references.init(id);
                });
            },
            structure_by_id: function (id) {
                contentPart.clear();
                require(["backbone/structure/structure_page"], function (structure_page) {
                    structure_page.init(id, '/admin/logged/api/page', true);
                });
            },
            references_page: function (page) {
                contentPart.clear();
                if (!page) page = 1;
                require(["backbone/reference/references"], function (references) {
                    references.init(page);
                });
            },
            type_template_data: function () {
                contentPart.clear();
                require(["backbone/templates/templates"], function (template) {
                    template.init();
                });
            },
            type_template_data_by_id: function (id) {
                contentPart.clear();
                require(["template_page"], function (template_page) {
                    template_page.init(id);
                });
            },
            export: function () {
                contentPart.clear();
                require(["backbone/backup/export"], function (main) {
                    main.init('export', self.tabs.backUpTabs);
                });
            },
            import: function () {
                contentPart.clear();
                require(["backbone/backup/import"], function (main) {
                    main.init('import', self.tabs.backUpTabs);
                });
            },
            activeRouter: function () {
                var activeRouter = Backbone.history.getFragment();
                if (activeRouter == '') {
                    activeRouter = '#!/' + this.routes[""];
                } else {
                    activeRouter = '#' + activeRouter;
                }
                return activeRouter;
            },
            activeLinkSideMenu: function () {
                var activeRouter = this.activeRouter();
                $('#side-menu li').removeClass('active');
                var firstUrl = activeRouter.split("/")[1];
                var activeHref = '#!/' + firstUrl;
                var $sideMenuLink = $('#side-menu a[href="' + activeHref + '"]');
                if ($sideMenuLink.length === 0) {
                    this.setActiveFirstLinkOfSideMenu();
                    return false;
                }
                $sideMenuLink.parent('li').addClass('active');
            },
            setActiveFirstLinkOfSideMenu: function () {
                $('#side-menu li').eq(0).addClass('active');
            }
        }
    };

    RouterFactory.prototype.addRoutes = function (routes) {
        var self = this;
        Object.keys(routes).forEach(function (route) {
            self.routes[route] = routes[route];
        });
    };

    RouterFactory.prototype.addRouteMethods = function (methods) {
        var self = this;
        Object.keys(methods).forEach(function (method) {
            var methodParams = methods[method];
            var funcParams = methodParams.hasIdParam ? 'id' : '';
            var functionBody = '';

            self.routerMethods[method] = function (id) {
                contentPart.clear();
                require(['/' + methodParams.filePath + '.js'], function (instance) {
                    $.when(getPluginLangData(methodParams.pluginName)).then(
                        function (data) {
                            if (methodParams.tab && methodParams.tab.name && methodParams.tab.tabGroup) {
                                if (id) {
                                    instance.init(methodParams.tab.name, self.tabs.extend.plugins[methodParams.pluginName][methodParams.tab.tabGroup], id, methodParams.pluginName);
                                } else {
                                    instance.init(methodParams.tab.name, self.tabs.extend.plugins[methodParams.pluginName][methodParams.tab.tabGroup], methodParams.pluginName);
                                }
                            } else if (id) {
                                instance.init(id, methodParams.pluginName);
                            } else {
                                instance.init(methodParams.pluginName);
                            }
                        }
                    )
                });
            };
        });
    };

    RouterFactory.prototype.addTabs = function (tabs) {
        var self = this;
        Object.keys(tabs.plugins).forEach(function (pluginName) {
            Object.keys(tabs.plugins[pluginName]).forEach(function (tab) {
                self.tabs.extend.plugins[pluginName] = {};
                self.tabs.extend.plugins[pluginName][tab] = tabs.plugins[pluginName][tab];
            });
        });
    };

    RouterFactory.prototype.setMainSection = function (section) {
        var pathForSection = this.routes['!/' + section];
        this.routes[""] = pathForSection;
        this.routes["!/"] = pathForSection;
    };

    RouterFactory.prototype.init = function () {
        var joinRouters = this.routerMethods;
        joinRouters.routes = this.routes;
        var Router = Backbone.Router.extend(joinRouters);
        this.router = new Router();
        return this.router;
    };

    function initRouters(mainSection, options) {
        var routersFactory = new RouterFactory();
        routersFactory.setMainSection(mainSection);
        if (options && options.routes && options.routeMethods) {
            routersFactory.addRoutes(options.routes);
            routersFactory.addRouteMethods(options.routeMethods);
            if (options.tabs) routersFactory.addTabs(options.tabs);
        }

        window.routers = routersFactory.init(); // Создаём контроллер
        var notFirstRout = false;
        routers.bind('route', function (route) {
            this.activeLinkSideMenu();
            if (notFirstRout) {
                hideMainMenu();
            } else {
                notFirstRout = true;
            }
        });
        Backbone.history.start();  // Запускаем HTML5 History push
    }
};

var lang = 'en';
var pluginLangData = {};
var langData;

function getTranslate(key) {
    var explKeys = key.split(".");
    if (!key) {
        return "No key specified";
    } else if (explKeys.length > 0) {
        return recursiveTanslt(explKeys, langData, 0);
    }
}

function recursiveTanslt(keyArr, data, index) {
    var key = keyArr[index];
    var nwIndex = ++index;
    if (typeof data[key] === "object") {
        return recursiveTanslt(keyArr, data[key], nwIndex);
    } else {
        return data[key];
    }
}

function getPluginTranslate(key, pluginName) {
    var explKeys = key.split(".");
    if (!key) {
        return "No key specified";
    } else if (explKeys.length > 0) {
        var pluginData = pluginLangData[pluginName];
        return recursiveTanslt(explKeys, pluginData, 0);
    }
}

var ActiveLangModelReq = Backbone.Model.extend({
    urlRoot: '/admin/logged/api/active_lang'
});

//получаем активный язык
var collectionActiveLangReq = new ActiveLangModelReq();
var optionsSyncActiveLang = ({
    error: function () {
        alert('no active lang');
    },
    success: function (data) {
        lang = data.text_id;
        getLangData(lang);
    }
});

generalSettings.sync('read', collectionActiveLangReq, optionsSyncActiveLang);

function getLangData(lang) {
    var langReqString = '/js/administration/json/i18n/' + lang + '.json';
    //получаем данные активного языка
    var LangModelReq = Backbone.Model.extend({
        urlRoot: langReqString
    });
    this.collectionLangReq = new LangModelReq();
    var optionsSyncLang = ({
        error: function () {
            alert('no lang data');
        },
        success: function (data) {
            langData = data;
            initApp();
        },
        cache: true
    });
    generalSettings.sync('read', this.collectionLangReq, optionsSyncLang);
}

function getPluginLangData(pluginName) {
    var dfd = jQuery.Deferred();
    var langReqString = '/plugins/' + pluginName + '/i18n/' + lang + '.json';
    //получаем данные активного языка
    var LangModelReq = Backbone.Model.extend({
        urlRoot: langReqString
    });
    var modelLangReq = new LangModelReq();
    var optionsSyncLang = ({
        error: function () {
            alert('no lang data');
        },
        success: function (data) {
            pluginLangData[pluginName] = data;
            dfd.resolve(true);
        },
        cache: true
    });

    generalSettings.sync('read', modelLangReq, optionsSyncLang);
    return dfd.promise();
}