const ApiService = Shopware.Classes.ApiService;

export default class RemovalApiService extends ApiService {
  constructor(httpClient, loginService, apiEndpoint = 'eyecook/blurhash') {
    super(httpClient, loginService, apiEndpoint);
    this.name = 'ecbRemovalApiService';
  }

  async fetchRemoveByMediaId(mediaId) {
    return this.httpClient.get(
      `/${this.getApiBasePath(`remove/media/${mediaId}`, '_action')}`,
      { headers: this.getBasicHeaders() }
    ).then(({ data }) => data);
  }

  async fetchRemoveByMediaIds(mediaIds) {
    return this.httpClient.post(
      `/${this.getApiBasePath('remove/media', '_action')}`,
      { mediaIds },
      { headers: this.getBasicHeaders() },
    ).then(response => ApiService.handleResponse(response));
  }

  async fetchRemoveByFolderId(folderId) {
    return this.httpClient.get(
      `/${this.getApiBasePath(`remove/folder/${folderId}`, '_action')}`,
      { headers: this.getBasicHeaders() },
    ).then(response => ApiService.handleResponse(response));
  }

  async fetchRemoveByFolderIds(folderIds) {
    return this.httpClient.post(
      `/${this.getApiBasePath('remove/folder', '_action')}`,
      { folderIds },
      { headers: this.getBasicHeaders() },
    ).then(response => ApiService.handleResponse(response));
  }
}
