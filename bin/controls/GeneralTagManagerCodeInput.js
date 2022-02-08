/**
 * As QUIQQER does not allow HTML in the default setting inputs, we need a custom input.
 *
 * @module package/quiqqer/matomo/bin/controls/GeneralTagManagerCodeInput
 *
 * @author PCSG (Jan Wennrich)
 */
define('package/quiqqer/matomo/bin/controls/GeneralTagManagerCodeInput', [

    'qui/QUI',
    'qui/controls/Control',

    'Ajax'

], function (QUI, QUIControl, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/matomo/bin/controls/GeneralTagManagerCodeInput',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.$Elm = null;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });

            this.parent(options);
        },

        setProject: function (Project) {
            this.$Project = Project;

            this.$onImport();
        },

        $onImport: function () {
            if (!this.$Project) {
                return;
            }

            let LoadingInput = this.getElm();

            QUIAjax.get('package_quiqqer_matomo_ajax_getGeneralTagManagerCode', code => {
                this.$Textarea = new Element('textarea', {
                    value: code,
                    name: 'matomo.settings.tagmanager.code.general',
                    styles: {
                        width: '100%',
                        height: '12em'
                    }
                });

                this.$Textarea.inject(LoadingInput, 'after');
            }, {
                projectName   : this.$Project.getName(),
                'package': 'quiqqer/matomo'
            });
        }
    });
});