/**
 * @module package/quiqqer/matomo/bin/controls/TagManagerCodesLanguages
 *
 * @author PCSG (Jan Wennrich)
 */
define('package/quiqqer/matomo/bin/controls/TagManagerCodesLanguages', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',

    'css!package/quiqqer/matomo/bin/controls/TagManagerCodesLanguages.css'

], function(QUI, QUIControl, QUISelect) {
    'use strict';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/matomo/bin/controls/TagManagerCodesLanguages',

        Binds: [
            '$onImport',
            '$showLanguageSnippet',
            'setProject'
        ],

        initialize: function(options) {
            this.$Elm = null;
            this.$SnippetContainer = null;
            this.$Languages = null;
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

            console.log(this.getElm());

            const Container = new Element('div').wraps(this.getElm());

            Container.addClass('field-container-field');
            Container.addClass('quiqqer-matomo-snippet-language');

            this.$Languages = new QUISelect({
                events: {
                    onChange: this.$showLanguageSnippet
                },
                styles: {
                    marginBottom: 10,
                    width: '100%'
                }
            }).inject(Container);

            this.$SnippetContainer = new Element('div', {
                styles: {
                    minHeight: 240
                }
            }).inject(Container);

            this.$Languages.disable();

            const project = this.$Project.getName();

            this.$Project.getConfig().then((config) => {
                config.langs.trim(',').split(',').forEach((language) => {
                    this.$Languages.appendChild(
                        project + ' ( ' + language + ' )',
                        language,
                        URL_BIN_DIR + '16x16/flags/' + language + '.png'
                    );
                });

                this.$Languages.enable();
            });
        },

        $showLanguageSnippet: function() {
            const language = this.$Languages.getValue();

            if (language === '') {
                this.$SnippetContainer.set('html', '');
                return;
            }

            this.$Languages.disable();
            this.$SnippetContainer.set('html', '');

            const Textarea = new Element('textarea', {
                'data-qui': 'package/quiqqer/html-snippets/bin/backend/controls/SnippetInput',
                'data-qui-options-snippet': 'matomo-tm-' + language,
                'data-qui-options-event': 'onQuiqqer::template::header::begin'
            }).inject(this.$SnippetContainer);

            QUI.parse(this.$SnippetContainer).then(() => {
                this.$Languages.enable();
                QUI.Controls.getById(Textarea.get('data-quiid')).setProject(this.$Project);
            });
        }
    });
});
