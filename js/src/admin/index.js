import app from 'flarum/admin/app';
import SettingsPage from './components/SettingsPage';

app.initializers.add('ralkage-search', () => {
    app.extensionData.for('ralkage-search')
        .registerPage(SettingsPage);
});
