const path = require('path');

module.exports = (Encore) => {
    Encore.addEntry('ibexa-admin-ui-masilia-sites-react-js', [
        path.resolve(__dirname, '../public/admin/sites/index.tsx'),
    ]);

    Encore.addEntry('ibexa-admin-ui-masilia-sites-react-css', [
        path.resolve(__dirname, '../public/admin/sites/sites.scss'),
    ]);
};
