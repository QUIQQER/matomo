/**
 * Disables the manual bridge setting when Matomo exposes its container setting.
 *
 * @module package/quiqqer/matomo/bin/controls/DataLayerBridgeSetting
 */
define('package/quiqqer/matomo/bin/controls/DataLayerBridgeSetting', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale'

], function(QUI, QUIControl, QUIAjax, QUILocale) {
    'use strict';

    const lg = 'quiqqer/matomo';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/matomo/bin/controls/DataLayerBridgeSetting',

        Binds: [
            '$onImport',
            '$refresh'
        ],

        initialize: function(options) {
            this.parent(options);

            this.$Project = null;
            this.$isImported = false;
            this.$loadedProject = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        setProject: function(Project) {
            this.$Project = Project;

            if (this.$isImported) {
                this.$refresh();
            }
        },

        $onImport: function() {
            this.$isImported = true;
            this.$refresh();
        },

        $refresh: function() {
            if (!this.$Project) {
                return;
            }

            const projectName = this.$Project.getName();

            if (this.$loadedProject === projectName) {
                return;
            }

            const Input = this.getElm();
            this.$loadedProject = projectName;
            Input.set('disabled', true);

            QUIAjax.get(
                'package_quiqqer_matomo_ajax_getDataLayerBridgeStatus',
                (status) => {
                    if (!status.automatic) {
                        Input.set('disabled', false);
                        Input.erase('title');
                        return;
                    }

                    Input.checked = status.useQuiqqerBridge;
                    Input.set(
                        'title',
                        QUILocale.get(
                            lg,
                            status.activelySyncGtmDataLayer
                                ? 'matomo.settings.dataLayerBridge.automatic.syncEnabled'
                                : 'matomo.settings.dataLayerBridge.automatic.syncDisabled'
                        )
                    );
                },
                {
                    projectName: projectName,
                    'package': 'quiqqer/matomo',
                    onError: () => {
                        this.$loadedProject = null;
                        Input.set('disabled', false);
                    }
                }
            );
        }
    });
});
