import GenerationApiService from '../service/api/generation.api-service';
import RemovalApiService from '../service/api/removal.api-service';
import ValidationApiService from '../service/api/validation.api-service';

const { Application } = Shopware;

const apiServices = [
  GenerationApiService,
  ValidationApiService,
  RemovalApiService,
];

(function () {
  /**
   * @see @Administration:src/app/init-pre/api-services.init.js
   */
  apiServices.forEach((ApiService) => {
    const factoryContainer = Application.getContainer('factory');
    const initContainer = Application.getContainer('init');

    const apiServiceFactory = factoryContainer.apiService;
    const service = new ApiService(initContainer.httpClient, Shopware.Service('loginService'));
    const serviceName = service.name;
    apiServiceFactory.register(serviceName, service);

    Application.addServiceProvider(serviceName, () => {
      return service;
    });
  });
})();
