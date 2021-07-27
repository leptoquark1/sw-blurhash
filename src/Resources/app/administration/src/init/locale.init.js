import deDEMessages from '../snippet/de-DE.json';
import enGBMessages from '../snippet/en-GB.json';

(async function() {
    const factoryContainer = Shopware.Application.getContainer('factory');
    const localeFactory = factoryContainer.locale;

    localeFactory.extend('de-DE', deDEMessages);
    localeFactory.extend('en-GB', enGBMessages);

    return localeFactory;
})().catch();
