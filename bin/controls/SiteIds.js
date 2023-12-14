/**
 * @module package/quiqqer/matomo/bin/controls/SiteIds
 *
 * @author PCSG (Jan Wennrich)
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/matomo/bin/controls/SiteIds', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/translator/bin/controls/Update'

], function(QUI, QUIControl, TranslatorUpdate) {
    'use strict';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/matomo/bin/controls/SiteIds',

        Binds: [
            '$onImport',
            'setProject'
        ],

        initialize: function(options) {
            this.$Elm = null;
            this.$Project = null;

            this.addEvents({
                onImport: this.$onImport
            });

            this.parent(options);
        },

        /**
         * Set the internal project
         *
         * @param {Object} Project
         */
        setProject: function(Project) {
            this.$Project = Project;

            if (!this.$Languages) {
                this.$onImport();
            }
        },

        /**
         * event : on import
         */
        $onImport: function() {
            if (!this.$Project) {
                return;
            }

            new TranslatorUpdate({
                'group': 'project/' + this.$Project.getName(),
                'var': 'matomo.siteID',
                events: {
                    onChange: () => {
                        this.$Languages.save();
                    }
                }
            }).inject(this.$Elm);
        }
    });
});