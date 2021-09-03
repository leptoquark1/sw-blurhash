const ApiService = Shopware.Classes.ApiService;

export default class ValidationApiService extends ApiService {
  constructor(httpClient, loginService, apiEndpoint = 'eyecook/blurhash') {
    super(httpClient, loginService, apiEndpoint);
    this.name = 'ecbValidationApiService';
  }

  async fetchValidateByMediaId(mediaId) {
    return this.httpClient.get(
      `/${this.getApiBasePath(`validator/media/${mediaId}`, '_action')}`,
      { headers: this.getBasicHeaders() },
    ).then(response => ApiService.handleResponse(response));
  }

  async fetchValidateByFolderId(folderId) {
    return this.httpClient.get(
      `/${this.getApiBasePath(`validator/folder/${folderId}`, '_action')}`,
      { headers: this.getBasicHeaders() },
    ).then(response => ApiService.handleResponse(response));
  }
}
