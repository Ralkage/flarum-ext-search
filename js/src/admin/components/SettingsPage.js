import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';

export default class SettingsPage extends ExtensionPage {
    oninit(vnode) {
        super.oninit(vnode);
        this.reindexing = false;
        this.health = null;
        this.checkHealth();
    }

    checkHealth() {
        app.request({
            method: 'GET',
            url: app.forum.attribute('apiUrl') + '/search/health',
        }).then((response) => {
            this.health = response;
            m.redraw();
        }).catch(() => {
            this.health = { ok: false, error: 'unreachable' };
            m.redraw();
        });
    }

    reindex() {
        if (this.reindexing) return;
        this.reindexing = true;

        app.request({
            method: 'POST',
            url: app.forum.attribute('apiUrl') + '/search/reindex',
        }).then((response) => {
            this.reindexing = false;
            app.alerts.show({ type: 'success' }, app.translator.trans('flarum-ext-search.admin.settings.reindex_done', {
                discussions: response.discussions,
                posts: response.posts,
            }));
            m.redraw();
        }).catch(() => {
            this.reindexing = false;
            app.alerts.show({ type: 'error' }, app.translator.trans('flarum-ext-search.admin.settings.reindex_failed'));
            m.redraw();
        });
    }

    content() {
        const t = app.translator.trans.bind(app.translator);

        return (
            <div className="ExtensionPage-settings">
                <div className="container">
                    <h3>{t('flarum-ext-search.admin.settings.connection_title')}</h3>

                    <div className="Form-group">
                        <label>{t('flarum-ext-search.admin.settings.host_label')}</label>
                        <p className="helpText">{t('flarum-ext-search.admin.settings.host_help')}</p>
                        <input className="FormControl" type="text"
                            bidi={this.setting('flarum-ext-search.host')} />
                    </div>

                    <div className="Form-group">
                        <label>{t('flarum-ext-search.admin.settings.api_key_label')}</label>
                        <p className="helpText">{t('flarum-ext-search.admin.settings.api_key_help')}</p>
                        <input className="FormControl" type="password"
                            bidi={this.setting('flarum-ext-search.api_key')} />
                    </div>

                    <div className="Form-group">
                        <label>{t('flarum-ext-search.admin.settings.index_prefix_label')}</label>
                        <p className="helpText">{t('flarum-ext-search.admin.settings.index_prefix_help')}</p>
                        <input className="FormControl" type="text"
                            bidi={this.setting('flarum-ext-search.index_prefix')} />
                    </div>

                    <div className="Form-group">
                        <label>{t('flarum-ext-search.admin.settings.hit_limit_label')}</label>
                        <p className="helpText">{t('flarum-ext-search.admin.settings.hit_limit_help')}</p>
                        <input className="FormControl" type="number" min="10" max="5000"
                            bidi={this.setting('flarum-ext-search.hit_limit')} />
                    </div>

                    {this.submitButton()}

                    <h3>{t('flarum-ext-search.admin.settings.status_title')}</h3>

                    <div className="Form-group">
                        {this.health === null ? (
                            <p>{t('flarum-ext-search.admin.settings.checking_health')}</p>
                        ) : this.health.ok ? (
                            <p style="color: #27ae60; font-weight: 600;">
                                ✓ {t('flarum-ext-search.admin.settings.health_ok', {
                                    discussions: this.health.discussions || 0,
                                    posts: this.health.posts || 0,
                                })}
                            </p>
                        ) : (
                            <p style="color: #c0392b; font-weight: 600;">
                                ✗ {t('flarum-ext-search.admin.settings.health_failed')}
                            </p>
                        )}
                    </div>

                    <div className="Form-group">
                        <Button
                            className="Button Button--primary"
                            loading={this.reindexing}
                            onclick={() => this.reindex()}
                        >
                            {t('flarum-ext-search.admin.settings.reindex_button')}
                        </Button>
                        <p className="helpText">{t('flarum-ext-search.admin.settings.reindex_help')}</p>
                    </div>
                </div>
            </div>
        );
    }
}
