/**
 * @module package/quiqqer/matomo/bin/Panel
 */
define('package/quiqqer/matomo/bin/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Select',
    'Locale',
    'Ajax',
    'Users',

    'css!package/quiqqer/matomo/bin/Panel.css'

], function (QUI, QUIPanel, QUISelect, QUILocale, QUIAjax, Users) {
    "use strict";

    var lg = 'quiqqer/matomo';

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/matomo/bin/Panel',

        Binds: [
            '$onInject'
        ],

        options: {
            project: false
        },

        initialize: function (options) {
            this.parent(options);

            // defaults
            this.setAttributes({
                title: QUILocale.get(lg, 'panel.matomo.title')
            });

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * event: on create
         */
        $onInject: function () {
            var self = this;

            this.Loader.show();

            require(['Projects'], function (Projects) {
                Projects.getList().then(function (projects) {
                    var keys    = Object.keys(projects);
                    var project = keys[0];

                    self.setAttribute('project', project);

                    if (Object.getLength(projects) === 1) {
                        return self.$loadFrame();
                    }

                    var Select = new QUISelect({
                        name     : 'projectList',
                        showIcons: false,
                        events   : {
                            onChange: function (value) {
                                self.setAttribute('project', value);
                                self.$loadFrame();
                            }
                        }
                    });

                    for (var key in projects) {
                        if (!projects.hasOwnProperty(key)) {
                            continue;
                        }

                        Select.appendChild(
                            key,
                            key
                        );
                    }

                    self.addButton(Select);
                    Select.setValue(self.getAttribute('project'));

                }.bind(this));
            }.bind(this));
        },

        /**
         * load the iframe and the matomo data
         */
        $loadFrame: function () {
            this.Loader.show();

            var User = Users.getUserBySession(),
                Prom = Promise.resolve();

            if (!User.isLoaded()) {
                Prom = User.load();
            }

            Prom.then(function () {
                QUIAjax.get([
                    'ajax_project_get_config',
                    'package_quiqqer_matomo_ajax_md5'
                ], function (config, pass) {

                    var url   = config['matomo.settings.url'],
                        id    = config['matomo.settings.id'],
                        token = config['matomo.settings.token'];

                    var useTokenForStatisticsDisplay = config['matomo.settings.useTokenForStatisticsDisplay'];

                    this.getContent()
                        .getElements('.quiqqer-matomo-panel-nosettings,iframe')
                        .destroy();

                    if (url === '' || id === '') {
                        new Element('div', {
                            'class': 'quiqqer-matomo-panel-nosettings',
                            html   : QUILocale.get(lg, 'panel.error.settings.missing')
                        }).inject(this.getContent());

                        this.Loader.hide();
                        return;
                    }

                    this.getContent().set('html', '');
                    this.getContent().setStyle('background', '#edecec');

                    var frameParams = {
                        module: 'CoreHome',
                        action: 'index',
                        idSite: id,
                        period: 'day',
                        date  : 'yesterday'
                    };

                    var now                = new Date().getTime(),
                        opened             = parseInt(QUI.Storage.remove('matomo-opened')),
                        usersMatomoLogin    = User.getAttribute('quiqqer.matomo.login'),
                        usersMatomoPassword = User.getAttribute('quiqqer.matomo.pass');

                    if (!opened) {
                        opened = 0;
                    }

                    if (!useTokenForStatisticsDisplay && !usersMatomoLogin && !usersMatomoPassword) {
                        QUI.getMessageHandler().then(function (MH) {
                            MH.addInformation(QUILocale.get(lg, 'panel.notice.userdata.missing'));
                        });
                    }

                    if (useTokenForStatisticsDisplay && !token) {
                        QUI.getMessageHandler().then(function (MH) {
                            MH.addInformation(QUILocale.get(lg, 'panel.notice.token.missing'));
                        });
                    }

                    if (!useTokenForStatisticsDisplay && usersMatomoPassword && usersMatomoLogin && opened + 7200 < now) {
                        frameParams.module   = 'Login';
                        frameParams.action   = 'logme';
                        frameParams.login    = usersMatomoLogin;
                        frameParams.password = pass;
                    }

                    if (useTokenForStatisticsDisplay && token) {
                        frameParams.module = 'LoginTokenAuth';
                        frameParams.action = 'logme';
                        frameParams.token_auth = token;
                    }

                    // session storage
                    QUI.Storage.set('matomo-opened', now);

                    url = url.replace('https://', '').replace('http://', '');

                    var src = 'https://' + url + '/index.php?' + Object.toQueryString(frameParams);

                    new Element('iframe', {
                        src   : src,
                        styles: {
                            border: 'none',
                            height: 'calc(100% - 4px)',
                            width : '100%'
                        }
                    }).inject(this.getContent());

                    this.Loader.hide();
                }.bind(this), {
                    'package': 'quiqqer/matomo',
                    project  : this.getAttribute('project'),
                    str      : User.getAttribute('quiqqer.matomo.pass')
                });
            }.bind(this));
        }
    });
});
