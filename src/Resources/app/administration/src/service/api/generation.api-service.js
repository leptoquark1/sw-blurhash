const ApiService = Shopware.Classes.ApiService;

export default class GenerationApiService extends ApiService {
  constructor(httpClient, loginService, apiEndpoint = 'eyecook/blurhash') {
    super(httpClient, loginService, apiEndpoint);
    this.name = 'ecbGenerationApiService';
  }

  async fetchGenerateByMediaId(mediaId) {
    return this.httpClient.get(
      `/${this.getApiBasePath(`generate/media/${mediaId}`, '_action')}`,
      { headers: this.getBasicHeaders() }
    ).then(({ data }) => data);
  }

  async fetchGenerateByMediaIds(mediaIds) {
    return this.httpClient.post(
      `/${this.getApiBasePath('generate/media', '_action')}`,
      { mediaIds },
      { headers: this.getBasicHeaders() },
    ).then(response => ApiService.handleResponse(response));
  }

  async fetchGenerateByFolderId(folderId, all) {
    return this.httpClient.get(
      `/${this.getApiBasePath(`generate/folder/${folderId}`, '_action')}`,
      { headers: this.getBasicHeaders(), params: { all: !!all } },
    ).then(response => ApiService.handleResponse(response));
  }

  async fetchGenerateByFolderIds(folderIds, all) {
    return this.httpClient.post(
      `/${this.getApiBasePath('generate/folder', '_action')}`,
      { folderIds, all: !!all },
      { headers: this.getBasicHeaders() },
    ).then(response => ApiService.handleResponse(response));
  }
}
